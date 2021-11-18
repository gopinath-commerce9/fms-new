<?php


namespace Modules\Sales\Entities;

use Modules\Base\Entities\RestApiService;
use DB;
use \Exception;
use Modules\Base\Entities\BaseServiceHelper;
use App\Models\User;

class SalesServiceHelper
{

    private $restApiService = null;
    private $baseService = null;

    public function __construct($channel = '')
    {
        $this->baseService = new BaseServiceHelper();
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
            ->select('delivery_time_slot', DB::raw('count(*) as total_orders'))
            ->get();
        $timeSlotArray = [];
        if ($orders && (count($orders) > 0)) {
            foreach ($orders as $orderEl) {
                $timeSlotArray[] = $orderEl->delivery_time_slot;
            }
        }
        return $timeSlotArray;
    }

    public function getSaleOrders($region = '', $apiChannel = '', $status = '', $deliveryDate = '', $timeSlot = '') {

        $orderRequest = SaleOrder::select('*');

        $emirates = config('fms.emirates');
        if (!is_null($region) && (trim($region) != '')) {
            $orderRequest->where('region_code', trim($region));
        } else {
            $orderRequest->whereIn('region_code', array_keys($emirates));
        }

        $availableApiChannels = $this->getAllAvailableChannels();
        if (!is_null($apiChannel) && (trim($apiChannel) != '')) {
            $orderRequest->where('channel', trim($apiChannel));
        } else {
            $orderRequest->whereIn('channel', array_keys($availableApiChannels));
        }

        $availableStatuses = $this->getAvailableStatuses();
        if (!is_null($status) && (trim($status) != '')) {
            $orderRequest->where('order_status', trim($status));
        } else {
            $orderRequest->whereIn('order_status', array_keys($availableStatuses));
        }

        if (!is_null($deliveryDate) && (trim($deliveryDate) != '')) {
            $orderRequest->where('delivery_date', date('Y-m-d', strtotime(trim($deliveryDate))));
        }

        if (!is_null($timeSlot) && (trim($timeSlot) != '')) {
            $orderRequest->where('delivery_time_slot', trim($timeSlot));
        }

        $orderRequest->orderBy('delivery_date', 'asc');

        return $orderRequest->get();

    }

    public function getAvailablePosOrderSources() {
        $returnData = [];
        $orderSources = config('fms.pos_system.order_sources');
        foreach ($orderSources as $osKey => $orderSource) {
            $returnData[$osKey] = [
                'code' => $orderSource['code'],
                'source' => $orderSource['source'],
                'charge' => $orderSource['charge']
            ];
        }
        return $returnData;
    }

    public function getAvailablePosPaymentMethods() {
        $returnData = [];
        $paymentMethods = config('fms.pos_system.payment_methods');
        foreach ($paymentMethods as $pmKey => $paymentMethod) {
            $returnData[$pmKey] = [
                'method' => $paymentMethod['method'],
                'title' => $paymentMethod['title']
            ];
        }
        return $returnData;
    }

    public function getAvailableRegionsList($countryId = '') {

        if (is_null($countryId) || (is_string($countryId) && (trim($countryId) == ''))) {
            $countryId = $this->restApiService->getApiDefaultCountry();
        }

        $uri = $this->restApiService->getRestApiUrl() . 'directory/countries/' . $countryId;
        $apiResult = $this->restApiService->processGetApi($uri, [], [], true, true);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function getAvailableCityList($countryId = '') {

        if (is_null($countryId) || (is_string($countryId) && (trim($countryId) == ''))) {
            $countryId = $this->restApiService->getApiDefaultCountry();
        }

        $uri = $this->restApiService->getRestApiUrl() . 'directory/areas/' . $countryId;
        $apiResult = $this->restApiService->processGetApi($uri, [], [], true, true);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function getProductData($productBarCode = '') {

        if (is_null($productBarCode) || (trim($productBarCode) == '')) {
            return [];
        }

        $uri = $this->restApiService->getRestApiUrl() . 'products';
        $qParams = [
            'searchCriteria[filter_groups][0][filters][0][field]' => 'barcode',
            'searchCriteria[filter_groups][0][filters][0][condition_type]' => 'eq',
            'searchCriteria[filter_groups][0][filters][0][value]' => $productBarCode,
        ];
        $apiResult = $this->restApiService->processGetApi($uri, $qParams);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function placePosOrder($orderData = [], $channelId = '', $placingUser = 0) {

        if (is_null($orderData) || !is_array($orderData) || (count($orderData) == 0)) {
            return [
                'success' => false,
                'message' => 'Order Data is empty'
            ];
        }

        $uri = $this->restApiService->getRestApiUrl() . 'sales/createorder';
        $headers = [];
        if (!is_null($channelId) && is_string($channelId) && (trim($channelId) != '')) {
            $headers['Channel-Id'] = trim($channelId);
        }
        $apiResult = $this->restApiService->processPostApi($uri, $orderData, $headers);
        if (!$apiResult['status']) {
            return [
                'success' => false,
                'message' => $apiResult['message'],
            ];
        }

        return [
            'success' => true,
            'response' => $apiResult['response'],
        ];

    }

    public function saveOrderToDatabase($channel = '', $orderId = '', $placingUser = 0, $mode = 'pos') {

        $processUser = null;
        if (!is_null($placingUser) && is_numeric($placingUser) && ((int)$placingUser > 0)) {
            $targetUser = User::find((int)$processUser);
            if ($targetUser) {
                $processUser = $targetUser;
            }
        }
        $availableModes = ['pos', 'sync'];
        $modeClean = (!is_null($mode) && is_string($mode) && in_array(trim($mode), $availableModes))
            ? trim($mode) : 'pos';

        $this->restApiService = new RestApiService();
        $this->setApiChannel($channel);
        $this->restApiChannel = $this->restApiService->getCurrentApiChannel();

        $uri = $this->restApiService->getRestApiUrl() . 'orders/' . $orderId;
        $apiResult = $this->restApiService->processGetApi($uri);
        if (!$apiResult['status']) {
            return [
                'success' => false,
                'message' => $apiResult['message'],
            ];
        }

        $currentApiEnv = $this->restApiService->getApiEnvironment();
        $currentApiChannel = $this->restApiService->getCurrentApiChannel();

        $saleOrderEl = $apiResult['response'];
        if (!is_array($saleOrderEl) || (count($saleOrderEl) == 0)) {
            return [
                'success' => false,
                'message' => 'Could not fetch the data for Sale Order #' . $orderId . '.'
            ];
        }

        if(!is_array($saleOrderEl['items']) || (count($saleOrderEl['items']) == 0)) {
            return [
                'success' => false,
                'message' => 'There is no Order Item for Sale Order #' . $orderId . '.'
            ];
        }

        $customerResponse = $this->processNewSaleCustomer($currentApiEnv, $currentApiChannel, $saleOrderEl);
        if (!$customerResponse['status']) {
            return [
                'success' => false,
                'message' => $customerResponse['message'],
            ];
        }

        $customerObj = $customerResponse['customerObj'];
        $saleResponse = $this->processNewSaleOrder($currentApiEnv, $currentApiChannel, $saleOrderEl, $customerObj);
        if (!$saleResponse['status']) {
            return [
                'success' => false,
                'message' => $saleResponse['message'],
            ];
        }

        $saleOrderObj = $saleResponse['saleOrderObj'];
        $orderAlreadyCreated = $saleResponse['orderAlreadyCreated'];
        $orderSaveErrors = [];

        foreach ($saleOrderEl['items'] as $orderItemEl) {
            $orderItemResponse = $this->processNewSaleOrderItem($orderItemEl, $saleOrderObj);
            if(!$orderItemResponse['status']) {
                $orderSaveErrors[] = 'Could not process Order Item #' . $orderItemEl['item_id'] . ' for Sale Order #' . $orderId . '. '  . $orderItemResponse['message'];
            }
        }

        $billingResponse = $this->processNewSaleOrderBillingAddress($saleOrderEl, $saleOrderObj);
        if (!$billingResponse['status']) {
            $orderSaveErrors[] =  'Could not process Billing Address data for Sale Order #' . $orderId . '. ' . $billingResponse['message'];
        }

        $shippingResponse = $this->processNewSaleOrderShippingAddress($saleOrderEl, $saleOrderObj);
        if (!$shippingResponse['status']) {
            $orderSaveErrors[] =  'Could not process Shipping Address data for Sale Order #' . $orderId . '. ' . $shippingResponse['message'];
        }

        $paymentResponse = $this->processNewSaleOrderPayments($saleOrderEl, $saleOrderObj);
        if (!$paymentResponse['status']) {
            $orderSaveErrors[] =  'Could not process Payment data for Sale Order #' . $orderId . '. ' . $paymentResponse['message'];
        }

        if(!is_array($saleOrderEl['status_histories']) || (count($saleOrderEl['status_histories']) == 0)) {
            $orderSaveErrors[] =  'There is no Status History data for Sale Order #' . $orderId . '.';
        } else {
            foreach ($saleOrderEl['status_histories'] as $historyEl) {
                $historyResponse = $this->processNewSaleOrderStatusHistory($historyEl, $saleOrderObj);
                if(!$historyResponse['status']) {
                    $orderSaveErrors[] =  'Could not process Status History #' . $historyEl['entity_id'] . ' for Sale Order #' . $orderId . '. '  . $historyResponse['message'];
                }
            }
        }

        $processResponse = $this->recordOrderStatusProcess($saleOrderObj, $orderAlreadyCreated, $processUser, $modeClean);
        if (!$processResponse['status']) {
            $orderSaveErrors[] =  'Could not record the processing of Sale Order #' . $orderId . '. ' . $processResponse['message'];
        }

        return [
            'success' => true,
            'saleOrder' => $saleOrderObj,
            'errors' => $orderSaveErrors
        ];

    }

    private function processNewSaleCustomer($currentApiEnv = '', $currentApiChannel = '', $saleOrderEl = []) {

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

    private function processNewSaleOrder($currentApiEnv = '', $currentApiChannel = '', $saleOrderEl = [], SaleCustomer $customerObj = null) {

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
                    'zone_id' => $saleOrderEl['extension_attributes']['zone_id'],
                    'store' => $saleOrderEl['store_name'],
                    'delivery_date' => $saleOrderEl['extension_attributes']['delivery_date'],
                    'delivery_time_slot' => $saleOrderEl['extension_attributes']['delivery_time_slot'],
                    'total_item_count' => $saleOrderEl['total_item_count'],
                    'total_qty_ordered' => $saleOrderEl['total_qty_ordered'],
                    'order_weight' => $saleOrderEl['weight'],
                    'box_count' => (isset($saleOrderEl['extension_attributes']['box_count'])) ? $saleOrderEl['extension_attributes']['box_count'] : null,
                    'not_require_pack' => $saleOrderEl['extension_attributes']['not_require_pack'],
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

    private function processNewSaleOrderItem($orderItemEl = [], SaleOrder $saleOrderObj = null) {

        try {

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
                'item_barcode' => $itemExtAttr['barcode'],
                'item_name' => $itemExtAttr['product_en_name'],
                'item_info' => $itemExtAttr['pack_weight_info'],
                'item_image' => $itemExtAttr['product_image'],
                'actual_qty' => $itemExtAttr['actual_qty'],
                'qty_ordered' => $orderItemEl['qty_ordered'],
                'qty_shipped' => $orderItemEl['qty_shipped'],
                'qty_invoiced' => $orderItemEl['qty_invoiced'],
                'qty_canceled' => $orderItemEl['qty_canceled'],
                'qty_returned' => $orderItemEl['qty_returned'],
                'qty_refunded' => $orderItemEl['qty_refunded'],
                'selling_unit' => $itemExtAttr['selling_format'],
                'selling_unit_label' => $itemExtAttr['selling_format_label'],
                'billing_period' => $itemExtAttr['billing_period'],
                'delivery_day' => $itemExtAttr['delivery_day'],
                'scale_number' => ((array_key_exists('scale_number', $itemExtAttr)) ? $itemExtAttr['scale_number'] : null),
                'country_label' => $itemExtAttr['country_label'],
                'item_weight' => $orderItemEl['row_weight'],
                'price' => $orderItemEl['price'],
                'row_total' => $orderItemEl['row_total'],
                'tax_amount' => $orderItemEl['tax_amount'],
                'tax_percent' => $orderItemEl['tax_percent'],
                'discount_amount' => $orderItemEl['discount_amount'],
                'discount_percent' => $orderItemEl['discount_percent'],
                'row_grand_total' => $orderItemEl['row_total_incl_tax'],
                'vendor_id' => $itemExtAttr['vendor_id'],
                'vendor_availability' => $itemExtAttr['vendor_availability'],
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

    private function processNewSaleOrderBillingAddress($saleOrderEl = [], SaleOrder $saleOrderObj = null) {

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

    private function processNewSaleOrderShippingAddress($saleOrderEl = [], SaleOrder $saleOrderObj = null) {

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

    private function processNewSaleOrderPayments($saleOrderEl = [], SaleOrder $saleOrderObj = null) {

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

    private function processNewSaleOrderStatusHistory($historyEl = [], SaleOrder $saleOrderObj = null) {

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

    private function recordOrderStatusProcess(SaleOrder $saleOrderObj = null, $orderAlreadyCreated = true, User $processUser = null, $mode = 'pos') {

        try {

            if (!$orderAlreadyCreated || ($orderAlreadyCreated && $processUser)) {
                $availableModes = ['pos', 'sync'];
                $modeClean = (!is_null($mode) && is_string($mode) && in_array(trim($mode), $availableModes))
                    ? trim($mode) : 'pos';
                $givenAction = SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CREATED;
                if ($modeClean == 'pos') {
                    $givenAction = SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CREATED;
                } elseif ($modeClean == 'sync') {
                    $givenAction = ($orderAlreadyCreated)
                        ? SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_REIMPORT
                        : SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_IMPORT;
                }
                $saleOrderProcessHistoryObj = (new SaleOrderProcessHistory())->create([
                    'order_id' => $saleOrderObj->id,
                    'action' => $givenAction,
                    'status' => 1,
                    'comments' => 'The Sale Order Id #' . $saleOrderObj->order_id . ' is ' . (($orderAlreadyCreated) ? 're-imported' : 'imported') . '.',
                    'extra_info' => null,
                    'done_by' => ($processUser) ? $processUser->id : null,
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

    public function getFileUrl($path = '') {
        return $this->baseService->getFileUrl($path);
    }

    public function getUserImageUrl($path = '') {
        return $this->baseService->getFileUrl('media/images/users/' . $path);
    }

}
