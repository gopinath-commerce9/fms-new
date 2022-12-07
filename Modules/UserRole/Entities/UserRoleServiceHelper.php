<?php


namespace Modules\UserRole\Entities;

use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrder;
use DB;
use \Exception;
use Modules\Base\Entities\BaseServiceHelper;
use App\Models\User;
use Modules\Sales\Entities\SaleOrderAddress;
use Modules\Sales\Entities\SaleOrderAmountCollection;
use Modules\Sales\Entities\SaleOrderPayment;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\Sales\Entities\SaleOrderStatusHistory;

class UserRoleServiceHelper
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

    public function getPickersAllowedStatuses() {
        $statusList = config('fms.order_statuses');
        $allowedStatusList = config('fms.role_allowed_statuses.picker');
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

    public function getDriversAllowedStatuses() {
        $statusList = config('fms.order_statuses');
        $allowedStatusList = config('fms.role_allowed_statuses.driver');
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

    public function getPickerDeliveryTimeSlots() {
        $statusList = $this->getPickersAllowedStatuses();
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

    public function getPickerOrderStats($region = '', $apiChannel = '', $picker = [], $startDate = '', $endDate = '', $timeSlot = '') {

        $statsList = [];

        $regionClean = (!is_null($region) && (trim($region) != '')) ? trim($region) : null;
        $apiChannelClean = (!is_null($apiChannel) && (trim($apiChannel) != '')) ? trim($apiChannel) : null;
        $pickerClean = (!is_null($picker) && is_array($picker) && (count($picker) > 0)) ? $picker : null;
        $startDateClean = (!is_null($startDate) && (trim($startDate) != '')) ? date('Y-m-d', strtotime(trim($startDate))) : null;
        $endDateClean = (!is_null($endDate) && (trim($endDate) != '')) ? date('Y-m-d', strtotime(trim($endDate))) : null;
        $timeSlotClean = (!is_null($timeSlot) && (trim($timeSlot) != '')) ? trim($timeSlot) : null;
        $fromDate = null;
        $toDate = null;
        if (!is_null($startDateClean) && !is_null($endDateClean)) {
            if ($endDateClean > $startDateClean) {
                $fromDate = $startDateClean;
                $toDate = $endDateClean;
            } else {
                $fromDate = $endDateClean;
                $toDate = $startDateClean;
            }
        }

        $filterableSaleOrderIds = [];
        $filterableUserOrderIds = [];

        $userRoleObj = new UserRole();
        $pickers = $userRoleObj->allPickers();
        if(count($pickers->mappedUsers) > 0) {
            $pickersArray = $pickers->mappedUsers->toArray();
            foreach($pickersArray as $userEl) {

                if (!is_null($pickerClean) && !in_array($userEl['id'], $pickerClean)) {
                    continue;
                }

                $pickedDataList = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKED)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                $currentPickupOrders = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                if ($pickedDataList && (count($pickedDataList) > 0)) {
                    $pickedDataArray = $pickedDataList->toArray();
                    $pickedOrderIds = array_column($pickedDataArray, 'order_id');
                    foreach($pickedOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$userEl['id']][] = $orderIdEl;
                    }
                }

                if ($currentPickupOrders && (count($currentPickupOrders) > 0)) {
                    $currentPickupArray = $currentPickupOrders->toArray();
                    $currentPickupOrderIds = array_column($currentPickupArray, 'order_id');
                    foreach($currentPickupOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$userEl['id']][] = $orderIdEl;
                    }
                }

            }
        }

        if (count($filterableSaleOrderIds) > 0) {

            $chunkedArraySize = 5000;
            foreach (array_chunk($filterableSaleOrderIds, $chunkedArraySize) as $chunkedKey => $chunkedSaleOrderIds) {

                $orderRequest = SaleOrder::select('*');
                $orderRequest->whereIn('id', $chunkedSaleOrderIds);

                $emirates = $this->getAvailableRegionsList();
                if (!is_null($regionClean) && (trim($regionClean) != '')) {
                    $orderRequest->where('region_id', trim($regionClean));
                } else {
                    $orderRequest->whereIn('region_id', array_keys($emirates));
                }

                $availableApiChannels = $this->getAllAvailableChannels();
                if (!is_null($apiChannelClean) && (trim($apiChannelClean) != '')) {
                    $orderRequest->where('channel', trim($apiChannelClean));
                } else {
                    $orderRequest->whereIn('channel', array_keys($availableApiChannels));
                }

                $givenTimeSlots = $this->getDeliveryTimeSlots();
                if (!is_null($timeSlotClean) && (trim($timeSlotClean) != '') && (count($givenTimeSlots) > 0) && in_array(trim($timeSlotClean), $givenTimeSlots)) {
                    $orderRequest->where('delivery_time_slot', trim($timeSlotClean));
                } elseif (count($givenTimeSlots) > 0) {
                    $orderRequest->whereIn('delivery_time_slot', $givenTimeSlots);
                }

                $orderList = $orderRequest->get();

                if ($orderList && (count($orderList) > 0)) {
                    $orderListArray = $orderList->toArray();
                    foreach ($orderListArray as $orderEl) {

                        $pickedData = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKED)
                            ->limit(1)->get();

                        $currentPicker = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP)
                            ->orderBy('done_at', 'desc')
                            ->limit(1)->get();

                        $historyObj = null;
                        if ($pickedData && (count($pickedData) > 0)) {
                            $historyObj = $pickedData->first();
                        } elseif ($currentPicker && (count($currentPicker) > 0)) {
                            $historyObj = $currentPicker->first();
                        }

                        if (!is_null($historyObj)) {

                            $canProceed = true;
                            if (!is_null($historyObj->done_by) && !array_key_exists($historyObj->done_by, $filterableUserOrderIds)) {
                                $canProceed = false;
                            }

                            if (!is_null($fromDate) && (date('Y-m-d', strtotime($fromDate)) > date('Y-m-d', strtotime($historyObj->done_at)))) {
                                $canProceed = false;
                            }

                            if (!is_null($toDate) && (date('Y-m-d', strtotime($toDate)) < date('Y-m-d', strtotime($historyObj->done_at)))) {
                                $canProceed = false;
                            }

                            if ($canProceed) {

                                $userElQ = User::select('*')
                                    ->where('id', $historyObj->done_by)->get();
                                $userEl = ($userElQ) ? $userElQ->first() : $historyObj->actionDoer;

                                $currentUserId = (!is_null($userEl)) ? $userEl->id : $historyObj->done_by;
                                $currentUserName = (!is_null($userEl)) ? $userEl->name : '[Deleted User]';
                                $currentUserActive = (!is_null($userEl) && ($userEl->mappedRole->first()->pivot->is_active == '1')) ? 'Yes': 'No';

                                $currentTotalCount = 0;
                                $currentAssignCount = 0;
                                $currentPickedCount = 0;
                                $currentHoldedCount = 0;
                                if (
                                    array_key_exists($currentUserId, $statsList)
                                    && array_key_exists(date('Y-m-d', strtotime($historyObj->done_at)), $statsList[$currentUserId])
                                ) {
                                    $currentTotalCount = (int) $statsList[$currentUserId][date('Y-m-d', strtotime($historyObj->done_at))]['totalOrders'];
                                    $currentAssignCount = (int) $statsList[$currentUserId][date('Y-m-d', strtotime($historyObj->done_at))]['assignedOrders'];
                                    $currentPickedCount = (int) $statsList[$currentUserId][date('Y-m-d', strtotime($historyObj->done_at))]['pickedOrders'];
                                    $currentHoldedCount = (int) $statsList[$currentUserId][date('Y-m-d', strtotime($historyObj->done_at))]['holdedOrders'];
                                }

                                if ($historyObj->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKED) {
                                    $currentPickedCount++;
                                } elseif ($historyObj->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP) {
                                    if ($orderEl['order_status'] == SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED) {
                                        $currentAssignCount++;
                                    } elseif ($orderEl['order_status'] == SaleOrder::SALE_ORDER_STATUS_ON_HOLD) {
                                        $currentHoldedCount++;
                                    }
                                }

                                $currentTotalCount++;

                                if (($currentPickedCount > 0) || ($currentAssignCount > 0) || ($currentHoldedCount > 0)) {
                                    $statsList[$currentUserId][date('Y-m-d', strtotime($historyObj->done_at))] = [
                                        'pickerId' => $currentUserId,
                                        'picker' => $currentUserName,
                                        'active' => $currentUserActive,
                                        'date' => date('Y-m-d', strtotime($historyObj->done_at)),
                                        'totalOrders' => $currentTotalCount,
                                        'assignedOrders' => $currentAssignCount,
                                        'pickedOrders' => $currentPickedCount,
                                        'holdedOrders' => $currentHoldedCount
                                    ];
                                }

                            }

                        }

                    }
                }

            }

        }

        $tempStatsList = $statsList;
        $statsList = [];
        foreach ($tempStatsList as $pickerKey => $dateData) {
            foreach ($dateData as $dateKey => $reportData) {
                $statsList[] = $reportData;
            }
        }

        return $statsList;

    }

    public function getPickerOrderStatsExcel($region = '', $apiChannel = '', $picker = [], $startDate = '', $endDate = '', $timeSlot = '') {

        $statsList = [];

        $regionClean = (!is_null($region) && (trim($region) != '')) ? trim($region) : null;
        $apiChannelClean = (!is_null($apiChannel) && (trim($apiChannel) != '')) ? trim($apiChannel) : null;
        $pickerClean = (!is_null($picker) && is_array($picker) && (count($picker) > 0)) ? $picker : null;
        $startDateClean = (!is_null($startDate) && (trim($startDate) != '')) ? date('Y-m-d', strtotime(trim($startDate))) : null;
        $endDateClean = (!is_null($endDate) && (trim($endDate) != '')) ? date('Y-m-d', strtotime(trim($endDate))) : null;
        $timeSlotClean = (!is_null($timeSlot) && (trim($timeSlot) != '')) ? trim($timeSlot) : null;
        $fromDate = null;
        $toDate = null;
        if (!is_null($startDateClean) && !is_null($endDateClean)) {
            if ($endDateClean > $startDateClean) {
                $fromDate = $startDateClean;
                $toDate = $endDateClean;
            } else {
                $fromDate = $endDateClean;
                $toDate = $startDateClean;
            }
        }

        $availableStatuses = $this->getAvailableStatuses();

        $filterableSaleOrderIds = [];
        $filterableUserOrderIds = [];

        $userRoleObj = new UserRole();
        $pickers = $userRoleObj->allPickers();
        if(count($pickers->mappedUsers) > 0) {
            $pickersArray = $pickers->mappedUsers->toArray();
            foreach($pickersArray as $userEl) {

                if (!is_null($pickerClean) && !in_array($userEl['id'], $pickerClean)) {
                    continue;
                }

                $pickedDataList = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKED)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                $currentPickupOrders = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                if ($pickedDataList && (count($pickedDataList) > 0)) {
                    $pickedDataArray = $pickedDataList->toArray();
                    $pickedOrderIds = array_column($pickedDataArray, 'order_id');
                    foreach($pickedOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$userEl['id']][] = $orderIdEl;
                    }
                }

                if ($currentPickupOrders && (count($currentPickupOrders) > 0)) {
                    $currentPickupArray = $currentPickupOrders->toArray();
                    $currentPickupOrderIds = array_column($currentPickupArray, 'order_id');
                    foreach($currentPickupOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$userEl['id']][] = $orderIdEl;
                    }
                }

            }
        }

        if (count($filterableSaleOrderIds) > 0) {

            $chunkedArraySize = 5000;
            foreach (array_chunk($filterableSaleOrderIds, $chunkedArraySize) as $chunkedKey => $chunkedSaleOrderIds) {

                $orderRequest = SaleOrder::select('*');
                $orderRequest->whereIn('id', $chunkedSaleOrderIds);

                $emirates = $this->getAvailableRegionsList();
                if (!is_null($regionClean) && (trim($regionClean) != '')) {
                    $orderRequest->where('region_id', trim($regionClean));
                } else {
                    $orderRequest->whereIn('region_id', array_keys($emirates));
                }

                $availableApiChannels = $this->getAllAvailableChannels();
                if (!is_null($apiChannelClean) && (trim($apiChannelClean) != '')) {
                    $orderRequest->where('channel', trim($apiChannelClean));
                } else {
                    $orderRequest->whereIn('channel', array_keys($availableApiChannels));
                }

                $givenTimeSlots = $this->getDeliveryTimeSlots();
                if (!is_null($timeSlotClean) && (trim($timeSlotClean) != '') && (count($givenTimeSlots) > 0) && in_array(trim($timeSlotClean), $givenTimeSlots)) {
                    $orderRequest->where('delivery_time_slot', trim($timeSlotClean));
                } elseif (count($givenTimeSlots) > 0) {
                    $orderRequest->whereIn('delivery_time_slot', $givenTimeSlots);
                }

                $orderList = $orderRequest->get();
                if ($orderList && (count($orderList) > 0)) {
                    $orderListArray = $orderList->toArray();
                    foreach($orderListArray as $orderEl) {

                        $pickedData = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKED)
                            ->limit(1)->get();

                        $currentPicker = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP)
                            ->orderBy('done_at', 'desc')
                            ->limit(1)->get();

                        $currentPickerAssigner = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP_ASSIGN)
                            ->orderBy('done_at', 'desc')
                            ->limit(1)->get();

                        $historyObj = null;
                        if ($pickedData && (count($pickedData) > 0)) {
                            $historyObj = $pickedData->first();
                        } elseif ($currentPicker && (count($currentPicker) > 0)) {
                            $historyObj = $currentPicker->first();
                        }

                        $assignerObj = null;
                        if ($currentPickerAssigner && (count($currentPickerAssigner) > 0)) {
                            $assignerObj = $currentPickerAssigner->first();
                        }

                        if (!is_null($historyObj) && !is_null($assignerObj)) {

                            $canProceed = true;
                            if (!is_null($historyObj->done_by) && !array_key_exists($historyObj->done_by, $filterableUserOrderIds)) {
                                $canProceed = false;
                            }

                            if (!is_null($fromDate) && (date('Y-m-d', strtotime($fromDate)) > date('Y-m-d', strtotime($historyObj->done_at)))) {
                                $canProceed = false;
                            }

                            if (!is_null($toDate) && (date('Y-m-d', strtotime($toDate)) < date('Y-m-d', strtotime($historyObj->done_at)))) {
                                $canProceed = false;
                            }

                            if ($canProceed) {

                                $userElQ = User::select('*')
                                    ->where('id', $historyObj->done_by)->get();
                                $userEl = ($userElQ) ? $userElQ->first() : $historyObj->actionDoer;

                                $currentUserId = (!is_null($userEl)) ? $userEl->id : $historyObj->done_by;
                                $currentUserName = (!is_null($userEl)) ? $userEl->name : '[Deleted User]';
                                $currentUserActive = (!is_null($userEl) && ($userEl->mappedRole->first()->pivot->is_active == '1')) ? 'Yes': 'No';

                                $assignerElQ = User::select('*')
                                    ->where('id', $assignerObj->done_by)->get();
                                $assignerEl = ($assignerElQ) ? $assignerElQ->first() : $assignerObj->actionDoer;

                                $currentAssignerId = (!is_null($assignerEl)) ? $assignerEl->id : $assignerObj->done_by;
                                $currentAssignerName = (!is_null($assignerEl)) ? $assignerEl->name : '[Deleted User]';
                                $currentAssignerActive = (!is_null($assignerEl) && ($assignerEl->mappedRole->first()->pivot->is_active == '1')) ? 'Yes': 'No';

                                $saleOrderExtraData = [
                                    'shipping_address' => [],
                                ];

                                $shippingAddressRequest = SaleOrderAddress::select('*')
                                    ->where('order_id', $orderEl['id'])
                                    ->where('type', 'shipping')
                                    ->limit(1)
                                    ->get();
                                if ($shippingAddressRequest && (count($shippingAddressRequest) > 0)) {
                                    $saleOrderExtraData['shipping_address'] = $shippingAddressRequest->first()->toArray();
                                }

                                $shipAddress = $saleOrderExtraData['shipping_address'];
                                $customerNameString = '';
                                $customerNameString .= (isset($shipAddress['first_name'])) ? $shipAddress['first_name'] : '';
                                $customerNameString .= (isset($shipAddress['last_name'])) ? ' ' . $shipAddress['last_name'] : '';
                                $customerContactString = (isset($shipAddress['contact_number'])) ? ' ' . $shipAddress['contact_number'] : '';
                                $shipAddressString = '';
                                $shipAddressString .= (isset($shipAddress['company'])) ? $shipAddress['company'] . ' ' : '';
                                $shipAddressString .= (isset($shipAddress['address_1'])) ? $shipAddress['address_1'] : '';
                                $shipAddressString .= (isset($shipAddress['address_2'])) ? ', ' . $shipAddress['address_2'] : '';
                                $shipAddressString .= (isset($shipAddress['address_3'])) ? ', ' . $shipAddress['address_3'] : '';
                                $shipAddressString .= (isset($shipAddress['city'])) ? ', ' . $shipAddress['city'] : '';
                                $shipAddressString .= (isset($shipAddress['region'])) ? ', ' . $shipAddress['region'] : '';
                                $shipAddressString .= (isset($shipAddress['post_code'])) ? ', ' . $shipAddress['post_code'] : '';

                                $tempArrayRecord = [
                                    'pickerId' => $currentUserId,
                                    'picker' => $currentUserName,
                                    'orderDeliveryDate' => date('Y-m-d', strtotime($orderEl['delivery_date'])),
                                    'orderDeliverySlot' => $orderEl['delivery_time_slot'],
                                    'pickerAssignedDate' => date('Y-m-d', strtotime($historyObj->done_at)),
                                    'pickerAssignedAt' => date('Y-m-d H:i:s', strtotime($historyObj->done_at)),
                                    'pickerAssignedBy' => $currentAssignerId,
                                    'pickerAssignerName' => $currentAssignerName,
                                    'orderRecordId' => $orderEl['id'],
                                    'orderId' => $orderEl['order_id'],
                                    'orderNumber' => "#" . $orderEl['increment_id'],
                                    'emirates' => $orderEl['region'],
                                    'customerName' => $customerNameString,
                                    'shippingAddress' => $shipAddressString,
                                    'customerContact' => $customerContactString,
                                    'orderStatus' => (array_key_exists($orderEl['order_status'], $availableStatuses)) ? $availableStatuses[$orderEl['order_status']] : $orderEl['order_status'],
                                ];
                                $statsList[$currentUserId][date('Y-m-d', strtotime($historyObj->done_at))][$orderEl['id']] = $tempArrayRecord;

                            }

                        }

                    }
                }

            }

        }

        $tempStatsList = $statsList;
        $statsList = [];
        foreach ($tempStatsList as $pickerKey => $dateData) {
            foreach ($dateData as $dateKey => $reportData) {
                foreach ($reportData as $recordKey => $recordData) {
                    $statsList[] = $recordData;
                }
            }
        }

        return $statsList;

    }

    public function getDriverOrderStats($region = '', $apiChannel = '', $driver = [], $feederFlag = '', $collVerifyFlag = '', $startDate = '', $endDate = '', $timeSlot = '', $datePurpose = '') {

        $statsList = [];

        $regionClean = (!is_null($region) && (trim($region) != '')) ? trim($region) : null;
        $apiChannelClean = (!is_null($apiChannel) && (trim($apiChannel) != '')) ? trim($apiChannel) : null;
        $driverClean = (!is_null($driver) && is_array($driver) && (count($driver) > 0)) ? $driver : null;
        $feederFlagClean = 2;
        if (!is_null($feederFlag) && (trim($feederFlag) != '') && (trim($feederFlag) == '1')) {
            $feederFlagClean = 1;
        } elseif (!is_null($feederFlag) && (trim($feederFlag) != '') && (trim($feederFlag) == '2')) {
            $feederFlagClean = 0;
        }
        $collVerifyFlagClean = 2;
        if (!is_null($collVerifyFlag) && (trim($collVerifyFlag) != '') && (trim($collVerifyFlag) == '1')) {
            $collVerifyFlagClean = SaleOrder::COLLECTION_VERIFIED_YES;
        } elseif (!is_null($collVerifyFlag) && (trim($collVerifyFlag) != '') && (trim($collVerifyFlag) == '2')) {
            $collVerifyFlagClean = SaleOrder::COLLECTION_VERIFIED_NO;
        }
        $startDateClean = (!is_null($startDate) && (trim($startDate) != '')) ? date('Y-m-d', strtotime(trim($startDate))) : null;
        $endDateClean = (!is_null($endDate) && (trim($endDate) != '')) ? date('Y-m-d', strtotime(trim($endDate))) : null;
        $timeSlotClean = (!is_null($timeSlot) && (trim($timeSlot) != '')) ? trim($timeSlot) : null;
        $datePurposeClean = (!is_null($datePurpose) && (trim($datePurpose) != '') && ((trim($datePurpose) == '1') || (trim($datePurpose) == '2'))) ? (int)trim($datePurpose) : 1;
        $fromDate = null;
        $toDate = null;
        if (!is_null($startDateClean) && !is_null($endDateClean)) {
            if ($endDateClean > $startDateClean) {
                $fromDate = $startDateClean;
                $toDate = $endDateClean;
            } else {
                $fromDate = $endDateClean;
                $toDate = $startDateClean;
            }
        }

        $filterableSaleOrderIds = [];
        $filterableUserOrderIds = [];

        $userRoleObj = new UserRole();
        $drivers = $userRoleObj->allDrivers();
        if(count($drivers->mappedUsers) > 0) {
            $driversArray = $drivers->mappedUsers->toArray();
            foreach($driversArray as $userEl) {

                if (!is_null($driverClean) && !in_array($userEl['id'], $driverClean)) {
                    continue;
                }

                $feederChecker = true;
                if ($feederFlagClean < 2) {
                    $feederChecker = false;
                    $driverRoleMap = UserRoleMap::select('*')
                        ->where('user_id', $userEl['id'])
                        ->where('is_feeder_driver', $feederFlagClean)
                        ->get();
                    if ($driverRoleMap && (count($driverRoleMap) > 0)) {
                        $feederChecker = true;
                    }
                }
                if ($feederChecker === false) {
                    continue;
                }

                $deliveredDataList = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                $canceledDataList = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                $currentDeliveryOrders = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                if ($deliveredDataList && (count($deliveredDataList) > 0)) {
                    $deliveredDataArray = $deliveredDataList->toArray();
                    $deliveredOrderIds = array_column($deliveredDataArray, 'order_id');
                    foreach($deliveredOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$userEl['id']][] = $orderIdEl;
                    }
                }

                if ($canceledDataList && (count($canceledDataList) > 0)) {
                    $canceledDataArray = $canceledDataList->toArray();
                    $canceledOrderIds = array_column($canceledDataArray, 'order_id');
                    foreach($canceledOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$userEl['id']][] = $orderIdEl;
                    }
                }

                if ($currentDeliveryOrders && (count($currentDeliveryOrders) > 0)) {
                    $currentDeliveryArray = $currentDeliveryOrders->toArray();
                    $currentDeliveryOrderIds = array_column($currentDeliveryArray, 'order_id');
                    foreach($currentDeliveryOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$userEl['id']][] = $orderIdEl;
                    }
                }

            }
        }

        if (count($filterableSaleOrderIds) > 0) {

            $chunkedArraySize = 5000;
            foreach (array_chunk($filterableSaleOrderIds, $chunkedArraySize) as $chunkedKey => $chunkedSaleOrderIds) {

                $orderRequest = SaleOrder::select('*');
                $orderRequest->whereIn('id', $chunkedSaleOrderIds);

                $emirates = $this->getAvailableRegionsList();
                if (!is_null($regionClean) && (trim($regionClean) != '')) {
                    $orderRequest->where('region_id', trim($regionClean));
                } else {
                    $orderRequest->whereIn('region_id', array_keys($emirates));
                }

                $availableApiChannels = $this->getAllAvailableChannels();
                if (!is_null($apiChannelClean) && (trim($apiChannelClean) != '')) {
                    $orderRequest->where('channel', trim($apiChannelClean));
                } else {
                    $orderRequest->whereIn('channel', array_keys($availableApiChannels));
                }

                $givenTimeSlots = $this->getDeliveryTimeSlots();
                if (!is_null($timeSlotClean) && (trim($timeSlotClean) != '') && (count($givenTimeSlots) > 0) && in_array(trim($timeSlotClean), $givenTimeSlots)) {
                    $orderRequest->where('delivery_time_slot', trim($timeSlotClean));
                } elseif (count($givenTimeSlots) > 0) {
                    $orderRequest->whereIn('delivery_time_slot', $givenTimeSlots);
                }

                if ($collVerifyFlagClean < 2) {
                    $orderRequest->where('is_amount_verified', $collVerifyFlagClean);
                }

                $orderList = $orderRequest->get();

                if ($orderList && (count($orderList) > 0)) {
                    $orderListArray = $orderList->toArray();
                    foreach($orderListArray as $orderEl) {

                        $deliveredData = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED)
                            ->limit(1)->get();

                        $canceledData = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED)
                            ->limit(1)->get();

                        $currentDriver = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY)
                            ->orderBy('done_at', 'desc')
                            ->limit(1)->get();

                        $deliveryHistory = null;
                        if ($currentDriver && (count($currentDriver) > 0)) {
                            $deliveryHistory = $currentDriver->first();
                        }

                        $historyObj = null;
                        if ($deliveredData && (count($deliveredData) > 0)) {
                            $historyObj = $deliveredData->first();
                        } elseif ($canceledData && (count($canceledData) > 0)) {
                            $historyObj = $canceledData->first();
                        } else {
                            $historyObj = $deliveryHistory;
                        }

                        $filterHistory = null;
                        if ($datePurposeClean == 1) {
                            $filterHistory = $historyObj;
                        } elseif ($datePurposeClean == 2) {
                            $filterHistory = $deliveryHistory;
                        }

                        if (!is_null($deliveryHistory) && !is_null($historyObj) && !is_null($filterHistory)) {

                            $canProceed = true;
                            if (!is_null($filterHistory->done_by) && !array_key_exists($filterHistory->done_by, $filterableUserOrderIds)) {
                                $canProceed = false;
                            }

                            if (!is_null($fromDate) && (date('Y-m-d', strtotime($fromDate)) > date('Y-m-d', strtotime($filterHistory->done_at)))) {
                                $canProceed = false;
                            }

                            if (!is_null($toDate) && (date('Y-m-d', strtotime($toDate)) < date('Y-m-d', strtotime($filterHistory->done_at)))) {
                                $canProceed = false;
                            }

                            if ($canProceed) {

                                $userElQ = User::select('*')
                                    ->where('id', $filterHistory->done_by)->get();
                                $userEl = ($userElQ) ? $userElQ->first() : $filterHistory->actionDoer;

                                $currentUserId = (!is_null($userEl)) ? $userEl->id : $historyObj->done_by;
                                $currentUserName = (!is_null($userEl)) ? $userEl->name : '[Deleted User]';
                                $currentUserActive = (!is_null($userEl) && ($userEl->mappedRole->first()->pivot->is_active == '1')) ? 'Yes': 'No';
                                $currentUserFeeder = (!is_null($userEl) && ($userEl->mappedRole->first()->pivot->is_feeder_driver == '1')) ? 'Yes': 'No';

                                $currentAssignCount = 0;
                                $currentDeliveryCount = 0;
                                $currentDeliveredCount = 0;
                                $currentCanceledCount = 0;
                                if (
                                    array_key_exists($currentUserId, $statsList)
                                    && array_key_exists(date('Y-m-d', strtotime($filterHistory->done_at)), $statsList[$currentUserId])
                                ) {
                                    $currentAssignCount = (int) $statsList[$currentUserId][date('Y-m-d', strtotime($filterHistory->done_at))]['assignedOrders'];
                                    $currentDeliveryCount = (int) $statsList[$currentUserId][date('Y-m-d', strtotime($filterHistory->done_at))]['deliveryOrders'];
                                    $currentDeliveredCount = (int) $statsList[$currentUserId][date('Y-m-d', strtotime($filterHistory->done_at))]['deliveredOrders'];
                                    $currentCanceledCount = (int) $statsList[$currentUserId][date('Y-m-d', strtotime($filterHistory->done_at))]['canceledOrders'];
                                }

                                $addToRecords = false;
                                if ($historyObj->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED) {
                                    $currentDeliveredCount++;
                                    $addToRecords = true;
                                } elseif ($historyObj->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED) {
                                    $currentCanceledCount++;
                                    $addToRecords = true;
                                } elseif ($historyObj->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY) {
                                    if ($orderEl['order_status'] == SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH) {
                                        $currentAssignCount++;
                                        $addToRecords = true;
                                    } elseif ($orderEl['order_status'] == SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY) {
                                        $currentDeliveryCount++;
                                        $addToRecords = true;
                                    }
                                }

                                if ($addToRecords) {

                                    $statsList[$currentUserId][date('Y-m-d', strtotime($filterHistory->done_at))] = [
                                        'driverId' => $currentUserId,
                                        'driver' => $currentUserName,
                                        'active' => $currentUserActive,
                                        'feeder' => $currentUserFeeder,
                                        'date' => date('Y-m-d', strtotime($filterHistory->done_at)),
                                        'assignedOrders' => $currentAssignCount,
                                        'deliveryOrders' => $currentDeliveryCount,
                                        'deliveredOrders' => $currentDeliveredCount,
                                        'canceledOrders' => $currentCanceledCount
                                    ];
                                }

                            }

                        }

                    }
                }

            }

        }

        $tempStatsList = $statsList;
        $statsList = [];
        foreach ($tempStatsList as $pickerKey => $dateData) {
            foreach ($dateData as $dateKey => $reportData) {
                $statsList[] = $reportData;
            }
        }

        return $statsList;

    }

    public function getDriverOrderStatsExcel($region = '', $apiChannel = '', $driver = [], $feederFlag = '', $collVerifyFlag = '', $startDate = '', $endDate = '', $timeSlot = '', $datePurpose = '') {

        $statsList = [];

        $regionClean = (!is_null($region) && (trim($region) != '')) ? trim($region) : null;
        $apiChannelClean = (!is_null($apiChannel) && (trim($apiChannel) != '')) ? trim($apiChannel) : null;
        $driverClean = (!is_null($driver) && is_array($driver) && (count($driver) > 0)) ? $driver : null;
        $feederFlagClean = 2;
        if (!is_null($feederFlag) && (trim($feederFlag) != '') && (trim($feederFlag) == '1')) {
            $feederFlagClean = 1;
        } elseif (!is_null($feederFlag) && (trim($feederFlag) != '') && (trim($feederFlag) == '2')) {
            $feederFlagClean = 0;
        }
        $collVerifyFlagClean = 2;
        if (!is_null($collVerifyFlag) && (trim($collVerifyFlag) != '') && (trim($collVerifyFlag) == '1')) {
            $collVerifyFlagClean = SaleOrder::COLLECTION_VERIFIED_YES;
        } elseif (!is_null($collVerifyFlag) && (trim($collVerifyFlag) != '') && (trim($collVerifyFlag) == '2')) {
            $collVerifyFlagClean = SaleOrder::COLLECTION_VERIFIED_NO;
        }
        $startDateClean = (!is_null($startDate) && (trim($startDate) != '')) ? date('Y-m-d', strtotime(trim($startDate))) : null;
        $endDateClean = (!is_null($endDate) && (trim($endDate) != '')) ? date('Y-m-d', strtotime(trim($endDate))) : null;
        $timeSlotClean = (!is_null($timeSlot) && (trim($timeSlot) != '')) ? trim($timeSlot) : null;
        $datePurposeClean = (!is_null($datePurpose) && (trim($datePurpose) != '') && ((trim($datePurpose) == '1') || (trim($datePurpose) == '2'))) ? (int)trim($datePurpose) : 1;
        $fromDate = null;
        $toDate = null;
        if (!is_null($startDateClean) && !is_null($endDateClean)) {
            if ($endDateClean > $startDateClean) {
                $fromDate = $startDateClean;
                $toDate = $endDateClean;
            } else {
                $fromDate = $endDateClean;
                $toDate = $startDateClean;
            }
        }

        $availableStatuses = $this->getAvailableStatuses();

        $filterableSaleOrderIds = [];
        $filterableUserOrderIds = [];

        $userRoleObj = new UserRole();
        $drivers = $userRoleObj->allDrivers();
        if(count($drivers->mappedUsers) > 0) {
            $driversArray = $drivers->mappedUsers->toArray();
            foreach($driversArray as $userEl) {

                if (!is_null($driverClean) && !in_array($userEl['id'], $driverClean)) {
                    continue;
                }

                $feederChecker = true;
                if ($feederFlagClean < 2) {
                    $feederChecker = false;
                    $driverRoleMap = UserRoleMap::select('*')
                        ->where('user_id', $userEl['id'])
                        ->where('is_feeder_driver', $feederFlagClean)
                        ->get();
                    if ($driverRoleMap && (count($driverRoleMap) > 0)) {
                        $feederChecker = true;
                    }
                }
                if ($feederChecker === false) {
                    continue;
                }

                $deliveredDataList = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                $canceledDataList = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                $currentDeliveryOrders = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                if ($deliveredDataList && (count($deliveredDataList) > 0)) {
                    $deliveredDataArray = $deliveredDataList->toArray();
                    $deliveredOrderIds = array_column($deliveredDataArray, 'order_id');
                    foreach($deliveredOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$userEl['id']][] = $orderIdEl;
                    }
                }

                if ($canceledDataList && (count($canceledDataList) > 0)) {
                    $canceledDataArray = $canceledDataList->toArray();
                    $canceledOrderIds = array_column($canceledDataArray, 'order_id');
                    foreach($canceledOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$userEl['id']][] = $orderIdEl;
                    }
                }

                if ($currentDeliveryOrders && (count($currentDeliveryOrders) > 0)) {
                    $currentDeliveryArray = $currentDeliveryOrders->toArray();
                    $currentDeliveryOrderIds = array_column($currentDeliveryArray, 'order_id');
                    foreach($currentDeliveryOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$userEl['id']][] = $orderIdEl;
                    }
                }

            }
        }

        if (count($filterableSaleOrderIds) > 0) {

            $chunkedArraySize = 5000;
            foreach (array_chunk($filterableSaleOrderIds, $chunkedArraySize) as $chunkedKey => $chunkedSaleOrderIds) {

                $orderRequest = SaleOrder::select('*');
                $orderRequest->whereIn('id', $chunkedSaleOrderIds);

                $emirates = $this->getAvailableRegionsList();
                if (!is_null($regionClean) && (trim($regionClean) != '')) {
                    $orderRequest->where('region_id', trim($regionClean));
                } else {
                    $orderRequest->whereIn('region_id', array_keys($emirates));
                }

                $availableApiChannels = $this->getAllAvailableChannels();
                if (!is_null($apiChannelClean) && (trim($apiChannelClean) != '')) {
                    $orderRequest->where('channel', trim($apiChannelClean));
                } else {
                    $orderRequest->whereIn('channel', array_keys($availableApiChannels));
                }

                $givenTimeSlots = $this->getDeliveryTimeSlots();
                if (!is_null($timeSlotClean) && (trim($timeSlotClean) != '') && (count($givenTimeSlots) > 0) && in_array(trim($timeSlotClean), $givenTimeSlots)) {
                    $orderRequest->where('delivery_time_slot', trim($timeSlotClean));
                } elseif (count($givenTimeSlots) > 0) {
                    $orderRequest->whereIn('delivery_time_slot', $givenTimeSlots);
                }

                if ($collVerifyFlagClean < 2) {
                    $orderRequest->where('is_amount_verified', $collVerifyFlagClean);
                }

                $orderList = $orderRequest->get();
                if ($orderList && (count($orderList) > 0)) {
                    $orderListArray = $orderList->toArray();
                    foreach($orderListArray as $orderEl) {

                        $deliveredData = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED)
                            ->limit(1)->get();

                        $canceledData = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED)
                            ->limit(1)->get();

                        $currentDriver = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY)
                            ->orderBy('done_at', 'desc')
                            ->limit(1)->get();

                        $deliveryHistory = null;
                        if ($currentDriver && (count($currentDriver) > 0)) {
                            $deliveryHistory = $currentDriver->first();
                        }

                        $historyObj = null;
                        if ($deliveredData && (count($deliveredData) > 0)) {
                            $historyObj = $deliveredData->first();
                        } elseif ($canceledData && (count($canceledData) > 0)) {
                            $historyObj = $canceledData->first();
                        } else {
                            $historyObj = $deliveryHistory;
                        }

                        $filterHistory = null;
                        if ($datePurposeClean == 1) {
                            $filterHistory = $historyObj;
                        } elseif ($datePurposeClean == 2) {
                            $filterHistory = $deliveryHistory;
                        }

                        if (!is_null($deliveryHistory) && !is_null($historyObj) && !is_null($filterHistory)) {

                            $canProceed = true;
                            if (!is_null($filterHistory->done_by) && !array_key_exists($filterHistory->done_by, $filterableUserOrderIds)) {
                                $canProceed = false;
                            }

                            if (!is_null($fromDate) && (date('Y-m-d', strtotime($fromDate)) > date('Y-m-d', strtotime($filterHistory->done_at)))) {
                                $canProceed = false;
                            }

                            if (!is_null($toDate) && (date('Y-m-d', strtotime($toDate)) < date('Y-m-d', strtotime($filterHistory->done_at)))) {
                                $canProceed = false;
                            }

                            if ($canProceed) {

                                $userElQ = User::select('*')
                                    ->where('id', $filterHistory->done_by)->get();
                                $userEl = ($userElQ) ? $userElQ->first() : $filterHistory->actionDoer;

                                $currentUserId = (!is_null($userEl)) ? $userEl->id : $historyObj->done_by;
                                $currentUserName = (!is_null($userEl)) ? $userEl->name : '[Deleted User]';
                                $currentUserActive = (!is_null($userEl) && ($userEl->mappedRole->first()->pivot->is_active == '1')) ? 'Yes': 'No';
                                $currentUserFeeder = (!is_null($userEl) && ($userEl->mappedRole->first()->pivot->is_feeder_driver == '1')) ? 'Yes': 'No';

                                $saleOrderExtraData = [
                                    'shipping_address' => [],
                                    'payment_data' => [],
                                    'paid_amount_collections' => [],
                                ];

                                $shippingAddressRequest = SaleOrderAddress::select('*')
                                    ->where('order_id', $orderEl['id'])
                                    ->where('type', 'shipping')
                                    ->limit(1)
                                    ->get();
                                if ($shippingAddressRequest && (count($shippingAddressRequest) > 0)) {
                                    $saleOrderExtraData['shipping_address'] = $shippingAddressRequest->first()->toArray();
                                }

                                $paymentDataRequest = SaleOrderPayment::select('*')
                                    ->where('order_id', $orderEl['id'])
                                    ->get();
                                if ($paymentDataRequest && (count($paymentDataRequest) > 0)) {
                                    $saleOrderExtraData['payment_data'] = $paymentDataRequest->toArray();
                                }

                                $paidAmountCollectionRequest = SaleOrderAmountCollection::select('*')
                                    ->where('order_id', $orderEl['id'])
                                    ->where('status',  SaleOrderAmountCollection::PAYMENT_COLLECTION_STATUS_PAID)
                                    ->get();
                                if ($paidAmountCollectionRequest && (count($paidAmountCollectionRequest) > 0)) {
                                    $saleOrderExtraData['paid_amount_collections'] = $paidAmountCollectionRequest->toArray();
                                }

                                $shipAddress = $saleOrderExtraData['shipping_address'];
                                $customerNameString = '';
                                $customerNameString .= (isset($shipAddress['first_name'])) ? $shipAddress['first_name'] : '';
                                $customerNameString .= (isset($shipAddress['last_name'])) ? ' ' . $shipAddress['last_name'] : '';
                                $customerContactString = (isset($shipAddress['contact_number'])) ? ' ' . $shipAddress['contact_number'] : '';
                                $shipAddressString = '';
                                $shipAddressString .= (isset($shipAddress['company'])) ? $shipAddress['company'] . ' ' : '';
                                $shipAddressString .= (isset($shipAddress['address_1'])) ? $shipAddress['address_1'] : '';
                                $shipAddressString .= (isset($shipAddress['address_2'])) ? ', ' . $shipAddress['address_2'] : '';
                                $shipAddressString .= (isset($shipAddress['address_3'])) ? ', ' . $shipAddress['address_3'] : '';
                                $shipAddressString .= (isset($shipAddress['city'])) ? ', ' . $shipAddress['city'] : '';
                                $shipAddressString .= (isset($shipAddress['region'])) ? ', ' . $shipAddress['region'] : '';
                                $shipAddressString .= (isset($shipAddress['post_code'])) ? ', ' . $shipAddress['post_code'] : '';

                                $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
                                $fixTotalPaidArray = ['adminpaymentmethod', 'ngeniusonline'];
                                $totalOrderValueOrig = (float)$orderEl['order_total'];
                                $totalCanceledValue = (!is_null($orderEl['canceled_total'])) ? (float)$orderEl['canceled_total'] : 0;
                                $totalOrderValue = $totalOrderValueOrig - $totalCanceledValue;
                                $totalDueValue = $orderEl['order_due'];
                                $initialPaidValue = (float)$orderEl['order_total'] - (float)$orderEl['order_due'];
                                if (in_array($saleOrderExtraData['payment_data'][0]['method'], $fixTotalDueArray)) {
                                    $totalDueValue = $totalOrderValue;
                                    $initialPaidValue = 0;
                                }
                                if (in_array($saleOrderExtraData['payment_data'][0]['method'], $fixTotalPaidArray)) {
                                    $totalDueValue = 0;
                                    $initialPaidValue = $totalOrderValue;
                                }

                                $paymentMethodTitle = '';
                                $payInfoLoopTargetLabel = 'method_title';
                                if (isset($saleOrderExtraData['payment_data'][0]['extra_info'])) {
                                    $paymentAddInfo = json5_decode($saleOrderExtraData['payment_data'][0]['extra_info'], true);
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

                                if (count($saleOrderExtraData['paid_amount_collections']) > 0) {
                                    foreach ($saleOrderExtraData['paid_amount_collections'] as $paidCollEl) {
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

                                $tempArrayRecord = [
                                    'driverId' => $currentUserId,
                                    'driver' => $currentUserName,
                                    'orderDeliveryDate' => date('Y-m-d', strtotime($orderEl['delivery_date'])),
                                    'driverAssignedDate' => date('Y-m-d', strtotime($deliveryHistory->done_at)),
                                    'driverDeliveryDate' => date('Y-m-d', strtotime($historyObj->done_at)),
                                    'orderRecordId' => $orderEl['id'],
                                    'orderId' => $orderEl['order_id'],
                                    'orderNumber' => "#" . $orderEl['increment_id'],
                                    'emirates' => $orderEl['region'],
                                    'customerName' => $customerNameString,
                                    'shippingAddress' => $shipAddressString,
                                    'customerContact' => $customerContactString,
                                    'orderStatus' => (array_key_exists($orderEl['order_status'], $availableStatuses)) ? $availableStatuses[$orderEl['order_status']] : $orderEl['order_status'],
                                    'orderTotal' => $totalOrderValue . " " . $orderEl['order_currency'],
                                    'paymentMethod' => (trim($paymentMethodTitle) != '') ? $paymentMethodTitle : 'Online',
                                    'paymentMethodCode' => $saleOrderExtraData['payment_data'][0]['method'],
                                    'initialPay' => $initialPaidValue . " " . $orderEl['order_currency'],
                                    'collectedAmount' => $totalCollectedAmount . " " . $orderEl['order_currency'],
                                    'totalPaid' => ($initialPaidValue + $totalCollectedAmount) . " " . $orderEl['order_currency'],
                                    'paymentStatus' => $paymentStatus,
                                    'collectionVerified' => $orderEl['is_amount_verified'],
                                    'collectionVerifiedAt' => $orderEl['amount_verified_at'],
                                    'collectionVerifiedBy' => $orderEl['amount_verified_by'],
                                ];
                                foreach ($amountCollectionData as $collectionKey => $collectionValue) {
                                    $tempArrayRecord[$collectionKey] = $collectionValue . " " . $orderEl['order_currency'];
                                }
                                $statsList[$currentUserId][date('Y-m-d', strtotime($filterHistory->done_at))][$orderEl['id']] = $tempArrayRecord;

                            }

                        }

                    }
                }

            }

        }

        $tempStatsList = $statsList;
        $statsList = [];
        foreach ($tempStatsList as $driverKey => $dateData) {
            foreach ($dateData as $dateKey => $reportData) {
                foreach ($reportData as $recordKey => $recordData) {
                    $statsList[] = $recordData;
                }
            }
        }

        return $statsList;

    }

    public function getFeederOrderStats($region = '', $apiChannel = '', $driver = [], $startDate = '', $endDate = '', $timeSlot = '') {

        $statsList = [];

        $regionClean = (!is_null($region) && (trim($region) != '')) ? trim($region) : null;
        $apiChannelClean = (!is_null($apiChannel) && (trim($apiChannel) != '')) ? trim($apiChannel) : null;
        $driverClean = (!is_null($driver) && is_array($driver) && (count($driver) > 0)) ? $driver : null;
        $startDateClean = (!is_null($startDate) && (trim($startDate) != '')) ? date('Y-m-d', strtotime(trim($startDate))) : null;
        $endDateClean = (!is_null($endDate) && (trim($endDate) != '')) ? date('Y-m-d', strtotime(trim($endDate))) : null;
        $timeSlotClean = (!is_null($timeSlot) && (trim($timeSlot) != '')) ? trim($timeSlot) : null;
        $fromDate = null;
        $toDate = null;
        if (!is_null($startDateClean) && !is_null($endDateClean)) {
            if ($endDateClean > $startDateClean) {
                $fromDate = $startDateClean;
                $toDate = $endDateClean;
            } else {
                $fromDate = $endDateClean;
                $toDate = $startDateClean;
            }
        }

        $filterableSaleOrderIds = [];
        $filterableUserOrderIds = [];

        $availableStatuses = $this->getAvailableStatuses();
        $availableApiChannels = $this->getAllAvailableChannels();

        $userRoleObj = new UserRole();
        $drivers = $userRoleObj->allDrivers();
        if(count($drivers->mappedUsers) > 0) {
            $driversArray = $drivers->mappedUsers->toArray();
            foreach($driversArray as $userEl) {

                $feederChecker = false;
                $driverRoleMap = UserRoleMap::select('*')
                    ->where('user_id', $userEl['id'])
                    ->where('is_feeder_driver', 1)
                    ->get();
                if ($driverRoleMap && (count($driverRoleMap) > 0)) {
                    $feederChecker = true;
                }
                if ($feederChecker === false) {
                    continue;
                }

                if (!is_null($driverClean) && !in_array($userEl['id'], $driverClean)) {
                    continue;
                }

                $deliveredDataList = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                $canceledDataList = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                $currentDeliveryOrders = SaleOrderProcessHistory::select('*')
                    ->where('done_by', $userEl['id'])
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY)
                    ->whereBetween('done_at', [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))])
                    ->get();

                if ($deliveredDataList && (count($deliveredDataList) > 0)) {
                    $deliveredDataArray = $deliveredDataList->toArray();
                    $deliveredOrderIds = array_column($deliveredDataArray, 'order_id');
                    foreach($deliveredOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$orderIdEl][$userEl['id']] = [
                            'id' => $userEl['id'],
                            'name' => $userEl['name'],
                        ];
                    }
                }

                if ($canceledDataList && (count($canceledDataList) > 0)) {
                    $canceledDataArray = $canceledDataList->toArray();
                    $canceledOrderIds = array_column($canceledDataArray, 'order_id');
                    foreach($canceledOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$orderIdEl][$userEl['id']] = [
                            'id' => $userEl['id'],
                            'name' => $userEl['name'],
                        ];
                    }
                }

                if ($currentDeliveryOrders && (count($currentDeliveryOrders) > 0)) {
                    $currentDeliveryArray = $currentDeliveryOrders->toArray();
                    $currentDeliveryOrderIds = array_column($currentDeliveryArray, 'order_id');
                    foreach($currentDeliveryOrderIds as $orderIdEl) {
                        $filterableSaleOrderIds[] = $orderIdEl;
                        $filterableUserOrderIds[$orderIdEl][$userEl['id']] = [
                            'id' => $userEl['id'],
                            'name' => $userEl['name'],
                        ];
                    }
                }

            }
        }

        if (count($filterableSaleOrderIds) > 0) {

            $chunkedArraySize = 5000;
            foreach (array_chunk($filterableSaleOrderIds, $chunkedArraySize) as $chunkedKey => $chunkedSaleOrderIds) {

                $orderRequest = SaleOrder::select('*');
                $orderRequest->whereIn('id', $chunkedSaleOrderIds);

                $emirates = $this->getAvailableRegionsList();
                if (!is_null($regionClean) && (trim($regionClean) != '')) {
                    $orderRequest->where('region_id', trim($regionClean));
                } else {
                    $orderRequest->whereIn('region_id', array_keys($emirates));
                }

                $availableApiChannels = $this->getAllAvailableChannels();
                if (!is_null($apiChannelClean) && (trim($apiChannelClean) != '')) {
                    $orderRequest->where('channel', trim($apiChannelClean));
                } else {
                    $orderRequest->whereIn('channel', array_keys($availableApiChannels));
                }

                $givenTimeSlots = $this->getDeliveryTimeSlots();
                if (!is_null($timeSlotClean) && (trim($timeSlotClean) != '') && (count($givenTimeSlots) > 0) && in_array(trim($timeSlotClean), $givenTimeSlots)) {
                    $orderRequest->where('delivery_time_slot', trim($timeSlotClean));
                } elseif (count($givenTimeSlots) > 0) {
                    $orderRequest->whereIn('delivery_time_slot', $givenTimeSlots);
                }

                $orderList = $orderRequest->get();

                if ($orderList && (count($orderList) > 0)) {
                    $orderListArray = $orderList->toArray();
                    foreach($orderListArray as $orderEl) {

                        $deliveredData = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED)
                            ->limit(1)->get();

                        $canceledData = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
                            ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED)
                            ->limit(1)->get();

                        $currentDriver = SaleOrderProcessHistory::select('*')
                            ->where('order_id', $orderEl['id'])
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

                            $canProceed = true;

                            /*if (!is_null($historyObj->done_by) && !array_key_exists($historyObj->done_by, $filterableUserOrderIds)) {
                                $canProceed = false;
                            }*/

                            if (!is_null($fromDate) && (date('Y-m-d', strtotime($fromDate)) > date('Y-m-d', strtotime($historyObj->done_at)))) {
                                $canProceed = false;
                            }

                            if (!is_null($toDate) && (date('Y-m-d', strtotime($toDate)) < date('Y-m-d', strtotime($historyObj->done_at)))) {
                                $canProceed = false;
                            }

                            if ($canProceed) {

                                $userElQ = User::select('*')
                                    ->where('id', $historyObj->done_by)->get();
                                $userEl = ($userElQ) ? $userElQ->first() : $historyObj->actionDoer;

                                $currentUserId = (!is_null($userEl)) ? $userEl->id : $historyObj->done_by;
                                $currentUserName = (!is_null($userEl)) ? $userEl->name : '[Deleted User]';
                                $currentUserActive = (!is_null($userEl) && ($userEl->mappedRole->first()->pivot->is_active == '1')) ? 'Yes': 'No';
                                $currentUserFeeder = (!is_null($userEl) && ($userEl->mappedRole->first()->pivot->is_feeder_driver == '1')) ? 'Yes': 'No';

                                $saleOrderExtraData = [
                                    'shipping_address' => [],
                                    'payment_data' => [],
                                    'paid_amount_collections' => [],
                                ];

                                $shippingAddressRequest = SaleOrderAddress::select('*')
                                    ->where('order_id', $orderEl['id'])
                                    ->where('type', 'shipping')
                                    ->limit(1)
                                    ->get();
                                if ($shippingAddressRequest && (count($shippingAddressRequest) > 0)) {
                                    $saleOrderExtraData['shipping_address'] = $shippingAddressRequest->first()->toArray();
                                }

                                $paymentDataRequest = SaleOrderPayment::select('*')
                                    ->where('order_id', $orderEl['id'])
                                    ->get();
                                if ($paymentDataRequest && (count($paymentDataRequest) > 0)) {
                                    $saleOrderExtraData['payment_data'] = $paymentDataRequest->toArray();
                                }

                                $paidAmountCollectionRequest = SaleOrderAmountCollection::select('*')
                                    ->where('order_id', $orderEl['id'])
                                    ->where('status',  SaleOrderAmountCollection::PAYMENT_COLLECTION_STATUS_PAID)
                                    ->get();
                                if ($paidAmountCollectionRequest && (count($paidAmountCollectionRequest) > 0)) {
                                    $saleOrderExtraData['paid_amount_collections'] = $paidAmountCollectionRequest->toArray();
                                }

                                $shipAddress = $saleOrderExtraData['shipping_address'];
                                $customerName = '';
                                $customerName .= (isset($shipAddress['first_name'])) ? $shipAddress['first_name'] . ' ' : '';
                                $customerName .= (isset($shipAddress['last_name'])) ? $shipAddress['last_name'] : '';
                                $customerContact = (isset($shipAddress['contact_number'])) ? $shipAddress['contact_number'] : '';
                                $shipAddressString = '';
                                $shipAddressString .= (isset($shipAddress['company'])) ? $shipAddress['company'] . ' ' : '';
                                $shipAddressString .= (isset($shipAddress['address_1'])) ? $shipAddress['address_1'] : '';
                                $shipAddressString .= (isset($shipAddress['address_2'])) ? ', ' . $shipAddress['address_2'] : '';
                                $shipAddressString .= (isset($shipAddress['address_3'])) ? ', ' . $shipAddress['address_3'] : '';
                                $shipAddressString .= (isset($shipAddress['city'])) ? ', ' . $shipAddress['city'] : '';
                                $shipAddressString .= (isset($shipAddress['region'])) ? ', ' . $shipAddress['region'] : '';
                                $shipAddressString .= (isset($shipAddress['post_code'])) ? ', ' . $shipAddress['post_code'] : '';

                                $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
                                $fixTotalPaidArray = ['adminpaymentmethod', 'ngeniusonline'];
                                $totalOrderValueOrig = (float)$orderEl['order_total'];
                                $totalCanceledValue = (!is_null($orderEl['canceled_total'])) ? (float)$orderEl['canceled_total'] : 0;
                                $totalOrderValue = $totalOrderValueOrig - $totalCanceledValue;
                                $totalDueValue = $orderEl['order_due'];
                                $initialPaidValue = (float)$orderEl['order_total'] - (float)$orderEl['order_due'];
                                if (in_array($saleOrderExtraData['payment_data'][0]['method'], $fixTotalDueArray)) {
                                    $totalDueValue = $totalOrderValue;
                                    $initialPaidValue = 0;
                                }
                                if (in_array($saleOrderExtraData['payment_data'][0]['method'], $fixTotalPaidArray)) {
                                    $totalDueValue = 0;
                                    $initialPaidValue = $totalOrderValue;
                                }

                                $paymentMethodTitle = '';
                                $payInfoLoopTargetLabel = 'method_title';
                                if (isset($saleOrderExtraData['payment_data'][0]['extra_info'])) {
                                    $paymentAddInfo = json5_decode($saleOrderExtraData['payment_data'][0]['extra_info'], true);
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

                                if (count($saleOrderExtraData['paid_amount_collections']) > 0) {
                                    foreach ($saleOrderExtraData['paid_amount_collections'] as $paidCollEl) {
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

                                $tempArrayRecord = [
                                    'driverId' => $currentUserId,
                                    'driver' => $currentUserName,
                                    'orderDeliveryDate' => date('Y-m-d', strtotime($orderEl['delivery_date'])),
                                    'driverDeliveryDate' => date('Y-m-d', strtotime($historyObj->done_at)),
                                    'driverDeliveryAt' => date('Y-m-d H:i:s', strtotime($historyObj->done_at)),
                                    'feedersInvolved' => (array_key_exists($orderEl['id'], $filterableUserOrderIds) && (count($filterableUserOrderIds[$orderEl['id']]) > 0)) ? $filterableUserOrderIds[$orderEl['id']] : [],
                                    'orderRecordId' => $orderEl['id'],
                                    'orderId' => $orderEl['order_id'],
                                    'orderNumber' => "#" . $orderEl['increment_id'],
                                    'emirates' => $orderEl['region'],
                                    'channel' => $availableApiChannels[$orderEl['channel']]['name'],
                                    'customerName' => $customerName,
                                    'customerContact' => $customerContact,
                                    'shippingAddress' => $shipAddressString,
                                    'orderStatus' => (array_key_exists($orderEl['order_status'], $availableStatuses)) ? $availableStatuses[$orderEl['order_status']] : $orderEl['order_status'],
                                    'orderTotal' => $totalOrderValue . " " . $orderEl['order_currency'],
                                    'paymentMethod' => (trim($paymentMethodTitle) != '') ? $paymentMethodTitle : 'Online',
                                    'paymentMethodCode' => $saleOrderExtraData['payment_data'][0]['method'],
                                    'initialPay' => $initialPaidValue . " " . $orderEl['order_currency'],
                                    'collectedAmount' => $totalCollectedAmount . " " . $orderEl['order_currency'],
                                    'totalPaid' => ($initialPaidValue + $totalCollectedAmount) . " " . $orderEl['order_currency'],
                                    'paymentStatus' => $paymentStatus,
                                    'collectionVerified' => $orderEl['is_amount_verified'],
                                    'collectionVerifiedAt' => $orderEl['amount_verified_at'],
                                    'collectionVerifiedBy' => $orderEl['amount_verified_by'],
                                ];
                                foreach ($amountCollectionData as $collectionKey => $collectionValue) {
                                    if ((float)$collectionValue > 0) {
                                        $tempArrayRecord[$collectionKey] = $collectionValue . " " . $orderEl['order_currency'];
                                    }
                                }
                                $statsList[$currentUserId][date('Y-m-d', strtotime($historyObj->done_at))][$orderEl['id']] = $tempArrayRecord;

                            }

                        }

                    }
                }

            }

        }

        $tempStatsList = $statsList;
        $statsList = [];
        foreach ($tempStatsList as $driverKey => $dateData) {
            foreach ($dateData as $dateKey => $reportData) {
                foreach ($reportData as $recordKey => $recordData) {
                    $statsList[] = $recordData;
                }
            }
        }

        return $statsList;

    }

    public function getYangoDriverOrderStats($region = [], $apiChannel = '', $orderStatus = [], $startDate = '', $endDate = '', $timeSlot = '', $datePurpose = '', $currentRole = '') {

        $statsList = [];

        $regionClean = (!is_null($region) && is_array($region) && (count($region) > 0)) ? $region : null;
        $apiChannelClean = (!is_null($apiChannel) && (trim($apiChannel) != '')) ? trim($apiChannel) : null;
        $orderStatusClean = (!is_null($orderStatus) && is_array($orderStatus) && (count($orderStatus) > 0)) ? $orderStatus : null;
        $startDateClean = (!is_null($startDate) && (trim($startDate) != '')) ? date('Y-m-d', strtotime(trim($startDate))) : null;
        $endDateClean = (!is_null($endDate) && (trim($endDate) != '')) ? date('Y-m-d', strtotime(trim($endDate))) : null;
        $timeSlotClean = (!is_null($timeSlot) && (trim($timeSlot) != '')) ? trim($timeSlot) : null;
        $datePurposeClean = (!is_null($datePurpose) && (trim($datePurpose) != '') && ((trim($datePurpose) == '1') || (trim($datePurpose) == '2'))) ? (int)trim($datePurpose) : 1;
        $fromDate = null;
        $toDate = null;
        if (!is_null($startDateClean) && !is_null($endDateClean)) {
            if ($endDateClean > $startDateClean) {
                $fromDate = $startDateClean;
                $toDate = $endDateClean;
            } else {
                $fromDate = $endDateClean;
                $toDate = $startDateClean;
            }
        }

        $filterableSaleOrderIds = [];
        $filterableUserOrderIds = [];

        $orderRequest = SaleOrder::select('*');

        $emirates = $this->getAvailableRegionsList();
        $regionKeys = array_keys($emirates);
        if (
            !is_null($regionClean)
            && is_array($regionClean)
            && (count($regionClean) > 0)
            && (array_intersect($regionClean, $regionKeys) == $regionClean)
        ) {
            $orderRequest->whereIn('region_id', $regionClean);
        } else {
            $orderRequest->whereIn('region_id', $regionKeys);
        }

        $availableApiChannels = $this->getAllAvailableChannels();
        if (!is_null($apiChannelClean) && (trim($apiChannelClean) != '')) {
            $orderRequest->where('channel', trim($apiChannelClean));
        } else {
            $orderRequest->whereIn('channel', array_keys($availableApiChannels));
        }

        $availableStatuses = [];
        if (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_ADMIN)) {
            $availableStatuses = $this->getAvailableStatuses();
        } elseif (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_SUPERVISOR)) {
            $availableStatuses = $this->getSupervisorsAllowedStatuses();
        } elseif (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_PICKER)) {
            $availableStatuses = $this->getPickersAllowedStatuses();
        } elseif (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_DRIVER)) {
            $availableStatuses = $this->getDriversAllowedStatuses();
        }

        if (count($availableStatuses) > 0) {
            $statusKeys = array_keys($availableStatuses);
            if (
                !is_null($orderStatusClean)
                && is_array($orderStatusClean)
                && (count($orderStatusClean) > 0)
                && (array_intersect($orderStatusClean, $statusKeys) == $orderStatusClean)
            ) {
                $orderRequest->whereIn('order_status', $orderStatusClean);
            } else {
                $orderRequest->whereIn('order_status', $statusKeys);
            }
        }

        $givenTimeSlots = $this->getDeliveryTimeSlots();
        if (!is_null($timeSlotClean) && (trim($timeSlotClean) != '') && (count($givenTimeSlots) > 0) && in_array(trim($timeSlotClean), $givenTimeSlots)) {
            $orderRequest->where('delivery_time_slot', trim($timeSlotClean));
        } elseif (count($givenTimeSlots) > 0) {
            $orderRequest->whereIn('delivery_time_slot', $givenTimeSlots);
        }

        if ($datePurposeClean == '1') {
            $orderRequest->whereBetween('delivery_date', [$fromDate, $toDate]);
        }

        $orderList = $orderRequest->get();
        if ($orderList && (count($orderList) > 0)) {
            $orderListArray = $orderList->toArray();
            foreach($orderListArray as $orderEl) {

                $saleOrderExtraData = [
                    'shipping_address' => [],
                    'payment_data' => [],
                    'paid_amount_collections' => [],
                ];

                $shippingAddressRequest = SaleOrderAddress::select('*')
                    ->where('order_id', $orderEl['id'])
                    ->where('type', 'shipping')
                    ->limit(1)
                    ->get();
                if ($shippingAddressRequest && (count($shippingAddressRequest) > 0)) {
                    $saleOrderExtraData['shipping_address'] = $shippingAddressRequest->first()->toArray();
                }

                $paymentDataRequest = SaleOrderPayment::select('*')
                    ->where('order_id', $orderEl['id'])
                    ->get();
                if ($paymentDataRequest && (count($paymentDataRequest) > 0)) {
                    $saleOrderExtraData['payment_data'] = $paymentDataRequest->toArray();
                }

                $paidAmountCollectionRequest = SaleOrderAmountCollection::select('*')
                    ->where('order_id', $orderEl['id'])
                    ->where('status',  SaleOrderAmountCollection::PAYMENT_COLLECTION_STATUS_PAID)
                    ->get();
                if ($paidAmountCollectionRequest && (count($paidAmountCollectionRequest) > 0)) {
                    $saleOrderExtraData['paid_amount_collections'] = $paidAmountCollectionRequest->toArray();
                }

                $shipAddress = $saleOrderExtraData['shipping_address'];
                $customerNameString = '';
                $customerNameString .= (isset($shipAddress['first_name'])) ? $shipAddress['first_name'] : '';
                $customerNameString .= (isset($shipAddress['last_name'])) ? ' ' . $shipAddress['last_name'] : '';
                $customerContactString = (isset($shipAddress['contact_number'])) ? ' ' . $shipAddress['contact_number'] : '';
                $shipAddressString = '';
                $shipAddressString .= (isset($shipAddress['company'])) ? $shipAddress['company'] . ' ' : '';
                $shipAddressString .= (isset($shipAddress['address_1'])) ? $shipAddress['address_1'] : '';
                $shipAddressString .= (isset($shipAddress['address_2'])) ? ', ' . $shipAddress['address_2'] : '';
                $shipAddressString .= (isset($shipAddress['address_3'])) ? ', ' . $shipAddress['address_3'] : '';
                $shipAddressString .= (isset($shipAddress['city'])) ? ', ' . $shipAddress['city'] : '';
                $shipAddressString .= (isset($shipAddress['region'])) ? ', ' . $shipAddress['region'] : '';
                $shipAddressString .= (isset($shipAddress['post_code'])) ? ', ' . $shipAddress['post_code'] : '';
                $shippingLatitude = (isset($shipAddress['latitude'])) ? ', ' . $shipAddress['latitude'] : '';
                $shippingLongitude = (isset($shipAddress['longitude'])) ? ', ' . $shipAddress['longitude'] : '';

                $timeSlotSplitter = explode('-', $orderEl['delivery_time_slot'], 2);
                $fromTimeSlot = trim($timeSlotSplitter[0]);
                $toTimeSlot = trim($timeSlotSplitter[1]);

                $fromSlotDateTime = new \DateTime($fromTimeSlot);
                $fromSlot24Format = $fromSlotDateTime->format('H:i');
                $fromSlot24FormatSplitter = explode(':', $fromSlot24Format);
                /*$fromSlot24FormatClean = (trim($fromSlot24FormatSplitter[1]) == '00') ? trim($fromSlot24FormatSplitter[0]) : trim($fromSlot24Format);*/
                $fromSlot24FormatClean = trim($fromSlot24Format);

                $toSlotDateTime = new \DateTime($toTimeSlot);
                $toSlot24Format = $toSlotDateTime->format('H:i');
                $toSlot24FormatSplitter = explode(':', $toSlot24Format);
                /*$toSlot24FormatClean = (trim($toSlot24FormatSplitter[1]) == '00') ? trim($toSlot24FormatSplitter[0]) : trim($toSlot24Format);*/
                $toSlot24FormatClean = trim($toSlot24Format);

                $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
                $fixTotalPaidArray = ['adminpaymentmethod', 'ngeniusonline'];
                $totalOrderValueOrig = (float)$orderEl['order_total'];
                $totalCanceledValue = (!is_null($orderEl['canceled_total'])) ? (float)$orderEl['canceled_total'] : 0;
                $totalOrderValue = $totalOrderValueOrig - $totalCanceledValue;
                $totalDueValue = $orderEl['order_due'];
                $initialPaidValue = (float)$orderEl['order_total'] - (float)$orderEl['order_due'];
                if (in_array($saleOrderExtraData['payment_data'][0]['method'], $fixTotalDueArray)) {
                    $totalDueValue = $totalOrderValue;
                    $initialPaidValue = 0;
                }
                if (in_array($saleOrderExtraData['payment_data'][0]['method'], $fixTotalPaidArray)) {
                    $totalDueValue = 0;
                    $initialPaidValue = $totalOrderValue;
                }

                $paymentMethodTitle = '';
                $payInfoLoopTargetLabel = 'method_title';
                if (isset($saleOrderExtraData['payment_data'][0]['extra_info'])) {
                    $paymentAddInfo = json5_decode($saleOrderExtraData['payment_data'][0]['extra_info'], true);
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

                if (count($saleOrderExtraData['paid_amount_collections']) > 0) {
                    foreach ($saleOrderExtraData['paid_amount_collections'] as $paidCollEl) {
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

                $tempArrayRecord = [
                    'orderDeliveryDate' => date('Y-m-d', strtotime($orderEl['delivery_date'])),
                    'orderDeliverySlot' => $orderEl['delivery_time_slot'],
                    'orderDeliverySlot24' => $fromSlot24FormatClean . ' - ' . $toSlot24FormatClean,
                    'orderDeliveryNote' => $orderEl['delivery_notes'],
                    'channel' => $availableApiChannels[$orderEl['channel']]['name'],
                    'orderRecordId' => $orderEl['id'],
                    'orderId' => $orderEl['order_id'],
                    'orderNumber' => "#" . $orderEl['increment_id'],
                    'emirates' => $orderEl['region'],
                    'customerName' => $customerNameString,
                    'shippingAddress' => $shipAddressString,
                    'shippingLatitude' => $shippingLatitude,
                    'shippingLongitude' => $shippingLongitude,
                    'customerContact' => $customerContactString,
                    'orderStatus' => (array_key_exists($orderEl['order_status'], $availableStatuses)) ? $availableStatuses[$orderEl['order_status']] : $orderEl['order_status'],
                    'orderTotal' => $totalOrderValue,
                    'paymentMethod' => (trim($paymentMethodTitle) != '') ? $paymentMethodTitle : 'Online',
                    'paymentMethodCode' => $saleOrderExtraData['payment_data'][0]['method'],
                    'initialPay' => $initialPaidValue,
                    'collectedAmount' => $totalCollectedAmount,
                    'totalPaid' => ($initialPaidValue + $totalCollectedAmount),
                    'totalDue' => $totalDueValue,
                    'orderCurrency' => $orderEl['order_currency'],
                    'paymentStatus' => $paymentStatus,
                    'collectionVerified' => $orderEl['is_amount_verified'],
                    'collectionVerifiedAt' => $orderEl['amount_verified_at'],
                    'collectionVerifiedBy' => $orderEl['amount_verified_by'],
                ];
                foreach ($amountCollectionData as $collectionKey => $collectionValue) {
                    $tempArrayRecord[$collectionKey] = $collectionValue . " " . $orderEl['order_currency'];
                }
                $statsList[date('Y-m-d', strtotime($orderEl['delivery_date']))][$orderEl['id']] = $tempArrayRecord;

            }
        }

        $tempStatsList = $statsList;
        $statsList = [];
        foreach ($tempStatsList as $dateKey => $reportData) {
            foreach ($reportData as $recordKey => $recordData) {
                $statsList[] = $recordData;
            }
        }

        return $statsList;

    }

    public function getFileUrl($path = '') {
        return $this->baseService->getFileUrl($path);
    }

    public function getUserImageUrl($path = '') {
        return $this->baseService->getFileUrl('media/images/users/' . $path);
    }

}
