<?php


namespace Modules\Supervisor\Entities;

use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrder;
use DB;
use \Exception;
use Modules\Base\Entities\BaseServiceHelper;
use App\Models\User;
use Modules\Sales\Entities\SaleOrderAddress;
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

    public function getSupervisorOrders($region = '', $apiChannel = '', $status = '', $startDate = '', $endDate = '', $timeSlot = '') {

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
                        'status' => $historyEl['status'],
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

        if ($allItemsAvailable) {

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

                if(is_array($saleOrderEl['status_histories']) && (count($saleOrderEl['status_histories']) > 0)) {
                    foreach ($saleOrderEl['status_histories'] as $historyEl) {
                        $statusHistoryObj = SaleOrderStatusHistory::firstOrCreate([
                            'order_id' => $order->id,
                            'history_id' => $historyEl['entity_id'],
                            'sale_order_id' => $order->order_id,
                        ], [
                            'name' => $historyEl['entity_name'],
                            'status' => $historyEl['status'],
                            'comments' => $historyEl['comment'],
                            'status_created_at' => $historyEl['created_at'],
                            'customer_notified' => $historyEl['is_customer_notified'],
                            'visible_on_front' => $historyEl['is_visible_on_front'],
                            'is_active' => 1
                        ]);
                    }
                }

                if (
                    ($saleOrderEl['status'] === SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED)
                    || ($saleOrderEl['status'] !== SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH)
                ) {
                    return [
                        'status' => false,
                        'message' => "Sale Order status could not update!"
                    ];
                }

                $orderUpdateResult = SaleOrder::where('id', $order->id)
                    ->update([
                        'order_updated_at' => $saleOrderEl['updated_at'],
                        'box_count' => (isset($saleOrderEl['extension_attributes']['box_count'])) ? $saleOrderEl['extension_attributes']['box_count'] : null,
                        'order_due' => $saleOrderEl['total_due'],
                        'order_state' => $saleOrderEl['state'],
                        'order_status' => $saleOrderEl['status'],
                        'order_status_label' => (isset($saleOrderEl['extension_attributes']['order_status_label'])) ? $saleOrderEl['extension_attributes']['order_status_label'] : null,
                    ]);

                if(is_array($saleOrderEl['items']) && (count($saleOrderEl['items']) > 0)) {
                    foreach ($saleOrderEl['items'] as $orderItemEl) {
                        $itemExtAttr = $orderItemEl['extension_attributes'];

                        $orderItemUpdateResult = SaleOrderItem::where('order_id', $order->id)
                            ->where('item_id', $orderItemEl['item_id'])
                            ->where('sale_order_id', $order->order_id)
                            ->update([
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
                                'store_availability' => $orderItemUpdateData[$orderItemEl['item_id']]['store_availability'],
                                'availability_checked_at' => $orderItemUpdateData[$orderItemEl['item_id']]['availability_checked_at'],
                            ]);

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
        $apiResult = $this->restApiService->processGetApi($uri);

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
