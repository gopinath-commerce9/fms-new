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
     * @param string $env
     * @param string $channel
     *
     * @return string
     */
    public function getFormattedTime($dateTimeString = '', $format = '', $env = '', $channel = '') {

        if (is_null($dateTimeString) || (trim($dateTimeString) == '')) {
            return '';
        }

        if (is_null($format) || (trim($format) == '')) {
            $format = \DateTime::ISO8601;
        }

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $appTimeZone = config('app.timezone');
        $channelTimeZone = $apiService->getApiTimezone();
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

    public function getPicklistStatuses() {
        $statusList = config('fms.order_statuses');
        $allowedStatusList = config('fms.picklist_statuses');
        $statusListClean = [];
        if(!is_null($allowedStatusList) && is_array($allowedStatusList) && (count($allowedStatusList) > 0)) {
            foreach ($allowedStatusList as $loopStatus) {
                $statusKey = strtolower(str_replace(' ', '_', trim($loopStatus)));
                $statusValue = ucwords(str_replace('_', ' ', trim($statusKey)));
                $statusListClean[$statusKey] = (array_key_exists($statusKey, $statusList) ? $statusList[$statusKey] : $statusValue);
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

    public function getDeliveryZones($region = [], $regionwise = false) {

        $statusList = $this->getAvailableStatuses();
        $orderRequest = SaleOrder::whereIn('order_status', array_keys($statusList))
            ->whereNotNull('zone_id')
            ->groupBy('region_id', 'zone_id')
            ->orderBy('region_id', 'asc')
            ->orderBy('zone_id', 'asc')
            ->select('region_id', 'zone_id', DB::raw('count(*) as total_orders'));

        $emirates = $this->getAvailableRegionsList();
        $regionKeys = array_keys($emirates);
        if (
            !is_null($region)
            && is_array($region)
            && (count($region) > 0)
            && (array_intersect($region, $regionKeys) == $region)
        ) {
            $orderRequest->whereIn('region_id', $region);
        } else {
            $orderRequest->whereIn('region_id', $regionKeys);
        }

        $orders = $orderRequest->get();

        $zoneArray = [];

        $zoneArrayAssoc = [];
        if ($orders && (count($orders) > 0)) {
            foreach ($orders as $orderEl) {
                if ((trim($orderEl->region_id) != '') && (trim($orderEl->zone_id) != '')) {
                    $zoneArrayAssoc[$orderEl->region_id][$orderEl->zone_id] = $orderEl->zone_id;
                }
            }
        }

        if (is_bool($regionwise) && ($regionwise === true)) {
            $zoneArray = $zoneArrayAssoc;
            return $zoneArray;
        }

        foreach ($zoneArrayAssoc as $regionKey => $zoneData) {
            foreach ($zoneData as $zoneKey => $zoneEl) {
                $zoneArray[$zoneEl] = $zoneEl;
            }
        }

        return $zoneArray;

    }

    public function getProductCategories() {
        $categories = ProductCategory::select('category_id', 'category_name', DB::raw('count(*) as total_categories'))
            ->groupBy('category_id')
            ->get();
        $categoryArray = [
            '0' => 'UnCat'
        ];
        if ($categories && (count($categories) > 0)) {
            foreach ($categories as $categoryEl) {
                $categoryArray[$categoryEl->category_id] = $categoryEl->category_name;
            }
        }
        return $categoryArray;
    }

    public function getAvailableRegionsList($countryId = '', $env = '', $channel = '', $forceFetch = false, $fullData = false) {

        $fetchClean = (!is_null($forceFetch) && is_bool($forceFetch)) ? $forceFetch : false;
        $fullDataClean = (!is_null($fullData) && is_bool($fullData)) ? $fullData : false;

        $baseServiceHelper = new BaseServiceHelper();
        $regionList = $baseServiceHelper->getRegionList($env, $channel, $fetchClean);

        if (count($regionList) == 0) {
            return [];
        }

        $returnData = [];
        foreach ($regionList as $regionEl) {
            $returnData[$regionEl['region_id']] = ($fullDataClean) ? $regionEl : $regionEl['name'];
        }

        return $returnData;

    }

    public function getSaleOrders($region = '', $apiChannel = '', $status = '', $deliveryDate = '', $timeSlot = '') {

        $orderRequest = SaleOrder::select('*');

        $emirates = $this->getAvailableRegionsList();
        if (!is_null($region) && (trim($region) != '')) {
            $orderRequest->where('region_id', trim($region));
        } else {
            $orderRequest->whereIn('region_id', array_keys($emirates));
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

        $givenTimeSlots = $this->getDeliveryTimeSlots();
        if (!is_null($timeSlot) && (trim($timeSlot) != '') && (count($givenTimeSlots) > 0) && in_array(trim($timeSlot), $givenTimeSlots)) {
            $orderRequest->where('delivery_time_slot', trim($timeSlot));
        } elseif (count($givenTimeSlots) > 0) {
            $orderRequest->whereIn('delivery_time_slot', $givenTimeSlots);
        }

        $orderRequest->orderBy('delivery_date', 'asc');
        $orderRequest->orderBy(DB::raw("STR_TO_DATE(TRIM(SUBSTRING_INDEX(delivery_time_slot, '-', 1)), '%l:%i %p')"), 'asc');
        $orderRequest->orderBy('order_id', 'asc');

        return $orderRequest->get();

    }

    public function getAvailablePosOrderSources() {
        $returnData = [];
        $orderSources = config('fms.pos_system.order_sources');
        foreach ($orderSources as $osKey => $orderSource) {
            $returnData[$osKey] = [
                'code' => $orderSource['code'],
                'source' => $orderSource['source'],
                'channelId' => $orderSource['channelId'],
                'charge' => $orderSource['charge'],
                'email' => $orderSource['email'],
                'contact' => $orderSource['contact']
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

    public function getAvailableCityList($countryId = '', $env = '', $channel = '') {

        if (is_null($countryId) || (is_string($countryId) && (trim($countryId) == ''))) {
            $countryId = $this->restApiService->getApiDefaultCountry();
        }

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'directory/areas/' . $countryId;
        $apiResult = $apiService->processGetApi($uri, [], [], true, true);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function getProductData($productBarCode = '', $env = '', $channel = '') {

        if (is_null($productBarCode) || (trim($productBarCode) == '')) {
            return [];
        }

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'products';
        $qParams = [
            'searchCriteria[filter_groups][0][filters][0][field]' => 'barcode',
            'searchCriteria[filter_groups][0][filters][0][condition_type]' => 'eq',
            'searchCriteria[filter_groups][0][filters][0][value]' => $productBarCode,
        ];
        $apiResult = $apiService->processGetApi($uri, $qParams);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function getProductDataBySku($productSku = '', $env = '', $channel = '') {

        if (is_null($productSku) || (trim($productSku) == '')) {
            return [];
        }

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'products/' . trim($productSku);
        $apiResult = $apiService->processGetApi($uri);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function placePosOrder($orderData = [], $channelId = '', $placingUser = 0, $env = '', $channel = '') {

        if (is_null($orderData) || !is_array($orderData) || (count($orderData) == 0)) {
            return [
                'success' => false,
                'message' => 'Order Data is empty'
            ];
        }

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'sales/createorder';
        $headers = [];
        if (!is_null($channelId) && is_string($channelId) && (trim($channelId) != '')) {
            $headers['Channel-Id'] = trim($channelId);
        }
        $apiResult = $apiService->processPostApi($uri, $orderData, $headers);
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

        $storeConfig = $this->getStoreConfigs();
        $currentApiEnv = $this->restApiService->getApiEnvironment();
        $currentApiChannel = $this->restApiService->getCurrentApiChannel();

        $saleOrderEl = $this->getOrderDetailsById($orderId);
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
            $orderItemResponse = $this->processNewSaleOrderItem($orderItemEl, $saleOrderObj, $storeConfig);
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
     * Fetch the Order details from the API Channel.
     *
     * @param string $orderId
     *
     * @return array
     */
    private function getOrderDetailsById($orderId = '') {

        /*$uri = $this->restApiService->getRestApiUrl() . 'orders/' . $orderId;
        $apiResult = $this->restApiService->processGetApi($uri);

        return ($apiResult['status']) ? $apiResult['response'] : [];*/

        $uri = $this->restApiService->getRestApiUrl() . 'orders';
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
        ) ? $apiResult['response']['items'][0] : [];

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
                    'zone_id' => ((array_key_exists('zone', $saleOrderEl['extension_attributes'])) ? $saleOrderEl['extension_attributes']['zone'] : null),
                    'store' => $saleOrderEl['store_name'],
                    'delivery_date' => ((array_key_exists('order_delivery_date', $saleOrderEl['extension_attributes'])) ? $saleOrderEl['extension_attributes']['order_delivery_date'] : null),
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

    private function processNewSaleOrderItem($orderItemEl = [], SaleOrder $saleOrderObj = null, $storeConfig = []) {

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
                'latitude' => ((array_key_exists('latitude', $saleOrderEl['billing_address'])) ? $saleOrderEl['billing_address']['latitude'] : null),
                'longitude' => ((array_key_exists('longitude', $saleOrderEl['billing_address'])) ? $saleOrderEl['billing_address']['longitude'] : null),
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
                'latitude' => ((array_key_exists('latitude', $orderShippingAddress)) ? $orderShippingAddress['latitude'] : null),
                'longitude' => ((array_key_exists('longitude', $orderShippingAddress)) ? $orderShippingAddress['longitude'] : null),
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
                'status' => array_key_exists('status', $historyEl) ? $historyEl['status'] : null,
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

    public function getOutOfStockItems($dayInterval = 3, $env = '', $channel = '') {

        $intervalClean =  (is_null($dayInterval) || !is_numeric($dayInterval) || ((int) trim($dayInterval) < 0))
            ? (int)trim($dayInterval) : 3;

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'getoutofstockitems/' . $intervalClean;
        $apiResult = $apiService->processGetApi($uri);

        return $apiResult;

    }

    public function getStockItemData($productSku = '', $env = '', $channel = '') {

        if (is_null($productSku) || (trim($productSku) == '')) {
            return [ 'status' => false, 'message' => 'The product SKU should not be empty!' ];
        }

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'stockItems/' . trim($productSku);
        $apiResult = $apiService->processGetApi($uri);

        return $apiResult;

    }

    public function setProductOutOfStock($productSku = '', $itemId = '', $env = '', $channel = '') {

        if (is_null($productSku) || (trim($productSku) == '')) {
            return [];
        }

        if (is_null($itemId) || (trim($itemId) == '')) {
            return [];
        }

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'products/' . trim($productSku) . '/stockItems/' . trim($itemId);
        $params = [
            'stockItem' => [
                'is_in_stock' => false
            ]
        ];
        $apiResult = $apiService->processPutApi($uri, $params);

        return $apiResult;

    }

    public function getSaleOrderItemsReport($region = '', $apiChannel = '', $status = [], $startDate = '', $endDate = '', $timeSlot = '') {

        $orderRequest = SaleOrder::select('sale_orders.*', 'sale_order_items.product_id', 'sale_order_items.item_sku', 'sale_order_items.item_name', DB::raw('SUM(sale_order_items.qty_ordered) as total_qty'), DB::raw('SUM(sale_order_items.qty_returned) as total_return_qty'));

        $emirates = $this->getAvailableRegionsList();
        if (!is_null($region) && (trim($region) != '')) {
            $orderRequest->where('region_id', trim($region));
        } else {
            $orderRequest->whereIn('region_id', array_keys($emirates));
        }

        $availableApiChannels = $this->getAllAvailableChannels();
        if (!is_null($apiChannel) && (trim($apiChannel) != '')) {
            $orderRequest->where('channel', trim($apiChannel));
        } else {
            $orderRequest->whereIn('channel', array_keys($availableApiChannels));
        }

        $availableStatuses = $this->getAvailableStatuses();
        $statusKeys = array_keys($availableStatuses);
        if (
            !is_null($status)
            && is_array($status)
            && (count($status) > 0)
            && (array_intersect($status, $statusKeys) == $status)
        ) {
            $orderRequest->whereIn('order_status', $status);
        } else {
            $orderRequest->whereIn('order_status', $statusKeys);
        }

        $startDateClean = (!is_null($startDate) && (trim($startDate) != '')) ? date('Y-m-d', strtotime(trim($startDate))) : null;
        $endDateClean = (!is_null($endDate) && (trim($endDate) != '')) ? date('Y-m-d', strtotime(trim($endDate))) : null;
        if (!is_null($startDateClean) && !is_null($endDateClean)) {
            $fromDate = '';
            $toDate = '';
            if ($endDateClean > $startDateClean) {
                $fromDate = $startDateClean;
                $toDate = $endDateClean;
            } else {
                $fromDate = $endDateClean;
                $toDate = $startDateClean;
            }
            $orderRequest->whereBetween('delivery_date', [$fromDate, $toDate]);
        }

        if (!is_null($timeSlot) && (trim($timeSlot) != '')) {
            $orderRequest->where('delivery_time_slot', trim($timeSlot));
        }

        $orderItems = $orderRequest->join('sale_order_items', 'sale_orders.order_id', '=', 'sale_order_items.sale_order_id')
            ->groupBy('sale_order_items.item_sku')
            ->orderBy('sale_order_items.product_id', 'asc')
            ->get();

        if (!$orderItems) {
            return [];
        }

        $queryResultArray = $orderItems->toArray();
        $finalArray = [];
        foreach ($queryResultArray as $queryEl) {
            $tempArray = [
                'item_sku' => $queryEl['item_sku'],
                'item_name' => $queryEl['item_name'],
                'total_qty' => $queryEl['total_qty'],
                'total_return_qty' => $queryEl['total_return_qty'],
                'supplier_name' => '',
                'item_type' => ''
            ];
            $productData = $this->getProductDataBySku($queryEl['item_sku'], $queryEl['env'], $queryEl['channel']);
            $searchAttributes = [
                'supplier_name',
                'item_type',
            ];
            if (!is_null($productData) && is_array($productData) && (count($productData) > 0)) {
                if (array_key_exists('custom_attributes', $productData)) {
                    $customAttr = $productData['custom_attributes'];
                    if (is_array($customAttr) && (count($customAttr) > 0)) {
                        foreach ($customAttr as $customAttrEl) {
                            if (array_key_exists('attribute_code', $customAttrEl) && array_key_exists('value', $customAttrEl)) {
                                if (in_array($customAttrEl['attribute_code'], $searchAttributes)) {
                                    $tempArray[$customAttrEl['attribute_code']] = $customAttrEl['value'];
                                }
                            }
                        }
                    }
                }
            }
            $finalArray[] = $tempArray;
        }

        return $finalArray;

    }

    public function getSaleOrderPickList($region = [], $apiChannel = '', $status = [], $startDate = '', $endDate = '', $timeSlot = '') {

        $orderRequest = SaleOrder::select('*');

        $emirates = $this->getAvailableRegionsList();
        $regionKeys = array_keys($emirates);
        if (
            !is_null($region)
            && is_array($region)
            && (count($region) > 0)
            && (array_intersect($region, $regionKeys) == $region)
        ) {
            $orderRequest->whereIn('region_id', $region);
        } else {
            $orderRequest->whereIn('region_id', $regionKeys);
        }

        $availableApiChannels = $this->getAllAvailableChannels();
        if (!is_null($apiChannel) && (trim($apiChannel) != '')) {
            $orderRequest->where('channel', trim($apiChannel));
        } else {
            $orderRequest->whereIn('channel', array_keys($availableApiChannels));
        }

        $availableStatuses = $this->getPicklistStatuses();
        $statusKeys = array_keys($availableStatuses);
        if (
            !is_null($status)
            && is_array($status)
            && (count($status) > 0)
            && (array_intersect($status, $statusKeys) == $status)
        ) {
            $orderRequest->whereIn('order_status', $status);
        } else {
            $orderRequest->whereIn('order_status', $statusKeys);
        }

        $startDateClean = (!is_null($startDate) && (trim($startDate) != '')) ? date('Y-m-d', strtotime(trim($startDate))) : null;
        $endDateClean = (!is_null($endDate) && (trim($endDate) != '')) ? date('Y-m-d', strtotime(trim($endDate))) : null;
        if (!is_null($startDateClean) && !is_null($endDateClean)) {
            $fromDate = '';
            $toDate = '';
            if ($endDateClean > $startDateClean) {
                $fromDate = $startDateClean;
                $toDate = $endDateClean;
            } else {
                $fromDate = $endDateClean;
                $toDate = $startDateClean;
            }
            $orderRequest->whereBetween('delivery_date', [$fromDate, $toDate]);
        }

        $givenTimeSlots = $this->getDeliveryTimeSlots();
        if (!is_null($timeSlot) && (trim($timeSlot) != '') && (count($givenTimeSlots) > 0) && in_array(trim($timeSlot), $givenTimeSlots)) {
            $orderRequest->where('delivery_time_slot', trim($timeSlot));
        } elseif (count($givenTimeSlots) > 0) {
            $orderRequest->whereIn('delivery_time_slot', $givenTimeSlots);
        }

        $orderRequest->orderBy('delivery_date', 'asc');
        $orderRequest->orderBy(DB::raw("STR_TO_DATE(TRIM(SUBSTRING_INDEX(delivery_time_slot, '-', 1)), '%l:%i %p')"), 'asc');
        $orderRequest->orderBy('increment_id', 'asc');

        return $orderRequest->get();

    }

    public function getCategoryByProductId($productId = '', $env = '', $channel = '') {

        if (is_null($productId) || (trim($productId) == '') || !is_numeric(trim($productId))) {
            return null;
        }

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'parentcatid/' . trim($productId);
        $apiResult = $apiService->processGetApi($uri);
        if (!$apiResult['status']) {
            return null;
        }

        if (!is_array($apiResult['response']) || (count($apiResult['response']) == 0)) {
            return null;
        }

        return $apiResult['response'][0];

    }

    public function getFileUrl($path = '') {
        return $this->baseService->getFileUrl($path);
    }

    public function getUserImageUrl($path = '') {
        return $this->baseService->getFileUrl('media/images/users/' . $path);
    }

}
