<?php


namespace Modules\Admin\Entities;

use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrder;
use DB;

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

    public function getDeliveryTimeSlots() {
        $statusList = $this->getAdminAllowedStatuses();
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

    public function getOrdersCountByRegion($region = '') {

        if (is_null($region) || (trim($region) == '')) {
            return [];
        }

        $givenFromDate = date('Y-m-d', strtotime('-3 days'));
        $givenToDate =  date('Y-m-d', strtotime('+10 days'));

        $orders = SaleOrder::where('region_code', $region)
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

        $orderRequest->orderBy('delivery_date', 'asc');

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

        $emirates = config('fms.emirates');
        if (!is_null($region) && (trim($region) != '')) {
            $orderRequest->where('region_code', trim($region));
        } else {
            $orderRequest->whereIn('region_code', array_keys($emirates));
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

        $emirates = config('fms.emirates');
        if (!is_null($region) && (trim($region) != '')) {
            $orderRequest->where('region_code', trim($region));
        } else {
            $orderRequest->whereIn('region_code', array_keys($emirates));
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

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        if (is_null($countryId) || (is_string($countryId) && (trim($countryId) == ''))) {
            $countryId = $apiService->getApiDefaultCountry();
        }

        $uri = $apiService->getRestApiUrl() . 'directory/countries/' . $countryId;
        $apiResult = $apiService->processGetApi($uri, [], [], true, true);

        return ($apiResult['status']) ? $apiResult['response'] : [];

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

}
