<?php


namespace Modules\Admin\Entities;

use Modules\Base\Entities\BaseServiceHelper;
use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleCustomer;
use Modules\Sales\Entities\SaleOrder;
use DB;
use Modules\Sales\Entities\SaleOrderAddress;
use Modules\Sales\Entities\SaleOrderItem;
use Modules\Sales\Entities\SaleOrderPayment;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\Sales\Entities\SaleOrderStatusHistory;

class AdminServiceHelper
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

    public function getAdminAllowedStatuses() {
        $statusList = config('fms.order_statuses');
        $allowedStatusList = SaleOrder::AVAILABLE_ORDER_STATUSES;
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

    public function getResyncStatuses() {
        $statusList = config('fms.order_statuses');
        $allowedStatusList = config('fms.resync_statuses');
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
        $statusList = $this->getAdminAllowedStatuses();
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

    public function getOrdersCountByRegion($region = '') {

        if (is_null($region) || (trim($region) == '')) {
            return [];
        }

        $givenFromDate = date('Y-m-d', strtotime('-3 days'));
        $givenToDate =  date('Y-m-d', strtotime('+10 days'));

        $orders = SaleOrder::where('region_id', $region)
            ->whereIn('order_status', SaleOrder::AVAILABLE_ORDER_STATUSES)
            ->whereBetween('delivery_date', [$givenFromDate, $givenToDate])
            ->groupBy('delivery_date', 'delivery_time_slot')
            ->select('delivery_date', 'delivery_time_slot', DB::raw('count(*) as total_orders'))
            ->get();

        return $orders;


    }

    public function getOrdersByRegion($region = '', $interval = '', $date = '', $pageSize = 0, $currentPage = 0) {

        if (
            (is_null($region) || (trim($region) == ''))
            || (is_null($interval) || (trim($interval) == ''))
            || (is_null($date) || (trim($date) == ''))
        ) {
            return [];
        }

        $pageSizeClean = (is_numeric(trim($pageSize))) ? trim((int)$pageSize) : 0;
        $currentPageClean = (is_numeric(trim($currentPage))) ? trim((int)$currentPage) : 0;

        $regionOrders = SaleOrder::where('region_code', $region)
            ->whereIn('order_status', SaleOrder::AVAILABLE_ORDER_STATUSES)
            ->where('delivery_date', $date);

        if ($interval !== 'na') {
            $regionOrders->where('delivery_time_slot', $interval);
        }

        $regionOrders->join('sale_customers', 'sale_orders.customer_id', '=', 'sale_customers.id')
            ->select('sale_orders.*', 'sale_customers.customer_group_id', 'sale_customers.sale_customer_id')
            ->groupBy('order_id')
            ->orderBy('delivery_date', 'asc')
            ->orderBy('zone_id', 'asc');

        if (($pageSizeClean > 0) && ($currentPageClean > 0)) {
            $currentOffset = (($currentPageClean - 1) * $pageSizeClean);
            $regionOrders->offset($currentOffset)->limit($pageSizeClean);
        }

        $resultOrders = $regionOrders->get();

        return ($resultOrders) ? $resultOrders->toArray() : [];

    }

    public function getSaleOrderItemsBySchedule($region = '', $date = '', $interval = '') {

        if (
            (is_null($region) || (trim($region) == ''))
            || (is_null($date) || (trim($date) == ''))
            || (is_null($interval) || (trim($interval) == ''))
        ) {
            return [];
        }

        $orderItems = SaleOrder::where('sale_orders.region_code', $region)
            ->whereIn('sale_orders.order_status', SaleOrder::AVAILABLE_ORDER_STATUSES)
            ->where('sale_orders.delivery_date', $date)
            ->where('sale_orders.delivery_time_slot', $interval)
            ->join('sale_order_items', 'sale_orders.order_id', '=', 'sale_order_items.sale_order_id')
            ->select('sale_order_items.product_id', 'sale_order_items.item_sku', 'sale_order_items.item_name', 'sale_order_items.country_label', 'sale_order_items.selling_unit', 'sale_order_items.item_info', 'sale_order_items.scale_number', 'sale_order_items.qty_ordered')
            ->groupBy('sale_order_items.item_id')
            ->orderBy('sale_order_items.product_id', 'asc')
            ->get();

        return ($orderItems) ? $orderItems->toArray() : [];

    }

    public function getSaleOrderItemsByDate($region = '', $date = '') {

        if (
            (is_null($region) || (trim($region) == ''))
            || (is_null($date) || (trim($date) == ''))
        ) {
            return [];
        }

        $orderItems = SaleOrder::where('sale_orders.region_code', $region)
            ->whereIn('sale_orders.order_status', SaleOrder::AVAILABLE_ORDER_STATUSES)
            ->where('sale_orders.delivery_date', $date)
            ->join('sale_order_items', 'sale_orders.order_id', '=', 'sale_order_items.sale_order_id')
            ->select('sale_order_items.product_id', 'sale_order_items.item_sku', 'sale_order_items.item_name', 'sale_order_items.country_label', 'sale_order_items.selling_unit', 'sale_order_items.item_info', 'sale_order_items.scale_number', DB::raw('SUM(sale_order_items.qty_ordered) as total_qty'))
            ->groupBy('sale_order_items.product_id')
            ->orderBy('sale_order_items.product_id', 'asc')
            ->get();

        return ($orderItems) ? $orderItems->toArray() : [];

    }

    public function getSaleOrderItemsByOrderIds($orders = []) {

        if (
            is_null($orders) || (count($orders) == 0)
        ) {
            return [];
        }

        $orderItems = SaleOrder::whereIn('sale_orders.id', $orders)
            ->join('sale_order_items', 'sale_orders.order_id', '=', 'sale_order_items.sale_order_id')
            ->select('sale_order_items.product_id', 'sale_order_items.item_sku', 'sale_order_items.item_name', 'sale_order_items.country_label', 'sale_order_items.selling_unit', 'sale_order_items.item_info', 'sale_order_items.scale_number', DB::raw('SUM(sale_order_items.qty_ordered) as total_qty'))
            ->groupBy('sale_order_items.product_id')
            ->orderBy('sale_order_items.product_id', 'asc')
            ->get();

        return ($orderItems) ? $orderItems->toArray() : [];

    }

    public function getAdminSaleOrders($region = '', $apiChannel = '', $status = '', $startDate = '', $endDate = '', $timeSlot = '') {

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

        $availableStatuses = $this->getAdminAllowedStatuses();
        if (!is_null($status) && (trim($status) != '')) {
            $orderRequest->where('order_status', trim($status));
        } else {
            $orderRequest->whereIn('order_status', array_keys($availableStatuses));
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
        $orderRequest->orderBy('order_id', 'asc');

        return $orderRequest->get();

    }

    public function getSaleOrderSalesChartData($apiChannel = '', $region = '', $status = '', $startDate = '', $endDate = '', $timeSlot = '') {

        $returnData = [];

        $orderRequest = SaleOrder::select('delivery_date', 'order_currency', DB::raw('sum(order_total) as total_sum'));

        $availableApiChannels = $this->getAllAvailableChannels();
        if (!is_null($apiChannel) && (trim($apiChannel) != '')) {
            $orderRequest->where('channel', trim($apiChannel));
        } else {
            $orderRequest->whereIn('channel', array_keys($availableApiChannels));
        }

        $emirates = $this->getAvailableRegionsList();
        if (!is_null($region) && (trim($region) != '')) {
            $orderRequest->where('region_id', trim($region));
        } else {
            $orderRequest->whereIn('region_id', array_keys($emirates));
        }

        $availableStatuses = $this->getAdminAllowedStatuses();
        if (!is_null($status) && (trim($status) != '')) {
            $orderRequest->where('order_status', trim($status));
        } else {
            $orderRequest->whereIn('order_status', array_keys($availableStatuses));
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

        $queryResult = $orderRequest
            ->groupBy('delivery_date', 'order_currency')
            ->orderBy('delivery_date', 'asc')
            ->orderBy('order_currency', 'asc')
            ->get();

        if($queryResult && (count($queryResult) > 0)) {
            foreach ($queryResult as $currentRow) {
                $returnData[$currentRow['delivery_date']][$currentRow['order_currency']] = $currentRow;
            }
        }

        return $returnData;

    }

    public function getSaleOrderStatusChartData($apiChannel = '', $region = '', $status = '', $startDate = '', $endDate = '', $timeSlot = '') {

        $returnData = [];

        $orderRequest = SaleOrder::select('delivery_date', 'order_status', 'order_status_label', DB::raw('count(*) as total_orders'));

        $availableApiChannels = $this->getAllAvailableChannels();
        if (!is_null($apiChannel) && (trim($apiChannel) != '')) {
            $orderRequest->where('channel', trim($apiChannel));
        } else {
            $orderRequest->whereIn('channel', array_keys($availableApiChannels));
        }

        $emirates = $this->getAvailableRegionsList();
        if (!is_null($region) && (trim($region) != '')) {
            $orderRequest->where('region_id', trim($region));
        } else {
            $orderRequest->whereIn('region_id', array_keys($emirates));
        }

        $availableStatuses = $this->getAdminAllowedStatuses();
        if (!is_null($status) && (trim($status) != '')) {
            $orderRequest->where('order_status', trim($status));
        } else {
            $orderRequest->whereIn('order_status', array_keys($availableStatuses));
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

        $queryResult = $orderRequest
            ->groupBy('delivery_date', 'order_status')
            ->orderBy('delivery_date', 'asc')
            ->orderBy('order_status', 'asc')
            ->get();

        if($queryResult && (count($queryResult) > 0)) {
            foreach ($queryResult as $currentRow) {
                $returnData[$currentRow['delivery_date']][$currentRow['order_status']] = $currentRow;
            }
        }

        return $returnData;

    }

    public function getDriversByDate($dateString = '', $env = '', $channel = '') {

        if (is_null($dateString) || (trim($dateString) == '')) {
            return [];
        }

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'getdriversbydate';
        $qParams = [
            'date' => trim($dateString)
        ];
        $apiResult = $apiService->processGetApi($uri, $qParams, [], true, true);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function getCustomerGroups($env = '', $channel = '') {

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'customerGroups/search';
        $qParams = [
            'searchCriteria' => '?'
        ];
        $apiResult = $apiService->processGetApi($uri, $qParams, [], true, true);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function getOrderVendorStatus($orderIds = []) {

        if (!is_array($orderIds) || (is_array($orderIds) && (count($orderIds) == 0))) {
            return [];
        }

        $orderIdList = SaleOrder::whereIn('id', $orderIds)->select('id', 'order_id', 'channel')->get();
        if(count($orderIdList) > 0) {
            $channelOrderList = [];
            foreach ($orderIdList as $orderEl) {
                $channelOrderList[$orderEl['channel']][$orderEl['id']] = $orderEl['order_id'];
            }
            $resultArray = [];
            foreach ($channelOrderList as $channelKey => $channelEl) {
                $apiService = new RestApiService();
                $apiService->setApiChannel($channelKey);
                foreach ($channelEl as $orderIdKey => $orderNumber) {
                    $uri = $apiService->getRestApiUrl() . 'vendors/orderstatus';
                    $qParams = [
                        'orderId' => $orderNumber
                    ];
                    $apiResult = $apiService->processGetApi($uri, $qParams, [], true, true);
                    if ($apiResult['status']) {
                        $currentResponse = $apiResult['response'];
                        foreach ($currentResponse as $vendor) {
                            $resultArray[$orderIdKey] = $vendor['status'];
                        }
                    }
                }
            }
            return $resultArray;
        }
        return [];
    }

    public function getAvailableRegionsList($countryId = '', $env = '', $channel = '') {

        $baseServiceHelper = new BaseServiceHelper();
        $regionList = $baseServiceHelper->getRegionList($env, $channel);

        if (count($regionList) == 0) {
            return [];
        }

        $returnData = [];
        foreach ($regionList as $regionEl) {
            $returnData[$regionEl['region_id']] = $regionEl['name'];
        }

        return $returnData;

    }

    public function getAvailableCityList($countryId = '', $env = '', $channel = '') {

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        if (is_null($countryId) || (is_string($countryId) && (trim($countryId) == ''))) {
            $countryId = $apiService->getApiDefaultCountry();
        }

        $uri = $apiService->getRestApiUrl() . 'directory/areas/' . $countryId;
        $apiResult = $apiService->processGetApi($uri, [], [], true, true);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function getVendorsList($env = '', $channel = '') {

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'vendors';
        $apiResult = $apiService->processGetApi($uri, [], [], true, true);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function resyncSaleOrderFromServer(SaleOrder $order = null, $userId = 0) {

        if (is_null($order)) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        $order->saleCustomer;
        $order->orderItems;
        $order->shippingAddress;
        $order->paymentData;
        $order->statusHistory;
        $order->processHistory;
        $saleOrderFormerData = $order->toArray();

        $orderEnv = $saleOrderFormerData['env'];
        $orderChannel = $saleOrderFormerData['channel'];
        $apiOrderId = $saleOrderFormerData['order_id'];

        $storeConfig = $this->getStoreConfigs($orderEnv, $orderChannel);
        $saleOrderEl = $this->getOrderDetailsById($apiOrderId, $orderEnv, $orderChannel);
        if (!is_array($saleOrderEl) || (count($saleOrderEl) == 0)) {
            return [
                'status' => false,
                'message' => 'Could not fetch the data for Sale Order!'
            ];
        }

        $saleResponse = $this->processSaleOrder($saleOrderFormerData, $saleOrderEl);
        if ($saleResponse['status'] === false) {
            return [
                'status' => false,
                'message' => $saleResponse['message']
            ];
        }

        $saleOrderObj = $saleResponse['saleOrderObj'];

        $orderSyncErrors = [];

        if(!is_array($saleOrderEl['items']) || (count($saleOrderEl['items']) == 0)) {
            $orderSyncErrors[] = 'There is no Order Item for Sale Order.';
        } else {

            $itemsToDelete = [];
            foreach ($saleOrderFormerData['order_items'] as $formerItem) {
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

            foreach ($saleOrderEl['items'] as $orderItemEl) {

                $commonOrderItem = [];
                foreach ($saleOrderFormerData['order_items'] as $formerItem) {
                    if ($formerItem['item_id'] === $orderItemEl['item_id']) {
                        $commonOrderItem = $formerItem;
                    }
                }

                $orderItemResponse = $this->processSaleOrderItem($orderItemEl, $saleOrderFormerData, $commonOrderItem, $storeConfig);
                if(!$orderItemResponse['status']) {
                    $orderSyncErrors[] = 'Could not process Order Item #' . $orderItemEl['item_id'] . ' (' . $orderItemResponse['message'] . ').';
                }
            }

            SaleOrderItem::destroy($itemsToDelete);

        }

        $shippingResponse = $this->processSaleOrderShippingAddress($saleOrderEl, $saleOrderFormerData);
        if (!$shippingResponse['status']) {
            $orderSyncErrors[] = 'Could not process Shipping Address data (' . $shippingResponse['message'] . ').';
        }

        $paymentResponse = $this->processSaleOrderPayments($saleOrderEl, $saleOrderFormerData);
        if (!$paymentResponse['status']) {
            $orderSyncErrors[] = 'Could not process Payment data (' . $paymentResponse['message'] . ').';
        }

        if(!is_array($saleOrderEl['status_histories']) || (count($saleOrderEl['status_histories']) == 0)) {
            $orderSyncErrors[] = 'There is no Status History data for Sale Order.';
        } else {
            foreach ($saleOrderEl['status_histories'] as $historyEl) {
                $historyResponse = $this->processSaleOrderStatusHistory($historyEl, $saleOrderFormerData);
                if(!$historyResponse['status']) {
                    $orderSyncErrors[] = 'Could not process Status History data (' . $historyResponse['message'] . ').';
                }
            }
        }

        $processResponse = $this->recordOrderStatusProcess($saleOrderFormerData, $userId);
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

        /*$uri = $apiService->getRestApiUrl() . 'orders';
        $qParams = [
            'searchCriteria[filter_groups][0][filters][0][field]' => 'entity_id',
            'searchCriteria[filter_groups][0][filters][0][condition_type]' => 'eq',
            'searchCriteria[filter_groups][0][filters][0][value]' => $orderId
        ];
        $apiResult = $apiService->processGetApi($uri, $qParams);
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

    private function processSaleOrder($currentOrderData = [], $saleOrderEl = []) {

        try {

            $orderShippingAddress = $saleOrderEl['extension_attributes']['shipping_assignments'][0]['shipping']['address'];

            $saleOrderObj = SaleOrder::where('id', $currentOrderData['id'])
                ->update([
                    'order_updated_at' => $saleOrderEl['updated_at'],
                    'total_item_count' => $saleOrderEl['total_item_count'],
                    'total_qty_ordered' => $saleOrderEl['total_qty_ordered'],
                    'order_weight' => $saleOrderEl['weight'],
                    'box_count' => (isset($saleOrderEl['extension_attributes']['box_count'])) ? $saleOrderEl['extension_attributes']['box_count'] : null,
                    'not_require_pack' => (isset($saleOrderEl['extension_attributes']['not_require_pack'])) ? $saleOrderEl['extension_attributes']['not_require_pack'] : 1,
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
                ]);

            return [
                'status' => true,
                'message' => '',
                'saleOrderObj' => $saleOrderObj,
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

    }

    private function processSaleOrderItem($orderItemEl = [], $currentOrderData = [], $currentOrderItem = [], $storeConfig = []) {

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
                'order_id' => $currentOrderData['id'],
                'item_id' => $orderItemEl['item_id'],
                'sale_order_id' => $currentOrderData['order_id']
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

    private function processSaleOrderShippingAddress($saleOrderEl = [], $currentOrderData = []) {

        try {

            $orderShippingAddress = $saleOrderEl['extension_attributes']['shipping_assignments'][0]['shipping']['address'];

            $shippingAddressObj = SaleOrderAddress::updateOrInsert([
                'order_id' => $currentOrderData['id'],
                'sale_order_id' => $saleOrderEl['entity_id'],
                'type' => 'shipping',
            ], [
                'address_id' => $orderShippingAddress['entity_id'],
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

    private function processSaleOrderPayments($saleOrderEl = [], $currentOrderData = []) {

        try {

            $paymentObj = SaleOrderPayment::firstOrCreate([
                'order_id' => $currentOrderData['id'],
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

    private function processSaleOrderStatusHistory($historyEl = [], $currentOrderData = []) {

        try {

            $statusHistoryObj = SaleOrderStatusHistory::firstOrCreate([
                'order_id' => $currentOrderData['id'],
                'history_id' => $historyEl['entity_id'],
                'sale_order_id' => $currentOrderData['order_id'],
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

    private function recordOrderStatusProcess($currentOrderData = [], $userId = 0) {

        try {

            $saleOrderProcessHistoryObj = (new SaleOrderProcessHistory())->create([
                'order_id' => $currentOrderData['id'],
                'action' => SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_RESYNC,
                'status' => 1,
                'comments' => 'The Sale Order Id #' . $currentOrderData['order_id'] . ' is re-synced.',
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
