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

    public function getPickerOrderStats($region = '', $apiChannel = '', $picker = '', $startDate = '', $endDate = '') {

        $statsList = [];

        $regionClean = (!is_null($region) && (trim($region) != '')) ? trim($region) : null;
        $apiChannelClean = (!is_null($apiChannel) && (trim($apiChannel) != '')) ? trim($apiChannel) : null;
        $pickerClean = (!is_null($picker) && (trim($picker) != '')) ? trim($picker) : null;
        $startDateClean = (!is_null($startDate) && (trim($startDate) != '')) ? date('Y-m-d', strtotime(trim($startDate))) : null;
        $endDateClean = (!is_null($endDate) && (trim($endDate) != '')) ? date('Y-m-d', strtotime(trim($endDate))) : null;
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

                if (!is_null($pickerClean) && ($userEl['id'] != $pickerClean)) {
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

        $orderRequest = SaleOrder::select('*');
        $orderRequest->whereIn('id', $filterableSaleOrderIds);

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

                        $currentAssignCount = 0;
                        $currentPickedCount = 0;
                        $currentHoldedCount = 0;
                        if (
                            array_key_exists($userEl->id, $statsList)
                            && array_key_exists(date('Y-m-d', strtotime($historyObj->done_at)), $statsList[$userEl->id])
                        ) {
                            $currentAssignCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($historyObj->done_at))]['assignedOrders'];
                            $currentPickedCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($historyObj->done_at))]['pickedOrders'];
                            $currentHoldedCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($historyObj->done_at))]['holdedOrders'];
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

                        if (($currentPickedCount > 0) || ($currentAssignCount > 0) || ($currentHoldedCount > 0)) {
                            $statsList[$userEl->id][date('Y-m-d', strtotime($historyObj->done_at))] = [
                                'pickerId' => $userEl->id,
                                'picker' => $userEl->name,
                                'active' => ($userEl->mappedRole->first()->pivot->is_active == '1') ? 'Yes' : 'No',
                                'date' => date('Y-m-d', strtotime($historyObj->done_at)),
                                'assignedOrders' => $currentAssignCount,
                                'pickedOrders' => $currentPickedCount,
                                'holdedOrders' => $currentHoldedCount
                            ];
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

    public function getDriverOrderStats($region = '', $apiChannel = '', $driver = [], $feederFlag = '', $startDate = '', $endDate = '', $timeSlot = '', $datePurpose = '') {

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

        $orderRequest = SaleOrder::select('*');
        $orderRequest->whereIn('id', $filterableSaleOrderIds);

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

                        $currentAssignCount = 0;
                        $currentDeliveryCount = 0;
                        $currentDeliveredCount = 0;
                        $currentCanceledCount = 0;
                        if (
                            array_key_exists($userEl->id, $statsList)
                            && array_key_exists(date('Y-m-d', strtotime($filterHistory->done_at)), $statsList[$userEl->id])
                        ) {
                            $currentAssignCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($filterHistory->done_at))]['assignedOrders'];
                            $currentDeliveryCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($filterHistory->done_at))]['deliveryOrders'];
                            $currentDeliveredCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($filterHistory->done_at))]['deliveredOrders'];
                            $currentCanceledCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($filterHistory->done_at))]['canceledOrders'];
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

                            $statsList[$userEl->id][date('Y-m-d', strtotime($filterHistory->done_at))] = [
                                'driverId' => $userEl->id,
                                'driver' => $userEl->name,
                                'active' => ($userEl->mappedRole->first()->pivot->is_active == '1') ? 'Yes' : 'No',
                                'feeder' => ($userEl->mappedRole->first()->pivot->is_feeder_driver == '1') ? 'Yes' : 'No',
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

        $tempStatsList = $statsList;
        $statsList = [];
        foreach ($tempStatsList as $pickerKey => $dateData) {
            foreach ($dateData as $dateKey => $reportData) {
                $statsList[] = $reportData;
            }
        }

        return $statsList;

    }

    public function getDriverOrderStatsExcel($region = '', $apiChannel = '', $driver = [], $feederFlag = '', $startDate = '', $endDate = '', $timeSlot = '', $datePurpose = '') {

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
                    /*->where('done_by', $userEl['id'])*/
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

        $orderRequest = SaleOrder::select('*');
        $orderRequest->whereIn('id', $filterableSaleOrderIds);

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
                        $shipAddressString = '';
                        $shipAddressString .= (isset($shipAddress['company'])) ? $shipAddress['company'] . ' ' : '';
                        $shipAddressString .= (isset($shipAddress['address_1'])) ? $shipAddress['address_1'] : '';
                        $shipAddressString .= (isset($shipAddress['address_2'])) ? ', ' . $shipAddress['address_2'] : '';
                        $shipAddressString .= (isset($shipAddress['address_3'])) ? ', ' . $shipAddress['address_3'] : '';
                        $shipAddressString .= (isset($shipAddress['city'])) ? ', ' . $shipAddress['city'] : '';
                        $shipAddressString .= (isset($shipAddress['region'])) ? ', ' . $shipAddress['region'] : '';
                        $shipAddressString .= (isset($shipAddress['post_code'])) ? ', ' . $shipAddress['post_code'] : '';

                        $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
                        $totalOrderValue = $orderEl['order_total'];
                        $totalDueValue = $orderEl['order_due'];
                        $initialPaidValue = (float)$orderEl['order_total'] - (float)$orderEl['order_due'];
                        if (in_array($saleOrderExtraData['payment_data'][0]['method'], $fixTotalDueArray)) {
                            $totalDueValue = $totalOrderValue;
                            $initialPaidValue = 0;
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
                            'driverId' => $userEl->id,
                            'driver' => $userEl->name,
                            'orderDeliveryDate' => date('Y-m-d', strtotime($orderEl['delivery_date'])),
                            'driverAssignedDate' => date('Y-m-d', strtotime($deliveryHistory->done_at)),
                            'driverDeliveryDate' => date('Y-m-d', strtotime($historyObj->done_at)),
                            'orderRecordId' => $orderEl['id'],
                            'orderId' => $orderEl['order_id'],
                            'orderNumber' => "#" . $orderEl['increment_id'],
                            'emirates' => $orderEl['region'],
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
                            $tempArrayRecord[$collectionKey] = $collectionValue . " " . $orderEl['order_currency'];
                        }
                        $statsList[$userEl->id][date('Y-m-d', strtotime($filterHistory->done_at))][$orderEl['id']] = $tempArrayRecord;

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

        $orderRequest = SaleOrder::select('*');
        $orderRequest->whereIn('id', $filterableSaleOrderIds);

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
                        $shipAddressString = '';
                        $shipAddressString .= (isset($shipAddress['company'])) ? $shipAddress['company'] . ' ' : '';
                        $shipAddressString .= (isset($shipAddress['address_1'])) ? $shipAddress['address_1'] : '';
                        $shipAddressString .= (isset($shipAddress['address_2'])) ? ', ' . $shipAddress['address_2'] : '';
                        $shipAddressString .= (isset($shipAddress['address_3'])) ? ', ' . $shipAddress['address_3'] : '';
                        $shipAddressString .= (isset($shipAddress['city'])) ? ', ' . $shipAddress['city'] : '';
                        $shipAddressString .= (isset($shipAddress['region'])) ? ', ' . $shipAddress['region'] : '';
                        $shipAddressString .= (isset($shipAddress['post_code'])) ? ', ' . $shipAddress['post_code'] : '';

                        $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
                        $totalOrderValue = $orderEl['order_total'];
                        $totalDueValue = $orderEl['order_due'];
                        $initialPaidValue = (float)$orderEl['order_total'] - (float)$orderEl['order_due'];
                        if (in_array($saleOrderExtraData['payment_data'][0]['method'], $fixTotalDueArray)) {
                            $totalDueValue = $totalOrderValue;
                            $initialPaidValue = 0;
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
                            'driverId' => $userEl->id,
                            'driver' => $userEl->name,
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
                        $statsList[$userEl->id][date('Y-m-d', strtotime($historyObj->done_at))][$orderEl['id']] = $tempArrayRecord;

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

    public function getFileUrl($path = '') {
        return $this->baseService->getFileUrl($path);
    }

    public function getUserImageUrl($path = '') {
        return $this->baseService->getFileUrl('media/images/users/' . $path);
    }

}
