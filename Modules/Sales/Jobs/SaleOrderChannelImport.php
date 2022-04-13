<?php

namespace Modules\Sales\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use \Exception;
use Illuminate\Support\Facades\Log;
use Modules\Base\Entities\RestApiService;
use App\Models\User;
use Modules\Sales\Entities\SaleCustomer;
use Modules\Sales\Entities\SaleOrder;
use Modules\Sales\Entities\SaleOrderItem;
use Modules\Sales\Entities\SaleOrderAddress;
use Modules\Sales\Entities\SaleOrderPayment;
use Modules\Sales\Entities\SaleOrderStatusHistory;
use Modules\Sales\Entities\SaleOrderProcessHistory;

class SaleOrderChannelImport implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 300;

    private $restApiService = null;

    private $restApiChannel = '';

    private $fromDate = null;

    private $toDate = null;

    private $processUser = null;

    private $dateDifference = 14;

    private $allowedSaleOrderStatuses = [
        'pending',
        'processing',
        'being_prepared',
        /*'holded',
        'order_updated',*/
        'closed',
        'ready_to_dispatch',
        /*'out_for_delivery',
        'delivered',
        'canceled',*/
    ];

    /**
     * Create a new job instance.
     *
     * @param string $channel
     * @param string $fromDateString
     * @param string $toDateString
     * @param int|null $processUser
     */
    public function __construct($channel = '', $fromDateString = '', $toDateString = '', $processUser = 0)
    {
        $this->onQueue('saleOrderImport');
        $this->restApiService = new RestApiService();
        $this->setApiChannel($channel);
        $this->restApiChannel = $this->restApiService->getCurrentApiChannel();
        $givenFromDate = (is_string($fromDateString) && (trim($fromDateString) != ''))
            ? date('Y-m-d H:i:s', strtotime($fromDateString)) : date('Y-m-d', strtotime('-' . $this->dateDifference . ' days'));
        $givenToDate = (is_string($toDateString) && (trim($toDateString) != ''))
            ? date('Y-m-d H:i:s', strtotime($toDateString)) : date('Y-m-d', strtotime('+1 days'));
        if ($givenToDate > $givenFromDate) {
            $this->fromDate = $givenFromDate;
            $this->toDate = $givenToDate;
        } else {
            $this->fromDate = $givenToDate;
            $this->toDate = $givenFromDate;
        }
        if (!is_null($processUser) && is_numeric($processUser) && ((int)$processUser > 0)) {
            $targetUser = User::find((int)$processUser);
            if ($targetUser) {
                $this->processUser = $targetUser;
            }
        }
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return 'SaleOrderChannelImport_' . strtolower(str_replace(' ', '-', trim($this->restApiChannel)))  . '_' . $this->fromDate . '_' . $this->toDate;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        Log::info($this->uniqueId() . ': Started the Job Process.');

        $storeConfig = $this->getStoreConfigs();

        $orderIdApiResponse = $this->getOrderIdsBetweenDates();
        if (is_array($orderIdApiResponse) && (count($orderIdApiResponse) > 0)) {
            if (
                array_key_exists('items', $orderIdApiResponse)
                && is_array($orderIdApiResponse['items'])
                && (count($orderIdApiResponse['items']) > 0)
            ) {

                $currentApiEnv = $this->restApiService->getApiEnvironment();
                $currentApiChannel = $this->restApiService->getCurrentApiChannel();

                foreach ($orderIdApiResponse['items'] as $saleOrderIdEl) {

                    $saleOrderId = $saleOrderIdEl['entity_id'];
                    $saleOrderEl = $this->getOrderDetailsById($saleOrderId);
                    if (is_array($saleOrderEl) && (count($saleOrderEl) > 0)) {

                        $customerResponse = $this->processSaleCustomer($currentApiEnv, $currentApiChannel, $saleOrderEl);
                        if ($customerResponse['status']) {

                            $customerObj = $customerResponse['customerObj'];

                            $saleResponse = $this->processSaleOrder($currentApiEnv, $currentApiChannel, $saleOrderEl, $customerObj);
                            if ($saleResponse['status']) {

                                $saleOrderObj = $saleResponse['saleOrderObj'];
                                $orderAlreadyCreated = $saleResponse['orderAlreadyCreated'];

                                if(is_array($saleOrderEl['items']) && (count($saleOrderEl['items']) > 0)) {
                                    foreach ($saleOrderEl['items'] as $orderItemEl) {
                                        $orderItemResponse = $this->processSaleOrderItem($orderItemEl, $saleOrderObj, $storeConfig);
                                        if(!$orderItemResponse['status']) {
                                            Log::error($this->uniqueId() . ': Could not process Order Item #' . $orderItemEl['item_id'] . ' for Sale Order #' . $saleOrderId . '. '  . $orderItemResponse['message']);
                                        }
                                    }
                                } else {
                                    Log::error($this->uniqueId() . ': There is no Order Item for Sale Order #' . $saleOrderId . '.');
                                }

                                $billingResponse = $this->processSaleOrderBillingAddress($saleOrderEl, $saleOrderObj);
                                if (!$billingResponse['status']) {
                                    Log::error($this->uniqueId() . ': Could not process Billing Address data for Sale Order #' . $saleOrderId . '. ' . $billingResponse['message']);
                                }

                                $shippingResponse = $this->processSaleOrderShippingAddress($saleOrderEl, $saleOrderObj);
                                if (!$shippingResponse['status']) {
                                    Log::error($this->uniqueId() . ': Could not process Shipping Address data for Sale Order #' . $saleOrderId . '. ' . $shippingResponse['message']);
                                }

                                $paymentResponse = $this->processSaleOrderPayments($saleOrderEl, $saleOrderObj);
                                if (!$paymentResponse['status']) {
                                    Log::error($this->uniqueId() . ': Could not process Payment data for Sale Order #' . $saleOrderId . '. ' . $paymentResponse['message']);
                                }

                                if(is_array($saleOrderEl['status_histories']) && (count($saleOrderEl['status_histories']) > 0)) {
                                    foreach ($saleOrderEl['status_histories'] as $historyEl) {
                                        $historyResponse = $this->processSaleOrderStatusHistory($historyEl, $saleOrderObj);
                                        if(!$historyResponse['status']) {
                                            Log::error($this->uniqueId() . ': Could not process Status History #' . $historyEl['entity_id'] . ' for Sale Order #' . $saleOrderId . '. '  . $historyResponse['message']);
                                        }
                                    }
                                } else {
                                    Log::error($this->uniqueId() . ': There is no Status History data for Sale Order #' . $saleOrderId . '.');
                                }

                                $processResponse = $this->recordOrderStatusProcess($saleOrderObj, $orderAlreadyCreated);
                                if (!$processResponse['status']) {
                                    Log::error($this->uniqueId() . ': Could not record the processing of Sale Order #' . $saleOrderId . '. ' . $processResponse['message']);
                                }

                                /*Log::info($this->uniqueId() . ': Finished processing Sale Order #' . $saleOrderId . '.');*/

                            } else {
                                Log::error($this->uniqueId() . ': Could not process Sale Order data for Sale Order #' . $saleOrderId . '. ' . $saleResponse['message']);
                            }

                        } else {
                            Log::error($this->uniqueId() . ': Could not process Customer data for Sale Order #' . $saleOrderId . '. ' . $customerResponse['message']);
                        }

                    } else {
                        Log::error($this->uniqueId() . ': Could not fetch the data for Sale Order #' . $saleOrderId . '.');
                    }

                }

            } else {
                Log::error($this->uniqueId() . ': No Sale Orders to fetch.');
            }
        } else {
            Log::error($this->uniqueId() . ': Could not fetch the Sale Order List.');
        }

        Log::info($this->uniqueId() . ': Finished the Job Process.');

    }

    /**
     * Switch to the given RESTFul API Channel
     *
     * @param string $channel
     */
    public function setApiChannel($channel = '') {
        if ($this->restApiService->isValidApiChannel($channel)) {
            $this->restApiService->setApiChannel($channel);
        }
    }

    /**
     * Fetch the Store Configurations.
     *
     * @return array
     */
    private function getStoreConfigs() {

        $uri = $this->restApiService->getRestApiUrl() . 'store/storeConfigs';
        $apiResult = $this->restApiService->processGetApi($uri);
        if ($apiResult['status']) {
            if (is_array($apiResult['response']) && array_key_exists('0', $apiResult['response'])) {
                return $apiResult['response'][0];
            } else {
                return [];
            }
        } else {
            return [];
        }

    }

    /**
     * Fetch the list of Sale Order Ids from the API Channel using the filters.
     *
     * @return array
     */
    private function getOrderIdsBetweenDates() {

        $uri = $this->restApiService->getRestApiUrl() . 'orders';
        $qParams = [
            'searchCriteria[filter_groups][0][filters][0][field]' => 'status',
            'searchCriteria[filter_groups][0][filters][0][condition_type]' => 'in',
            'searchCriteria[filter_groups][0][filters][0][value]' => implode(',', $this->allowedSaleOrderStatuses),
            'searchCriteria[filter_groups][1][filters][0][field]' => 'created_at',
            'searchCriteria[filter_groups][1][filters][0][condition_type]' => 'gteq',
            'searchCriteria[filter_groups][1][filters][0][value]' => date('Y-m-d H:i:s', strtotime($this->fromDate)),
            'searchCriteria[filter_groups][2][filters][0][field]' => 'created_At',
            'searchCriteria[filter_groups][2][filters][0][condition_type]' => 'lteq',
            'searchCriteria[filter_groups][2][filters][0][value]' => date('Y-m-d H:i:s', strtotime($this->toDate)),
            'fields' => 'items[entity_id]'
        ];
        $apiResult = $this->restApiService->processGetApi($uri, $qParams);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    /**
     * Fetch the Order details from the API Channel.
     *
     * @param string $orderId
     *
     * @return array
     */
    private function getOrderDetailsById($orderId = '') {

        $uri = $this->restApiService->getRestApiUrl() . 'orders/' . $orderId;
        $apiResult = $this->restApiService->processGetApi($uri);

        return ($apiResult['status']) ? $apiResult['response'] : [];

        /*$uri = $this->restApiService->getRestApiUrl() . 'orders';
        $qParams = [
            'searchCriteria[filter_groups][0][filters][0][field]' => 'entity_id',
            'searchCriteria[filter_groups][0][filters][0][condition_type]' => 'eq',
            'searchCriteria[filter_groups][0][filters][0][value]' => $orderId
        ];
        $apiResult = $this->restApiService->processGetApi($uri, $qParams);
        if (!$apiResult['status']) {
            return [];
        }

        if (!is_array($apiResult['response']) || (count($apiResult['response']) == 0)) {
            return [];
        }

        return (
            array_key_exists('items', $apiResult['response'])
            && is_array($apiResult['response']['items'])
            && (count($apiResult['response']['items']) > 0)
        ) ? $apiResult['response']['items'][0] : [];*/

    }

    /**
     * Set and Insert the Sale Customer Data.
     *
     * @param string $currentApiEnv
     * @param string $currentApiChannel
     * @param array $saleOrderEl
     *
     * @return array
     */
    private function processSaleCustomer($currentApiEnv = '', $currentApiChannel = '', $saleOrderEl = []) {

        try {

            $customerObj = SaleCustomer::updateOrCreate([
                'env' => $currentApiEnv,
                'channel' => $currentApiChannel,
                'contact_number' => $saleOrderEl['billing_address']['telephone'],
                'email_id' => $saleOrderEl['customer_email'],
            ], [
                'customer_group_id' => $saleOrderEl['customer_group_id'],
                'sale_customer_id' => ((array_key_exists('customer_id', $saleOrderEl)) ? $saleOrderEl['customer_id'] : null),
                'first_name' => $saleOrderEl['customer_firstname'],
                'last_name' => $saleOrderEl['customer_lastname'],
                'gender' => ((array_key_exists('customer_gender', $saleOrderEl)) ? $saleOrderEl['customer_gender'] : null),
                'is_active' => 1
            ]);

            return [
                'status' => true,
                'message' => '',
                'customerObj' => $customerObj
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * Set and Insert the Sale Order Data.
     *
     * @param string $currentApiEnv
     * @param string $currentApiChannel
     * @param array $saleOrderEl
     * @param SaleCustomer|null $customerObj
     *
     * @return array
     */
    private function processSaleOrder($currentApiEnv = '', $currentApiChannel = '', $saleOrderEl = [], SaleCustomer $customerObj = null) {

        try {

            $orderShippingAddress = $saleOrderEl['extension_attributes']['shipping_assignments'][0]['shipping']['address'];

            $orderAlreadyCreated = true;
            $saleOrderObj = SaleOrder::where('env', $currentApiEnv)
                ->where('channel', $currentApiChannel)
                ->where('order_id', $saleOrderEl['entity_id'])
                ->where('increment_id', $saleOrderEl['increment_id'])
                ->first();
            if (!$saleOrderObj) {
                $orderAlreadyCreated = false;
                $saleOrderObj = (new SaleOrder())->create([
                    'env' => $currentApiEnv,
                    'channel' => $currentApiChannel,
                    'order_id' => $saleOrderEl['entity_id'],
                    'increment_id' => $saleOrderEl['increment_id'],
                    'order_created_at' => $saleOrderEl['created_at'],
                    'order_updated_at' => $saleOrderEl['updated_at'],
                    'customer_id' => $customerObj->id,
                    'is_guest' => $saleOrderEl['customer_is_guest'],
                    'customer_firstname' => $saleOrderEl['customer_firstname'],
                    'customer_lastname' => $saleOrderEl['customer_lastname'],
                    'region_id' => $orderShippingAddress['region_id'],
                    'region_code' => $orderShippingAddress['region_code'],
                    'region' => $orderShippingAddress['region'],
                    'city' => $orderShippingAddress['city'],
                    'zone_id' => ((array_key_exists('zone_id', $saleOrderEl['extension_attributes'])) ? $saleOrderEl['extension_attributes']['zone_id'] : null),
                    'store' => $saleOrderEl['store_name'],
                    'delivery_date' => ((array_key_exists('order_delivery_date', $saleOrderEl['extension_attributes'])) ? date('Y-m-d', strtotime($saleOrderEl['extension_attributes']['order_delivery_date'])) : null),
                    'delivery_time_slot' => ((array_key_exists('order_delivery_time', $saleOrderEl['extension_attributes'])) ? $saleOrderEl['extension_attributes']['order_delivery_time'] : null),
                    'delivery_notes' => ((array_key_exists('order_delivery_note', $saleOrderEl['extension_attributes'])) ? $saleOrderEl['extension_attributes']['order_delivery_note'] : null),
                    'total_item_count' => $saleOrderEl['total_item_count'],
                    'total_qty_ordered' => $saleOrderEl['total_qty_ordered'],
                    'order_weight' => $saleOrderEl['weight'],
                    'box_count' => (isset($saleOrderEl['extension_attributes']['box_count'])) ? $saleOrderEl['extension_attributes']['box_count'] : null,
                    'not_require_pack' => (isset($saleOrderEl['extension_attributes']['not_require_pack'])) ? $saleOrderEl['extension_attributes']['not_require_pack'] : 1,
                    'order_currency' => $saleOrderEl['order_currency_code'],
                    'order_subtotal' => $saleOrderEl['subtotal'],
                    'order_tax' => $saleOrderEl['tax_amount'],
                    'discount_amount' => $saleOrderEl['discount_amount'],
                    'shipping_total' => $saleOrderEl['shipping_amount'],
                    'shipping_method' => $saleOrderEl['shipping_description'],
                    'order_total' => $saleOrderEl['grand_total'],
                    'order_due' => $saleOrderEl['total_due'],
                    'order_state' => $saleOrderEl['state'],
                    'order_status' => $saleOrderEl['status'],
                    'order_status_label' => (isset($saleOrderEl['extension_attributes']['order_status_label'])) ? $saleOrderEl['extension_attributes']['order_status_label'] : null,
                    'to_be_synced' => 0,
                    'is_synced' => 0,
                    'is_active' => 1,
                ]);
            }

            return [
                'status' => true,
                'message' => '',
                'saleOrderObj' => $saleOrderObj,
                'orderAlreadyCreated' => $orderAlreadyCreated,
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * Set and Insert the Sale Order Item Data.
     *
     * @param array $orderItemEl
     * @param SaleOrder|null $saleOrderObj
     * @param array $storeConfig
     *
     * @return array
     */
    private function processSaleOrderItem($orderItemEl = [], SaleOrder $saleOrderObj = null, $storeConfig = []) {

        try {

            $mediaUrl = $storeConfig['base_media_url'];
            $productImageUrlSegment = 'catalog/product';
            $productImageUrl = $mediaUrl . $productImageUrlSegment;

            $itemExtAttr = $orderItemEl['extension_attributes'];

            $saleOrderItemObj = SaleOrderItem::firstOrCreate([
                'order_id' => $saleOrderObj->id,
                'item_id' => $orderItemEl['item_id'],
                'sale_order_id' => $saleOrderObj->order_id
            ], [
                'item_created_at' => $orderItemEl['created_at'],
                'item_updated_at' => $orderItemEl['updated_at'],
                'product_id' => $orderItemEl['product_id'],
                'product_type' => $orderItemEl['product_type'],
                'item_sku' => $orderItemEl['sku'],
                'item_barcode' => ((array_key_exists('barcode', $itemExtAttr)) ? $itemExtAttr['barcode'] : $orderItemEl['sku']),
                'item_name' => ((array_key_exists('product_en_name', $itemExtAttr)) ? $itemExtAttr['product_en_name'] : $orderItemEl['name']),
                'item_info' => ((array_key_exists('pack_weight_info', $itemExtAttr)) ? $itemExtAttr['pack_weight_info'] : $itemExtAttr['product_weight']),
                'item_image' => $productImageUrl . $itemExtAttr['product_image'],
                'actual_qty' => ((array_key_exists('actual_qty', $itemExtAttr)) ? $itemExtAttr['actual_qty'] : $orderItemEl['qty_ordered']),
                'qty_ordered' => $orderItemEl['qty_ordered'],
                'qty_shipped' => $orderItemEl['qty_shipped'],
                'qty_invoiced' => $orderItemEl['qty_invoiced'],
                'qty_canceled' => $orderItemEl['qty_canceled'],
                'qty_returned' => ((array_key_exists('qty_returned', $orderItemEl)) ? $orderItemEl['qty_returned'] : null),
                'qty_refunded' => $orderItemEl['qty_refunded'],
                'selling_unit' => $itemExtAttr['unit'],
                'selling_unit_label' => $itemExtAttr['product_weight'],
                'billing_period' => ((array_key_exists('billing_period', $itemExtAttr)) ? $itemExtAttr['billing_period'] : null),
                'delivery_day' => ((array_key_exists('delivery_day', $itemExtAttr)) ? $itemExtAttr['delivery_day'] : null),
                'scale_number' => ((array_key_exists('scale_number', $itemExtAttr)) ? $itemExtAttr['scale_number'] : null),
                'country_label' => $itemExtAttr['country_of_manufacture'],
                'item_weight' => $orderItemEl['row_weight'],
                'price' => $orderItemEl['price'],
                'row_total' => $orderItemEl['row_total'],
                'tax_amount' => $orderItemEl['tax_amount'],
                'tax_percent' => $orderItemEl['tax_percent'],
                'discount_amount' => $orderItemEl['discount_amount'],
                'discount_percent' => $orderItemEl['discount_percent'],
                'row_grand_total' => $orderItemEl['row_total_incl_tax'],
                'vendor_id' => ((array_key_exists('vendor_id', $itemExtAttr)) ? $itemExtAttr['vendor_id'] : null),
                'vendor_availability' => ((array_key_exists('vendor_availability', $itemExtAttr)) ? $itemExtAttr['vendor_availability'] : 0),
                'is_active' => 1
            ]);

            return [
                'status' => true,
                'message' => '',
                'saleOrderItemObj' => $saleOrderItemObj
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * Set and Insert the Sale Order Billing Address Data.
     *
     * @param array $saleOrderEl
     * @param SaleOrder|null $saleOrderObj
     *
     * @return array
     */
    private function processSaleOrderBillingAddress($saleOrderEl = [], SaleOrder $saleOrderObj = null) {

        try {

            $billingAddressObj = SaleOrderAddress::firstOrCreate([
                'order_id' => $saleOrderObj->id,
                'address_id' => $saleOrderEl['billing_address']['entity_id'],
                'sale_order_id' => $saleOrderEl['entity_id'],
                'type' => 'billing',
            ], [
                'first_name' => $saleOrderEl['billing_address']['firstname'],
                'last_name' => $saleOrderEl['billing_address']['lastname'],
                'email_id' => $saleOrderEl['billing_address']['email'],
                'address_1' => $saleOrderEl['billing_address']['street'][0],
                'address_2' => ((array_key_exists(1, $saleOrderEl['billing_address']['street'])) ? $saleOrderEl['billing_address']['street'][1] : null),
                'address_3' => ((array_key_exists(2, $saleOrderEl['billing_address']['street'])) ? $saleOrderEl['billing_address']['street'][2] : null),
                'city' => $saleOrderEl['billing_address']['city'],
                'region_id' => $saleOrderEl['billing_address']['region_id'],
                'region_code' => $saleOrderEl['billing_address']['region_code'],
                'region' => $saleOrderEl['billing_address']['region'],
                'country_id' => $saleOrderEl['billing_address']['country_id'],
                'post_code' => $saleOrderEl['billing_address']['postcode'],
                'contact_number' => $saleOrderEl['billing_address']['telephone'],
                'is_active' => 1
            ]);

            return [
                'status' => true,
                'message' => '',
                'billingAddressObj' => $billingAddressObj
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * Set and Insert the Sale Order Shipping Address Data.
     *
     * @param array $saleOrderEl
     * @param SaleOrder|null $saleOrderObj
     *
     * @return array
     */
    private function processSaleOrderShippingAddress($saleOrderEl = [], SaleOrder $saleOrderObj = null) {

        try {

            $orderShippingAddress = $saleOrderEl['extension_attributes']['shipping_assignments'][0]['shipping']['address'];

            $shippingAddressObj = SaleOrderAddress::firstOrCreate([
                'order_id' => $saleOrderObj->id,
                'address_id' => $orderShippingAddress['entity_id'],
                'sale_order_id' => $saleOrderEl['entity_id'],
                'type' => 'shipping',
            ], [
                'first_name' => $orderShippingAddress['firstname'],
                'last_name' => $orderShippingAddress['lastname'],
                'email_id' => $orderShippingAddress['email'],
                'address_1' => $orderShippingAddress['street'][0],
                'address_2' => ((array_key_exists(1, $orderShippingAddress['street'])) ? $orderShippingAddress['street'][1] : null),
                'address_3' => ((array_key_exists(2, $orderShippingAddress['street'])) ? $orderShippingAddress['street'][2] : null),
                'city' => $orderShippingAddress['city'],
                'region_id' => $orderShippingAddress['region_id'],
                'region_code' => $orderShippingAddress['region_code'],
                'region' => $orderShippingAddress['region'],
                'country_id' => $orderShippingAddress['country_id'],
                'post_code' => $orderShippingAddress['postcode'],
                'contact_number' => $orderShippingAddress['telephone'],
                'is_active' => 1
            ]);

            return [
                'status' => true,
                'message' => '',
                'shippingAddressObj' => $shippingAddressObj
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * Set and Insert the Sale Order Payments Data.
     *
     * @param array $saleOrderEl
     * @param SaleOrder|null $saleOrderObj
     *
     * @return array
     */
    private function processSaleOrderPayments($saleOrderEl = [], SaleOrder $saleOrderObj = null) {

        try {

            $paymentObj = SaleOrderPayment::firstOrCreate([
                'order_id' => $saleOrderObj->id,
                'payment_id' => $saleOrderEl['payment']['entity_id'],
                'sale_order_id' => $saleOrderEl['entity_id'],
            ], [
                'method' => $saleOrderEl['payment']['method'],
                'amount_payable' => $saleOrderEl['payment']['amount_ordered'],
                'amount_paid' => ((array_key_exists('amount_paid', $saleOrderEl['payment'])) ? $saleOrderEl['payment']['amount_paid'] : null),
                'cc_last4' => ((array_key_exists('cc_last4', $saleOrderEl['payment'])) ? $saleOrderEl['payment']['cc_last4'] : null),
                'cc_start_month' => ((array_key_exists('cc_ss_start_month', $saleOrderEl['payment'])) ? $saleOrderEl['payment']['cc_ss_start_month'] : null),
                'cc_start_year' => ((array_key_exists('cc_ss_start_year', $saleOrderEl['payment'])) ? $saleOrderEl['payment']['cc_ss_start_year'] : null),
                'cc_exp_year' => ((array_key_exists('cc_exp_year', $saleOrderEl['payment'])) ? $saleOrderEl['payment']['cc_exp_year'] : null),
                'shipping_amount' => $saleOrderEl['payment']['shipping_amount'],
                'shipping_captured' => ((array_key_exists('shipping_captured', $saleOrderEl['payment'])) ? $saleOrderEl['payment']['shipping_captured'] : null),
                'extra_info' => json_encode($saleOrderEl['extension_attributes']['payment_additional_info']),
                'is_active' => 1
            ]);

            return [
                'status' => true,
                'message' => '',
                'paymentObj' => $paymentObj
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * Set and Insert the Sale Order Status History Data.
     *
     * @param array $historyEl
     * @param SaleOrder|null $saleOrderObj
     *
     * @return array
     */
    private function processSaleOrderStatusHistory($historyEl = [], SaleOrder $saleOrderObj = null) {

        try {

            $statusHistoryObj = SaleOrderStatusHistory::firstOrCreate([
                'order_id' => $saleOrderObj->id,
                'history_id' => $historyEl['entity_id'],
                'sale_order_id' => $saleOrderObj->order_id,
            ], [
                'name' => $historyEl['entity_name'],
                'status' => $historyEl['status'],
                'comments' => $historyEl['comment'],
                'status_created_at' => $historyEl['created_at'],
                'customer_notified' => $historyEl['is_customer_notified'],
                'visible_on_front' => $historyEl['is_visible_on_front'],
                'is_active' => 1
            ]);

            return [
                'status' => true,
                'message' => '',
                'statusHistoryObj' => $statusHistoryObj
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * Record the Processing of Sale Order.
     *
     * @param SaleOrder|null $saleOrderObj
     * @param bool $orderAlreadyCreated
     *
     * @return array
     */
    private function recordOrderStatusProcess(SaleOrder $saleOrderObj = null, $orderAlreadyCreated = true) {

        try {

            if (!$orderAlreadyCreated || ($orderAlreadyCreated && $this->processUser)) {
                $givenAction = ($orderAlreadyCreated)
                    ? SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_REIMPORT
                    : SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_IMPORT;
                $saleOrderProcessHistoryObj = (new SaleOrderProcessHistory())->create([
                    'order_id' => $saleOrderObj->id,
                    'action' => $givenAction,
                    'status' => 1,
                    'comments' => 'The Sale Order Id #' . $saleOrderObj->order_id . ' is ' . (($orderAlreadyCreated) ? 're-imported' : 'imported') . '.',
                    'extra_info' => null,
                    'done_by' => ($this->processUser) ? $this->processUser->id : null,
                    'done_at' => date('Y-m-d H:i:s'),
                ]);
            }

            return [
                'status' => true,
                'message' => '',
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

    }

}
