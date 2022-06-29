<?php

namespace Modules\Sales\Entities;

use Modules\Base\Entities\BaseServiceHelper;
use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrder;
use DB;
use Modules\Sales\Entities\SaleOrderPayment;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\Sales\Entities\SaleOrderStatusHistory;
use App\Models\User;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;
use Modules\API\Entities\ApiServiceHelper;

class SalesApiServiceHelper
{

    private $restApiService = null;

    public function __construct($channel = '')
    {
        $this->restApiService = new RestApiService();
        $this->setApiChannel($channel);
    }

    public function getApiEnvironment() {
        return $this->restApiService->getApiEnvironment();
    }

    /**
     * Get the current RESTFul API Channel.
     * @return string
     */
    public function getApiChannel() {
        return $this->restApiService->getCurrentApiChannel();
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
     * Get the list of all the available API Channels.
     *
     * @return array
     */
    public function getAllAvailableChannels() {
        return $this->restApiService->getAllAvailableApiChannels();
    }

    /**
     * Get the given DateTime string in the given DateTime format
     *
     * @param string $dateTimeString
     * @param string $format
     *
     * @return string
     */
    public function getFormattedTime($dateTimeString = '', $format = '') {

        if (is_null($dateTimeString) || (trim($dateTimeString) == '')) {
            return '';
        }

        if (is_null($format) || (trim($format) == '')) {
            $format = \DateTime::ISO8601;
        }

        $appTimeZone = config('app.timezone');
        $channelTimeZone = $this->restApiService->getApiTimezone();
        $zoneList = timezone_identifiers_list();
        $cleanZone = (in_array(trim($channelTimeZone), $zoneList)) ? trim($channelTimeZone) : $appTimeZone;

        try {
            $dtObj = new \DateTime($dateTimeString, new \DateTimeZone($appTimeZone));
            $dtObj->setTimezone(new \DateTimeZone($cleanZone));
            return $dtObj->format($format);
        } catch (\Exception $e) {
            return '';
        }

    }

    public function getAvailableStatuses() {
        $statusList = config('fms.order_statuses');
        $statusListClean = [];
        if(!is_null($statusList) && is_array($statusList) && (count($statusList) > 0)) {
            foreach ($statusList as $statusKey => $loopStatus) {
                $statusListClean[$statusKey] = $loopStatus;
            }
        }
        return $statusListClean;
    }

    public function getDeliveryTimeSlots() {
        $statusList = $this->getAvailableStatuses();
        $orders = SaleOrder::whereIn('order_status', array_keys($statusList))
            ->groupBy('delivery_time_slot')
            ->orderBy(DB::raw("STR_TO_DATE(TRIM(SUBSTRING_INDEX(delivery_time_slot, '-', 1)), '%l:%i %p')"), 'asc')
            ->select('delivery_time_slot', DB::raw('count(*) as total_orders'))
            ->get();
        $timeSlotArray = [];
        if ($orders && (count($orders) > 0)) {
            foreach ($orders as $orderEl) {
                if (trim($orderEl->delivery_time_slot) != '') {
                    $timeSlotArray[] = $orderEl->delivery_time_slot;
                }
            }
        }
        return $timeSlotArray;
    }

    /**
     * @throws \Exception
     */
    public function getCollectionVerifiedSaleOrders($verifiedDate = null, $limit = 100) {

        if (is_null($verifiedDate) || (trim($verifiedDate) == '') || ((bool)strtotime(trim($verifiedDate)) === false)) {
            return [];
        }

        $returnData = [];
        $returnDataCount = 0;

        $lastVerifiedDate = date('Y-m-d H:i:s', strtotime($verifiedDate));
        $limitClean = (is_numeric($limit)) ? (int)$limit : 100;

        $channelTimeZone = $this->restApiService->getApiTimezone();
        $newDateTime = new \DateTime($lastVerifiedDate, new \DateTimeZone($channelTimeZone));
        $newDateTime->setTimezone(new \DateTimeZone("UTC"));
        $dateTimeUTC = $newDateTime->format("Y-m-d H:i:s");

        $orderRequest = SaleOrder::select('*');

        $orderRequest->where('env', $this->getApiEnvironment());
        $orderRequest->where('channel', $this->getApiChannel());
        $orderRequest->where('is_amount_verified', 1);
        $orderRequest->where('amount_verified_at', '>', $dateTimeUTC);

        $orderRequest->orderBy('amount_verified_at', 'asc');

        $orderList = $orderRequest->get();
        if ($orderList && (count($orderList) > 0)) {
            foreach($orderList as $orderEl) {

                if ($returnDataCount > $limitClean) {
                    continue;
                }

                $deliveredData = SaleOrderProcessHistory::select('*')
                    ->where('order_id', $orderEl->id)
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED)
                    ->limit(1)->get();

                $canceledData = SaleOrderProcessHistory::select('*')
                    ->where('order_id', $orderEl->id)
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED)
                    ->limit(1)->get();

                $currentDriver = SaleOrderProcessHistory::select('*')
                    ->where('order_id', $orderEl->id)
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY)
                    ->orderBy('done_at', 'desc')
                    ->limit(1)->get();

                $historyObj = null;
                if ($deliveredData && (count($deliveredData) > 0)) {
                    $historyObj = $deliveredData->first();
                } elseif ($canceledData && (count($canceledData) > 0)) {
                    $historyObj = $canceledData->first();
                } elseif ($currentDriver && (count($currentDriver) > 0)) {
                    $historyObj = $currentDriver->first();
                }

                if (!is_null($historyObj)) {

                    $userElQ = User::select('*')
                        ->where('id', $historyObj->done_by)->get();
                    $userEl = ($userElQ) ? $userElQ->first() : $historyObj->actionDoer;

                    $orderEl->paymentData;
                    $orderEl->paidAmountCollections;
                    $saleOrderData = $orderEl->toArray();

                    $paymentMethodString = 'Online';
                    $totalOrderValueOrig = (float)$saleOrderData['order_total'];
                    $totalCanceledValue = (!is_null($saleOrderData['canceled_total'])) ? (float)$saleOrderData['canceled_total'] : 0;
                    $totalOrderValue = $totalOrderValueOrig - $totalCanceledValue;
                    $totalDueValue = (float)$saleOrderData['order_due'];

                    $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
                    if (in_array($saleOrderData['payment_data'][0]['method'], $fixTotalDueArray)) {
                        $paymentMethodString = '';
                        $totalDueValue = $totalOrderValue;
                    }
                    $collectedAmount = $totalOrderValue - $totalDueValue;

                    $amountCollectionData = [];
                    foreach(SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS as $cMethod) {
                        $amountCollectionData[$cMethod] = 0;
                    }

                    if (
                        isset($saleOrderData['paid_amount_collections'])
                        && is_array($saleOrderData['paid_amount_collections'])
                        && (count($saleOrderData['paid_amount_collections']) > 0)
                    ) {
                        foreach ($saleOrderData['paid_amount_collections'] as $paidCollEl) {
                            $amountCollectionData[$paidCollEl['method']] += (float) $paidCollEl['amount'];
                        }
                    }

                    /*foreach ($amountCollectionData as $cMethod => $cAmount) {
                        if ((float)$cAmount > 0) {
                            $returnData[] = [
                                'driverCode' => $userEl->id,
                                'driverName' => $userEl->name,
                                'paymentMode' => ucwords($cMethod),
                                'orderNumber' => $saleOrderData['increment_id'],
                                'collectionAmount' => $cAmount,
                                'collectionCurrency' => $saleOrderData['order_currency'],
                                'collectionDate' => $this->getFormattedTime($historyObj->done_at, 'Y-m-d'),
                                'collectionVerificationDate' => $this->getFormattedTime($saleOrderData['amount_verified_at'], 'Y-m-d H:i:s'),
                            ];
                            $returnDataCount++;
                        }
                    }*/

                    foreach ($amountCollectionData as $cMethod => $cAmount) {
                        if ((float)$cAmount > 0) {
                            $paymentMethodString .= ((trim($paymentMethodString) == '') ? '' : ' and ') . ucwords($cMethod);
                            $collectedAmount += (float) $cAmount;
                        }
                    }

                    $returnData[] = [
                        'driverCode' => $userEl->id,
                        'driverName' => $userEl->name,
                        'paymentMode' => $paymentMethodString,
                        'orderNumber' => $saleOrderData['increment_id'],
                        'collectionAmount' => $collectedAmount,
                        'collectionCurrency' => $saleOrderData['order_currency'],
                        'collectionDate' => $this->getFormattedTime($historyObj->done_at, 'Y-m-d'),
                        'collectionVerificationDate' => $this->getFormattedTime($saleOrderData['amount_verified_at'], 'Y-m-d H:i:s'),
                    ];
                    $returnDataCount++;

                }

            }
        }

        return $returnData;

    }

    public function isValidApiUser($userId = 0) {

        if (is_null($userId) || !is_numeric($userId) || ((int)$userId <= 0)) {
            return [
                'success' => false,
                'message' => 'Invalid User!',
                'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED,
            ];
        }

        $user = User::find($userId);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid User!',
                'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED,
            ];
        }

        $roleMapData = UserRoleMap::firstWhere('user_id', $user->id);
        if (!$roleMapData) {
            return [
                'success' => false,
                'message' => 'The User not assigned to any role!',
                'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED,
            ];
        }

        $mappedRoleId = $roleMapData->role_id;
        $roleData = UserRole::find($mappedRoleId);
        if (!$roleData) {
            return [
                'success' => false,
                'message' => 'The User not assigned to any role!',
                'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED,
            ];
        }

        if (!$roleData->isAdmin()) {
            return [
                'success' => false,
                'message' => 'The User is not a Driver!',
                'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED,
            ];
        }

        return [
            'success' => true,
            'message' => 'Authorized User.',
            'error' => '',
            'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_OK,
        ];

    }

    public function saleOrderSync($orderId = '', $env = '', $apiChannel = '', $userId = 0) {

        if (is_null($orderId) || (trim($orderId) == '') || !is_numeric(trim($orderId)) || ((int) trim($orderId) <= 0)) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        if (is_null($env) || (trim($env) == '')) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        $storeConfig = $this->getStoreConfigs($env, $apiChannel);
        $saleOrderEl = $this->getOrderDetailsById($orderId, $env, $apiChannel);
        if (!is_array($saleOrderEl) || (count($saleOrderEl) == 0)) {
            return [
                'status' => false,
                'message' => 'Could not fetch the data for Sale Order!'
            ];
        }

        $customerResponse = $this->processSaleCustomer($env, $apiChannel, $saleOrderEl);
        if ($customerResponse['status'] === false) {
            return [
                'status' => false,
                'message' => $customerResponse['message']
            ];
        }

        $customerObj = $customerResponse['customerObj'];
        $saleResponse = $this->processSaleOrder($env, $apiChannel, $saleOrderEl, $customerObj);
        if ($saleResponse['status'] === false) {
            return [
                'status' => false,
                'message' => $saleResponse['message']
            ];
        }

        $saleOrderObj = $saleResponse['saleOrderObj'];
        $orderAlreadyCreated = $saleResponse['orderAlreadyCreated'];

        $formerOrderItems = [];
        if ($orderAlreadyCreated) {
            $saleOrderObj->orderItems;
            $saleOrderFormerData = $saleOrderObj->toArray();
            $formerOrderItems = $saleOrderFormerData['order_items'];
        }

        $orderSyncErrors = [];
        if(!is_array($saleOrderEl['items']) || (count($saleOrderEl['items']) == 0)) {
            $orderSyncErrors[] = 'There is no Order Item for Sale Order.';
        } else {

            $itemsToDelete = [];
            if (count($formerOrderItems) > 0) {
                foreach ($formerOrderItems as $formerItem) {
                    $deletedFromServer = true;
                    foreach ($saleOrderEl['items'] as $orderItemEl) {
                        if ($formerItem['item_id'] === $orderItemEl['item_id']) {
                            $deletedFromServer = false;
                        }
                    }
                    if ($deletedFromServer) {
                        $itemsToDelete[] = $formerItem['id'];
                    }
                }
            }

            foreach ($saleOrderEl['items'] as $orderItemEl) {

                $commonOrderItem = [];
                if (count($formerOrderItems) > 0) {
                    foreach ($formerOrderItems as $formerItem) {
                        if ($formerItem['item_id'] === $orderItemEl['item_id']) {
                            $commonOrderItem = $formerItem;
                        }
                    }
                }

                $orderItemResponse = $this->processSaleOrderItem($orderItemEl, $saleOrderObj, $commonOrderItem, $storeConfig);
                if(!$orderItemResponse['status']) {
                    $orderSyncErrors[] = 'Could not process Order Item #' . $orderItemEl['item_id'] . ' (' . $orderItemResponse['message'] . ').';
                }
            }

            if (count($itemsToDelete) > 0) {
                SaleOrderItem::destroy($itemsToDelete);
            }

        }

        $billingResponse = $this->processSaleOrderBillingAddress($saleOrderEl, $saleOrderObj);
        if (!$billingResponse['status']) {
            $orderSyncErrors[] = 'Could not process Billing Address data (' . $billingResponse['message'] . ').';
        }

        $shippingResponse = $this->processSaleOrderShippingAddress($saleOrderEl, $saleOrderObj);
        if (!$shippingResponse['status']) {
            $orderSyncErrors[] = 'Could not process Shipping Address data (' . $shippingResponse['message'] . ').';
        }

        $paymentResponse = $this->processSaleOrderPayments($saleOrderEl, $saleOrderObj);
        if (!$paymentResponse['status']) {
            $orderSyncErrors[] = 'Could not process Payment data (' . $paymentResponse['message'] . ').';
        }

        if(!is_array($saleOrderEl['status_histories']) || (count($saleOrderEl['status_histories']) == 0)) {
            $orderSyncErrors[] = 'There is no Status History data for Sale Order.';
        } else {
            foreach ($saleOrderEl['status_histories'] as $historyEl) {
                $historyResponse = $this->processSaleOrderStatusHistory($historyEl, $saleOrderObj);
                if(!$historyResponse['status']) {
                    $orderSyncErrors[] = 'Could not process Status History data (' . $historyResponse['message'] . ').';
                }
            }
        }

        $processResponse = $this->recordOrderStatusProcess($saleOrderObj, $orderAlreadyCreated, $userId);
        if (!$processResponse['status']) {
            $orderSyncErrors[] = 'Could not record the processing of Sale Order (' . $processResponse['message'] . ').';
        }

        return [
            'status' => true,
            'message' => 'The Sale Order is synced successfully' . ((count($orderSyncErrors) > 0) ? ' with some errors.' : '.'),
            'errors' => $orderSyncErrors
        ];

    }

    private function getStoreConfigs($env = '', $apiChannel = '') {

        if (is_null($env) || (trim($env) == '')) {
            return [];
        }

        if (is_null($apiChannel) || (trim($apiChannel) == '')) {
            return [];
        }

        $apiService = new RestApiService();
        $apiService->setApiEnvironment($env);
        $apiService->setApiChannel($apiChannel);

        $uri = $apiService->getRestApiUrl() . 'store/storeConfigs';
        $apiResult = $apiService->processGetApi($uri);
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

    private function getOrderDetailsById($orderId = '', $env = '', $apiChannel = '') {

        if (is_null($orderId) || (trim($orderId) == '') || !is_numeric(trim($orderId)) || ((int) trim($orderId) <= 0)) {
            return [];
        }

        if (is_null($env) || (trim($env) == '')) {
            return [];
        }

        if (is_null($apiChannel) || (trim($apiChannel) == '')) {
            return [];
        }

        $apiService = new RestApiService();
        $apiService->setApiEnvironment($env);
        $apiService->setApiChannel($apiChannel);

        $uri = $apiService->getRestApiUrl() . 'orders/' . $orderId;
        $apiResult = $apiService->processGetApi($uri);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

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
            }

            $saleOrderObj = SaleOrder::updateOrCreate([
                'env' => $currentApiEnv,
                'channel' => $currentApiChannel,
                'order_id' => $saleOrderEl['entity_id'],
                'increment_id' => $saleOrderEl['increment_id'],
            ], [
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
                'order_due' => (!array_key_exists('total_canceled', $saleOrderEl)) ? $saleOrderEl['total_due'] : 0,
                'canceled_total' => (isset($saleOrderEl['total_canceled'])) ? $saleOrderEl['total_canceled'] : null,
                'invoiced_total' => (isset($saleOrderEl['total_invoiced'])) ? $saleOrderEl['total_invoiced'] : null,
                'order_state' => $saleOrderEl['state'],
                'order_status' => $saleOrderEl['status'],
                'order_status_label' => (isset($saleOrderEl['extension_attributes']['order_status_label'])) ? $saleOrderEl['extension_attributes']['order_status_label'] : null,
                'to_be_synced' => 0,
                'is_synced' => 0,
                'is_active' => 1,
            ]);

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

    private function processSaleOrderItem($orderItemEl = [], SaleOrder $saleOrderObj = null, $currentOrderItem = [], $storeConfig = []) {

        try {

            $mediaUrl = $storeConfig['base_media_url'];
            $productImageUrlSegment = 'catalog/product';
            $productImageUrl = $mediaUrl . $productImageUrlSegment;

            $storeAvailability = null;
            $storeAvailabilityCheckedAt = null;
            if (
                (count($currentOrderItem) > 0)
                && ($currentOrderItem['qty_ordered'] == $orderItemEl['qty_ordered'])
                && ($currentOrderItem['qty_canceled'] == $orderItemEl['qty_canceled'])
                && ($currentOrderItem['qty_shipped'] == $orderItemEl['qty_shipped'])
                && ($currentOrderItem['qty_invoiced'] == $orderItemEl['qty_invoiced'])
                && ($currentOrderItem['qty_refunded'] == $orderItemEl['qty_refunded'])
            ) {
                $storeAvailability = $currentOrderItem['store_availability'];
                $storeAvailabilityCheckedAt = $currentOrderItem['availability_checked_at'];
            }

            $itemExtAttr = $orderItemEl['extension_attributes'];

            $saleOrderItemObj = SaleOrderItem::updateOrInsert([
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
                'store_availability' => $storeAvailability,
                'availability_checked_at' => $storeAvailabilityCheckedAt,
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

    private function processSaleOrderBillingAddress($saleOrderEl = [], SaleOrder $saleOrderObj = null) {

        try {

            $billingAddressObj = SaleOrderAddress::updateOrInsert([
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

    private function processSaleOrderShippingAddress($saleOrderEl = [], SaleOrder $saleOrderObj = null) {

        try {

            $orderShippingAddress = $saleOrderEl['extension_attributes']['shipping_assignments'][0]['shipping']['address'];

            $shippingAddressObj = SaleOrderAddress::updateOrInsert([
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

    private function processSaleOrderPayments($saleOrderEl = [], SaleOrder $saleOrderObj = null) {

        try {

            $paymentObj = SaleOrderPayment::updateOrInsert([
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

    private function processSaleOrderStatusHistory($historyEl = [], SaleOrder $saleOrderObj = null) {

        try {

            $statusHistoryObj = SaleOrderStatusHistory::updateOrInsert([
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

    private function recordOrderStatusProcess(SaleOrder $saleOrderObj = null, $orderAlreadyCreated = true, $userId = 0) {

        try {

            $givenAction = ($orderAlreadyCreated)
                ? SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_REIMPORT
                : SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_IMPORT;
            $saleOrderProcessHistoryObj = (new SaleOrderProcessHistory())->create([
                'order_id' => $saleOrderObj->id,
                'action' => $givenAction,
                'status' => 1,
                'comments' => 'The Sale Order Id #' . $saleOrderObj->order_id . ' is ' . (($orderAlreadyCreated) ? 're-imported' : 'imported') . '.',
                'extra_info' => null,
                'done_by' => ($userId == 0) ? null : $userId,
                'done_at' => date('Y-m-d H:i:s'),
            ]);

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
