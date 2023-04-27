<?php


namespace Modules\Supervisor\Entities;

use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrder;
use DB;
use \Exception;
use Modules\Base\Entities\BaseServiceHelper;
use App\Models\User;
use Modules\Sales\Entities\SaleOrderAddress;
use Modules\Sales\Entities\SaleOrderAmountCollection;
use Modules\Sales\Entities\SaleOrderItem;
use Modules\Sales\Entities\SaleOrderPayment;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\Sales\Entities\SaleOrderStatusHistory;

class SupervisorServiceHelper
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

    public function getSupervisorsAllowedStatuses() {
        $statusList = config('fms.order_statuses');
        $allowedStatusList = config('fms.role_allowed_statuses.supervisor');
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
        $statusList = $this->getSupervisorsAllowedStatuses();
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

    public function getDeliveryZones($region = [], $regionwise = false) {

        $statusList = $this->getSupervisorsAllowedStatuses();
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

    public function getSupervisorOrders($region = [], $apiChannel = '', $status = '', $startDate = '', $endDate = '', $timeSlot = '', $zone = []) {

        $orderRequest = SaleOrder::select('*');

        $orderRequest->where('env', $this->getApiEnvironment());

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

        $availableStatuses = $this->getSupervisorsAllowedStatuses();
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

        if (!is_null($zone) && is_array($zone) && (count($zone) > 0)) {
            $orderRequest->whereIn('zone_id', $zone);
        }

        $orderRequest->orderBy('delivery_date', 'asc');
        $orderRequest->orderBy(DB::raw("STR_TO_DATE(TRIM(SUBSTRING_INDEX(delivery_time_slot, '-', 1)), '%l:%i %p')"), 'asc');
        $orderRequest->orderBy('order_id', 'asc');

        return $orderRequest->get();

    }

    public function getSaleOrderSalesChartData($apiChannel = '', $region = [], $status = '', $startDate = '', $endDate = '', $timeSlot = '', $zone = []) {

        $returnData = [];

        $orderRequest = SaleOrder::select('delivery_date', 'order_currency', DB::raw('sum(order_total) as total_sum'));

        $orderRequest->where('env', $this->getApiEnvironment());

        $availableApiChannels = $this->getAllAvailableChannels();
        if (!is_null($apiChannel) && (trim($apiChannel) != '')) {
            $orderRequest->where('channel', trim($apiChannel));
        } else {
            $orderRequest->whereIn('channel', array_keys($availableApiChannels));
        }

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

        $availableStatuses = $this->getSupervisorsAllowedStatuses();
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

        if (!is_null($zone) && is_array($zone) && (count($zone) > 0)) {
            $orderRequest->whereIn('zone_id', $zone);
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

    public function getSaleOrderStatusChartData($apiChannel = '', $region = [], $status = '', $startDate = '', $endDate = '', $timeSlot = '', $zone = []) {

        $returnData = [];

        $orderRequest = SaleOrder::select('delivery_date', 'order_status', 'order_status_label', DB::raw('count(*) as total_orders'));

        $orderRequest->where('env', $this->getApiEnvironment());

        $availableApiChannels = $this->getAllAvailableChannels();
        if (!is_null($apiChannel) && (trim($apiChannel) != '')) {
            $orderRequest->where('channel', trim($apiChannel));
        } else {
            $orderRequest->whereIn('channel', array_keys($availableApiChannels));
        }

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

        $availableStatuses = $this->getSupervisorsAllowedStatuses();
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

        if (!is_null($zone) && is_array($zone) && (count($zone) > 0)) {
            $orderRequest->whereIn('zone_id', $zone);
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

    public function getFileUrl($path = '') {
        return $this->baseService->getFileUrl($path);
    }

    public function getUserImageUrl($path = '') {
        return $this->baseService->getFileUrl('media/images/users/' . $path);
    }

    public function isPickerAssigned(User $picker = null) {
        $assignmentObj = null;
        if (!is_null($picker) && (count($picker->saleOrderProcessHistory) > 0)) {
            foreach ($picker->saleOrderProcessHistory as $processHistory) {
                if ($processHistory->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP) {
                    if (
                        ($processHistory->saleOrder)
                        && ($processHistory->saleOrder->order_status == SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED)
                    ) {
                        $assignmentObj = $processHistory;
                    }
                }
            }
        }
        return $assignmentObj;
    }

    public function isDriverAssigned(User $driver = null) {
        $assignmentObj = null;
        if (!is_null($driver) && (count($driver->saleOrderProcessHistory) > 0)) {
            foreach ($driver->saleOrderProcessHistory as $processHistory) {
                if ($processHistory->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY) {
                    if (
                        ($processHistory->saleOrder)
                        && (
                            ($processHistory->saleOrder->order_status == SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH)
                            || ($processHistory->saleOrder->order_status == SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY)
                        )
                    ) {
                        $assignmentObj = $processHistory;
                    }
                }
            }
        }
        return $assignmentObj;
    }

    public function setOrderAsBeingPrepared(SaleOrder $order = null, $pickerId = 0, $supervisorId = 0) {

        if (is_null($order)) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        $orderEnv = $order->env;
        $orderChannel = $order->channel;
        $apiService = new RestApiService();
        $apiService->setApiEnvironment($orderEnv);
        $apiService->setApiChannel($orderChannel);

        $uri = $apiService->getRestApiUrl() . 'changeorderstatus';
        $params = [
            'orderId' => $order->order_id,
            'state' => SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED,
            'status' => SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED
        ];
        $statusApiResult = $apiService->processPostApi($uri, $params);
        if (!$statusApiResult['status']) {
            return [
                'status' => false,
                'message' => $statusApiResult['message']
            ];
        }

        $uri = $apiService->getRestApiUrl() . 'orders/' . $order->order_id;
        $orderApiResult = $apiService->processGetApi($uri);
        if (!$orderApiResult['status']) {
            return [
                'status' => false,
                'message' => $orderApiResult['message']
            ];
        }

        try {

            $saleOrderEl = $orderApiResult['response'];
            $orderUpdateResult = SaleOrder::where('id', $order->id)
                ->update([
                    'order_updated_at' => $saleOrderEl['updated_at'],
                    'order_due' => $saleOrderEl['total_due'],
                    'order_state' => $saleOrderEl['state'],
                    'order_status' => $saleOrderEl['status'],
                    'order_status_label' => (isset($saleOrderEl['extension_attributes']['order_status_label'])) ? $saleOrderEl['extension_attributes']['order_status_label'] : null,
                ]);

            $paymentObj = SaleOrderPayment::updateOrCreate([
                'order_id' => $order->id,
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

            if(is_array($saleOrderEl['status_histories']) && (count($saleOrderEl['status_histories']) > 0)) {
                foreach ($saleOrderEl['status_histories'] as $historyEl) {
                    $statusHistoryObj = SaleOrderStatusHistory::firstOrCreate([
                        'order_id' => $order->id,
                        'history_id' => $historyEl['entity_id'],
                        'sale_order_id' => $order->order_id,
                    ], [
                        'name' => $historyEl['entity_name'],
                        'status' => array_key_exists('status', $historyEl) ? $historyEl['status'] : null,
                        'comments' => $historyEl['comment'],
                        'status_created_at' => $historyEl['created_at'],
                        'customer_notified' => $historyEl['is_customer_notified'],
                        'visible_on_front' => $historyEl['is_visible_on_front'],
                        'is_active' => 1
                    ]);
                }
            }

            $saleOrderProcessHistoryAssigner = (new SaleOrderProcessHistory())->create([
                'order_id' => $order->id,
                'action' => SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP_ASSIGN,
                'status' => 1,
                'comments' => 'The Sale Order Id #' . $order->order_id . ' is assigned for pickup.',
                'extra_info' => null,
                'done_by' => ($supervisorId !== 0) ? $supervisorId : null,
                'done_at' => date('Y-m-d H:i:s'),
            ]);
            $saleOrderProcessHistoryAssigned = (new SaleOrderProcessHistory())->create([
                'order_id' => $order->id,
                'action' => SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP,
                'status' => 1,
                'comments' => 'The Sale Order Id #' . $order->order_id . ' is assigned for pickup.',
                'extra_info' => null,
                'done_by' => $pickerId,
                'done_at' => date('Y-m-d H:i:s'),
            ]);

            return [
                'status' => true,
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

    }

    public function setOrderAsDispatchReady(SaleOrder $order = null, $boxCount = 0, $storeAvailabilityArray = [], $pickerId = 0) {

        if (is_null($order)) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        if (is_null($boxCount) || ((int)$boxCount <= 0)) {
            return [
                'status' => false,
                'message' => 'Sale Order Box Count is empty!'
            ];
        }

        if (!is_array($storeAvailabilityArray) || (count($storeAvailabilityArray) == 0)) {
            return [
                'status' => false,
                'message' => 'Sale Order Item Availability Data is empty!'
            ];
        }

        $notAllowedStatues = [
            SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH,
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY,
            SaleOrder::SALE_ORDER_STATUS_DELIVERED
        ];
        if (in_array($order->order_status, $notAllowedStatues)) {
            return [
                'status' => false,
                'message' => 'Sale Order status cannot be changed!'
            ];
        }

        $orderEnv = $order->env;
        $orderChannel = $order->channel;
        $apiService = new RestApiService();
        $apiService->setApiEnvironment($orderEnv);
        $apiService->setApiChannel($orderChannel);

        $allItemsAvailable = true;
        $orderItemUpdateData = [];
        $orderItemPostAQData = [];
        $orderItemPostNotAvData = [];
        foreach ($order->orderItems as $orderItemEl) {
            $itemInputId = $orderItemEl->sku;
            if(!empty($orderItemEl->item_barcode)){
                $barcode = $orderItemEl->item_barcode;
                if(substr($barcode,7)!=000000) {
                    $itemInputId = $barcode;
                }
            }
            $itemInputId = $orderItemEl->item_id;
            if (array_key_exists($orderItemEl->id, $storeAvailabilityArray)) {
                $availability = $storeAvailabilityArray[$orderItemEl->id];
                $actualItemQty = ((int)$storeAvailabilityArray[$orderItemEl->id] === SaleOrderItem::STORE_AVAILABLE_YES) ? $orderItemEl->qty_ordered : 0;
                if ((int)$storeAvailabilityArray[$orderItemEl->id] === SaleOrderItem::STORE_AVAILABLE_NO) {
                    $allItemsAvailable = false;
                    $orderItemPostNotAvData[] = [
                        'itemId' => $itemInputId,
                        'itemName' => $orderItemEl->item_name,
                        'itemSku' => $orderItemEl->item_sku
                    ];
                }
                $orderItemUpdateData[$orderItemEl->item_id] = [
                    'id' => $orderItemEl->id,
                    'item_id' => $orderItemEl->item_id,
                    'store_availability' => $availability,
                    'availability_checked_at' => date('Y-m-d H:i:s'),
                    'actual_qty' => $actualItemQty,
                ];
                $orderItemPostAQData[$itemInputId] = $actualItemQty;
            }
        }
        $orderStatusNew = ($allItemsAvailable) ? SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH : SaleOrder::SALE_ORDER_STATUS_ON_HOLD;

        if (count($orderItemPostAQData) > 0) {

            $uri = $apiService->getRestApiUrl() . 'changeorderstatus';
            $params = [
                'orderId' => $order->order_id,
                'state' => $orderStatusNew,
                'status' => $orderStatusNew,
                'parcelCount' => $boxCount,
                'actualQuantity' => $orderItemPostAQData,
                'itemsNotAvailable' => $orderItemPostNotAvData
            ];
            $statusApiResult = $apiService->processPostApi($uri, $params);
            if (!$statusApiResult['status']) {
                return [
                    'status' => false,
                    'message' => $statusApiResult['message']
                ];
            }

            /*$statusApiResponse = $statusApiResult['response'];
            if (($statusApiResponse['status'] !== 'success') || ($statusApiResponse['status'] == 'failed')) {
                return [
                    'status' => false,
                    'message' => $statusApiResponse['message']
                ];
            }*/

            $uri = $apiService->getRestApiUrl() . 'orders/' . $order->order_id;
            $orderApiResult = $apiService->processGetApi($uri);
            if (!$orderApiResult['status']) {
                return [
                    'status' => false,
                    'message' => $orderApiResult['message']
                ];
            }

            try {

                $saleOrderEl = $orderApiResult['response'];

                if(!is_null($saleOrderEl['status_histories']) && is_array($saleOrderEl['status_histories']) && (count($saleOrderEl['status_histories']) > 0)) {
                    foreach ($saleOrderEl['status_histories'] as $historyEl) {
                        $statusHistoryObj = SaleOrderStatusHistory::firstOrCreate([
                            'order_id' => $order->id,
                            'history_id' => $historyEl['entity_id'],
                            'sale_order_id' => $order->order_id,
                        ], [
                            'name' => $historyEl['entity_name'],
                            'status' => array_key_exists('status', $historyEl) ? $historyEl['status'] : null,
                            'comments' => $historyEl['comment'],
                            'status_created_at' => $historyEl['created_at'],
                            'customer_notified' => $historyEl['is_customer_notified'],
                            'visible_on_front' => $historyEl['is_visible_on_front'],
                            'is_active' => 1
                        ]);
                    }
                }

                $permittedStatusesArray = [
                    SaleOrder::SALE_ORDER_STATUS_ON_HOLD,
                    SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH
                ];
                if (!in_array($saleOrderEl['status'], $permittedStatusesArray)) {
                    return [
                        'status' => false,
                        'message' => "Sale Order status could not update!"
                    ];
                }

                $orderUpdateResult = SaleOrder::where('id', $order->id)
                    ->update([
                        'order_updated_at' => $saleOrderEl['updated_at'],
                        'box_count' => (isset($saleOrderEl['extension_attributes']['box_count'])) ? $saleOrderEl['extension_attributes']['box_count'] : null,
                        /*'order_due' => $saleOrderEl['total_due'],*/
                        'canceled_total' => (isset($saleOrderEl['total_canceled'])) ? $saleOrderEl['total_canceled'] : null,
                        'invoiced_total' => (isset($saleOrderEl['total_invoiced'])) ? $saleOrderEl['total_invoiced'] : null,
                        'order_state' => $saleOrderEl['state'],
                        'order_status' => $saleOrderEl['status'],
                        'order_status_label' => (isset($saleOrderEl['extension_attributes']['order_status_label'])) ? $saleOrderEl['extension_attributes']['order_status_label'] : null,
                    ]);

                if(is_array($saleOrderEl['items']) && (count($saleOrderEl['items']) > 0)) {
                    foreach ($saleOrderEl['items'] as $orderItemEl) {
                        $itemExtAttr = $orderItemEl['extension_attributes'];

                        $currentOrderItemUpdateData = [
                            'item_updated_at' => $orderItemEl['updated_at'],
                            'actual_qty' => ((array_key_exists('actual_qty', $itemExtAttr)) ? $itemExtAttr['actual_qty'] : $orderItemEl['qty_ordered']),
                            'qty_ordered' => $orderItemEl['qty_ordered'],
                            'qty_shipped' => $orderItemEl['qty_shipped'],
                            'qty_invoiced' => $orderItemEl['qty_invoiced'],
                            'qty_canceled' => $orderItemEl['qty_canceled'],
                            'qty_returned' => ((array_key_exists('qty_returned', $orderItemEl)) ? $orderItemEl['qty_returned'] : null),
                            'qty_refunded' => $orderItemEl['qty_refunded'],
                            'billing_period' => ((array_key_exists('billing_period', $itemExtAttr)) ? $itemExtAttr['billing_period'] : null),
                            'delivery_day' => ((array_key_exists('delivery_day', $itemExtAttr)) ? $itemExtAttr['delivery_day'] : null),
                            'item_weight' => $orderItemEl['row_weight'],
                            'vendor_id' => ((array_key_exists('vendor_id', $itemExtAttr)) ? $itemExtAttr['vendor_id'] : null),
                            'vendor_availability' => ((array_key_exists('vendor_availability', $itemExtAttr)) ? $itemExtAttr['vendor_availability'] : 0),
                        ];

                        if (array_key_exists($orderItemEl['item_id'], $orderItemUpdateData)) {
                            $currentOrderItemUpdateData['store_availability'] = $orderItemUpdateData[$orderItemEl['item_id']]['store_availability'];
                            $currentOrderItemUpdateData['availability_checked_at'] = $orderItemUpdateData[$orderItemEl['item_id']]['availability_checked_at'];
                        }

                        $orderItemUpdateResult = SaleOrderItem::where('order_id', $order->id)
                            ->where('item_id', $orderItemEl['item_id'])
                            ->where('sale_order_id', $order->order_id)
                            ->update($currentOrderItemUpdateData);

                    }
                }

                $paymentObj = SaleOrderPayment::updateOrCreate([
                    'order_id' => $order->id,
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

                $saleOrderProcessHistoryAssigner = (new SaleOrderProcessHistory())->create([
                    'order_id' => $order->id,
                    'action' => SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKED,
                    'status' => 1,
                    'comments' => 'The Sale Order Id #' . $order->order_id . ' is picked and ready to dispatch.',
                    'extra_info' => null,
                    'done_by' => ($pickerId !== 0) ? $pickerId : null,
                    'done_at' => date('Y-m-d H:i:s'),
                ]);

                return [
                    'status' => true,
                ];

            } catch (\Exception $e) {
                return [
                    'status' => false,
                    'message' => $e->getMessage()
                ];
            }

        } else {

            try {

                $orderStatuses = config('fms.order_statuses');

                $orderUpdateResult = SaleOrder::where('id', $order->id)
                    ->update([
                        'order_state' => $orderStatusNew,
                        'order_status' => $orderStatusNew,
                        'order_status_label' => (array_key_exists($orderStatusNew, $orderStatuses)) ? $orderStatuses[$orderStatusNew] : null,
                    ]);

                foreach ($orderItemUpdateData as $orderItemId => $orderItemUpdateDatum) {
                    $orderItemUpdateResult = SaleOrderItem::where('id', $orderItemUpdateDatum['id'])
                        ->update([
                            'actual_qty' => $orderItemUpdateDatum['actual_qty'],
                            'store_availability' => $orderItemUpdateDatum['store_availability'],
                            'availability_checked_at' => $orderItemUpdateDatum['availability_checked_at'],
                        ]);

                }

                return [
                    'status' => true,
                ];

            } catch (\Exception $e) {
                return [
                    'status' => false,
                    'message' => $e->getMessage()
                ];
            }

        }

    }

    public function assignOrderToDriver(SaleOrder $order = null, $driverId = 0, $supervisorId = 0) {

        if (is_null($order)) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        try {

            $saleOrderProcessHistoryAssigner = (new SaleOrderProcessHistory())->create([
                'order_id' => $order->id,
                'action' => SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY_ASSIGN,
                'status' => 1,
                'comments' => 'The Sale Order Id #' . $order->order_id . ' is assigned for delivery.',
                'extra_info' => null,
                'done_by' => ($supervisorId !== 0) ? $supervisorId : null,
                'done_at' => date('Y-m-d H:i:s'),
            ]);
            $saleOrderProcessHistoryAssigned = (new SaleOrderProcessHistory())->create([
                'order_id' => $order->id,
                'action' => SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY,
                'status' => 1,
                'comments' => 'The Sale Order Id #' . $order->order_id . ' is assigned for delivery.',
                'extra_info' => null,
                'done_by' => $driverId,
                'done_at' => date('Y-m-d H:i:s'),
            ]);

            return [
                'status' => true,
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

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

    public function getServerOrderDetails(SaleOrder $order = null) {

        if (is_null($order)) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        $saleOrderEl = $this->getOrderDetailsById($order->order_id, $order->env, $order->channel);
        if (!is_array($saleOrderEl) || (count($saleOrderEl) == 0)) {
            return [
                'status' => false,
                'message' => 'Could not fetch the data for Sale Order!'
            ];
        }

        return [
            'status' => true,
            'message' => 'The Sale Order data is fetched successfully',
            'orderData' => $saleOrderEl
        ];

    }

    public function getInvoiceDetails(SaleOrder $order = null) {

        if (is_null($order)) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        $saleInvoiceEl = $this->fetchInvoiceDetailsByOrderId($order->order_id, $order->env, $order->channel);
        if (!is_array($saleInvoiceEl) || (count($saleInvoiceEl) == 0)) {
            return [
                'status' => false,
                'message' => 'Could not fetch the data for Sale Order Invoice!'
            ];
        }

        $order->invoice_id = $saleInvoiceEl['entity_id'];
        $order->invoice_number = $saleInvoiceEl['increment_id'];
        $order->invoiced_at = date('Y-m-d H:i:s', strtotime($saleInvoiceEl['created_at']));
        $order->saveQuietly();

        return [
            'status' => true,
            'message' => 'The Sale Order Invoice data is fetched successfully',
            'invoiceData' => $saleInvoiceEl
        ];

    }

    public function syncOrderToKerabiya(SaleOrder $order = null, $userId = 0) {

        if (is_null($order)) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        $apiService = new RestApiService();
        $kerabiyaUrl = $apiService->getKerabiyaApiUrl();
        $kerabiyaKey = $apiService->getKerabiyaApiKey();
        $kerabiyaStaticValues = $apiService->getKerabiyaApiStaticValues();

        $order->saleCustomer;
        $order->orderItems;
        $order->billingAddress;
        $order->shippingAddress;
        $order->paymentData;
        $order->paidAmountCollections;
        $order->statusHistory;
        $saleOrderData = $order->toArray();

        $shipAddress = array_key_exists('shipping_address', $saleOrderData) ?  $saleOrderData['shipping_address'] : [];

        $shipAddressString = '';
        $customerName = '';
        if (count($shipAddress) > 0) {
            $shipAddressString .= (isset($shipAddress['company'])) ? $shipAddress['company'] . ' ' : '';
            $shipAddressString .= (isset($shipAddress['address_1'])) ? $shipAddress['address_1'] : '';
            $shipAddressString .= (isset($shipAddress['address_2'])) ? ', ' . $shipAddress['address_2'] : '';
            $shipAddressString .= (isset($shipAddress['address_3'])) ? ', ' . $shipAddress['address_3'] : '';
            $shipAddressString .= (isset($shipAddress['city'])) ? ', ' . $shipAddress['city'] : '';
            $shipAddressString .= (isset($shipAddress['region'])) ? ', ' . $shipAddress['region'] : '';
            $shipAddressString .= (isset($shipAddress['post_code'])) ? ', ' . $shipAddress['post_code'] : '';
            $customerName = $shipAddress['first_name'] . ' ' . $shipAddress['last_name'];
        }

        $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
        $fixTotalPaidArray = ['adminpaymentmethod', 'ngeniusonline'];
        $totalOrderValueOrig = (float)$saleOrderData['order_total'];
        $totalCanceledValue = (!is_null($saleOrderData['canceled_total'])) ? (float)$saleOrderData['canceled_total'] : 0;
        $totalOrderValue = $totalOrderValueOrig - $totalCanceledValue;
        $totalDueValue = $saleOrderData['order_due'];
        $initialPaidValue = (float)$saleOrderData['order_total'] - (float)$saleOrderData['order_due'];
        if (in_array($saleOrderData['payment_data'][0]['method'], $fixTotalDueArray)) {
            $totalDueValue = $totalOrderValue;
            $initialPaidValue = 0;
        }
        if (in_array($saleOrderData['payment_data'][0]['method'], $fixTotalPaidArray)) {
            $totalDueValue = 0;
            $initialPaidValue = $totalOrderValue;
        }

        $paymentMethodTitle = '';
        $payInfoLoopTargetLabel = 'method_title';
        if (isset($saleOrderData['payment_data'][0]['extra_info'])) {
            $paymentAddInfo = json5_decode($saleOrderData['payment_data'][0]['extra_info'], true);
            if (is_array($paymentAddInfo) && (count($paymentAddInfo) > 0)) {
                foreach ($paymentAddInfo as $paymentInfoEl) {
                    if ($paymentInfoEl['key'] == $payInfoLoopTargetLabel) {
                        $paymentMethodTitle = $paymentInfoEl['value'];
                    }
                }
            }
        }

        $amountCollectionData = [];
        $totalCollectedAmount = 0;
        foreach(SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS as $cMethod) {
            $amountCollectionData[$cMethod] = 0;
        }

        if (count($saleOrderData['paid_amount_collections']) > 0) {
            foreach ($saleOrderData['paid_amount_collections'] as $paidCollEl) {
                $amountCollectionData[$paidCollEl['method']] += (float) $paidCollEl['amount'];
                $totalCollectedAmount += (float) $paidCollEl['amount'];
                $totalDueValue -= (float) $paidCollEl['amount'];
            }
        }

        $paymentStatus = '';
        $epsilon = 0.00001;
        if (!(abs($totalOrderValue - 0) < $epsilon)) {
            if (abs($totalDueValue - 0) < $epsilon) {
                $paymentStatus = 'paid';
            } else {
                if ($totalDueValue < 0) {
                    $paymentStatus = 'overpaid';
                } else {
                    $paymentStatus = 'due';
                }
            }
        }

        $finalCollectionValue = ($paymentStatus === 'due') ? $totalDueValue : 0;

        $timeSlotSplitter = explode('-', $saleOrderData['delivery_time_slot'], 2);
        $fromTimeSlot = trim($timeSlotSplitter[0]);
        $toTimeSlot = trim($timeSlotSplitter[1]);

        $fromSlotDateTime = new \DateTime($fromTimeSlot);
        $fromSlot24Format = $fromSlotDateTime->format('H:i');
        $fromSlot24FormatSplitter = explode(':', $fromSlot24Format);
        $fromSlot24FormatClean = (trim($fromSlot24FormatSplitter[1]) == '00') ? trim($fromSlot24FormatSplitter[0]) : trim($fromSlot24Format);

        $toSlotDateTime = new \DateTime($toTimeSlot);
        $toSlot24Format = $toSlotDateTime->format('H:i');
        $toSlot24FormatSplitter = explode(':', $toSlot24Format);
        $toSlot24FormatClean = (trim($toSlot24FormatSplitter[1]) == '00') ? trim($toSlot24FormatSplitter[0]) : trim($toSlot24Format);

        $postData = [
            "ToCompany" => $customerName,
            "ToAddress" => $shipAddressString,
            "ToCity" => $shipAddress['region'],
            "ToLocation" => $shipAddress['city'],
            "ToCountry" => $shipAddress['country_id'],
            "ToCperson" => $customerName,
            "ToContactno" => $shipAddress['contact_number'],
            "ToMobileno" => $shipAddress['contact_number'],
            "ReferenceNumber" => "#" . $saleOrderData['increment_id'],
            "CompanyCode" => $kerabiyaStaticValues['company_code'],
            "Weight" => $kerabiyaStaticValues['weight'],
            "Pieces" => $saleOrderData['box_count'],
            "PackageType" => "Next Day", // Next Day/Same Day
            "CurrencyCode" => $kerabiyaStaticValues['currency_code'],
            "NcndAmount" => number_format($finalCollectionValue, 2, '.', ','),
            "ItemDescription" => "",
            "SpecialInstruction" => $saleOrderData['delivery_notes'],
            "BranchName" => $kerabiyaStaticValues['branch_name'],
            "TimeSlot"  => $fromSlot24FormatClean . ' to ' . $toSlot24FormatClean,
            "PreferedDate"  => $saleOrderData['delivery_date'],
        ];

        $uri = $kerabiyaUrl . 'CustomerBooking';
        $headers = [
            'API-KEY' => $kerabiyaKey
        ];
        $kerabiyaApiResult = $apiService->processPostApi($uri, $postData, $headers, false);
        if (!$kerabiyaApiResult['status']) {
            return [
                'status' => false,
                'message' => $kerabiyaApiResult['message']
            ];
        }

        $apiResponse = $kerabiyaApiResult['response'];
        if (!is_array($apiResponse) || (count($apiResponse) == 0)) {
            return [
                'status' => false,
                'message' => 'Could not fetch the data from Kerabiya Logistics!'
            ];
        }

        if ($apiResponse['success'] != '1') {
            return [
                'status' => false,
                'message' => $apiResponse['message']
            ];
        }

        $order->is_kerabiya_delivery = 1;
        $order->kerabiya_set_at = date('Y-m-d H:i:s');
        $order->kerabiya_set_by = $userId;
        $order->kerabiya_awb_number = $apiResponse['AwbNumber'];
        $order->kerabiya_awb_pdf = $apiResponse['AwbPdf'];
        $order->saveQuietly();

        return [
            'status' => true,
            'message' => 'The Sale Order is synced to Kerabiya Logistics successfully',
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

    private function getCustomerDetailsById($customerId = '', $env = '', $apiChannel = '') {

        if (is_null($customerId) || (trim($customerId) == '') || !is_numeric(trim($customerId)) || ((int) trim($customerId) <= 0)) {
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

        $uri = $apiService->getRestApiUrl() . 'customers/' . $customerId;
        $apiResult = $apiService->processGetApi($uri);

        return ($apiResult['status']) ? $apiResult['response'] : [];

    }

    private function fetchInvoiceDetailsByOrderId($orderId = '', $env = '', $apiChannel = '') {

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

        $uri = $apiService->getRestApiUrl() . 'invoices';
        $qParams = [
            'searchCriteria[filter_groups][0][filters][0][field]' => 'order_id',
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
        ) ? $apiResult['response']['items'][0] : [];

    }

    private function processSaleOrder($currentOrderData = [], $saleOrderEl = []) {

        try {

            $orderShippingAddress = $saleOrderEl['extension_attributes']['shipping_assignments'][0]['shipping']['address'];

            $updateData = [
                'order_updated_at' => $saleOrderEl['updated_at'],
                'total_item_count' => $saleOrderEl['total_item_count'],
                'total_qty_ordered' => $saleOrderEl['total_qty_ordered'],
                'order_weight' => $saleOrderEl['weight'],
                'zone_id' => ((array_key_exists('zone', $saleOrderEl['extension_attributes'])) ? $saleOrderEl['extension_attributes']['zone'] : null),
                'box_count' => (isset($saleOrderEl['extension_attributes']['box_count'])) ? $saleOrderEl['extension_attributes']['box_count'] : null,
                'not_require_pack' => (isset($saleOrderEl['extension_attributes']['not_require_pack'])) ? $saleOrderEl['extension_attributes']['not_require_pack'] : 1,
                'order_subtotal' => $saleOrderEl['subtotal'],
                'order_tax' => $saleOrderEl['tax_amount'],
                'discount_amount' => $saleOrderEl['discount_amount'],
                'shipping_total' => $saleOrderEl['shipping_amount'],
                'shipping_method' => $saleOrderEl['shipping_description'],
                'eco_friendly_packing_fee' => (isset($saleOrderEl['extension_attributes']['eco_friendly_packing'])) ? $saleOrderEl['extension_attributes']['eco_friendly_packing'] : null,
                'order_total' => $saleOrderEl['grand_total'],
                'canceled_total' => (isset($saleOrderEl['total_canceled'])) ? $saleOrderEl['total_canceled'] : null,
                'invoiced_total' => (isset($saleOrderEl['total_invoiced'])) ? $saleOrderEl['total_invoiced'] : null,
                'order_state' => $saleOrderEl['state'],
                'order_status' => $saleOrderEl['status'],
                'order_status_label' => (isset($saleOrderEl['extension_attributes']['order_status_label'])) ? $saleOrderEl['extension_attributes']['order_status_label'] : null,
            ];

            if (!array_key_exists('total_canceled', $saleOrderEl)) {
                $updateData['order_due'] = $saleOrderEl['total_due'];
            }

            $saleOrderObj = SaleOrder::where('id', $currentOrderData['id'])
                ->update($updateData);

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

            $customerId = (array_key_exists('customer_id', $saleOrderEl) && (trim($saleOrderEl['customer_id']) != '')) ? $saleOrderEl['customer_id'] : null;
            $orderEnv = $currentOrderData['env'];
            $orderChannel = $currentOrderData['channel'];
            $customerData = $this->getCustomerDetailsById($customerId, $orderEnv, $orderChannel);
            $latitude = null;
            $longitude = null;
            $addressId = array_key_exists('customer_address_id', $orderShippingAddress) ?  $orderShippingAddress['customer_address_id'] : null;
            if (is_array($customerData) && (count($customerData) > 0)) {
                if (array_key_exists('addresses', $customerData) && is_array($customerData['addresses']) && (count($customerData['addresses']) > 0)) {
                    $customerAddressList = $customerData['addresses'];
                    foreach ($customerAddressList as $currentAddressEl) {
                        if (!is_null($addressId) && ($currentAddressEl['id'] == $addressId)) {
                            $addressCustAttrBase = (array_key_exists('custom_attributes', $currentAddressEl) && (count($currentAddressEl['custom_attributes']) > 0)) ? $currentAddressEl['custom_attributes'] : [];
                            $addressCustAttr = [];
                            foreach ($addressCustAttrBase as $attrObj) {
                                $addressCustAttr[$attrObj['attribute_code']] = $attrObj['value'];
                            }
                            if (array_key_exists('latitude', $addressCustAttr) && !is_null($addressCustAttr['latitude'])) {
                                $latitude = $addressCustAttr['latitude'];
                            }
                            if (array_key_exists('longitude', $addressCustAttr) && !is_null($addressCustAttr['longitude'])) {
                                $longitude = $addressCustAttr['longitude'];
                            }
                        }
                    }
                }
            }

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
                'latitude' => $latitude,
                'longitude' => $longitude,
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
