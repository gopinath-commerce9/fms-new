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

    public function getOrdersCountByRegion($region = '') {

        if (is_null($region) || (trim($region) == '')) {
            return [];
        }

        /*$uri = $this->restApiService->getRestApiUrl() . 'getorderscountbyregion';
        $qParams = [
            'region' => trim($region)
        ];
        $apiResult = $this->restApiService->processGetApi($uri, $qParams);

        return ($apiResult['status']) ? $apiResult['response'] : [];*/

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

        /*$uri = $this->restApiService->getRestApiUrl() . 'getordersbyregion';
        $qParams = [
            'region' => trim($region),
            'timeInterval' => trim($interval),
            'date' => trim($date),
            'pageSize' => trim($pageSizeClean),
            'currentPage' => trim($currentPageClean),
        ];
        $apiResult = $this->restApiService->processGetApi($uri, $qParams);

        return ($apiResult['status']) ? $apiResult['response'] : [];*/

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

    public function getDriversByDate($dateString = '') {

        if (is_null($dateString) || (trim($dateString) == '')) {
            return [];
        }

        $uri = $this->restApiService->getRestApiUrl() . 'getdriversbydate';
        $qParams = [
            'date' => trim($dateString)
        ];
        $apiResult = $this->restApiService->processGetApi($uri, $qParams, [], true, true);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    public function getCustomerGroups() {

        $uri = $this->restApiService->getRestApiUrl() . 'customerGroups/search';
        $qParams = [
            'searchCriteria' => '?'
        ];
        $apiResult = $this->restApiService->processGetApi($uri, $qParams, [], true, true);

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

    public function getVendorsList() {

        $uri = $this->restApiService->getRestApiUrl() . 'vendors';
        $apiResult = $this->restApiService->processGetApi($uri, [], [], true, true);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

}
