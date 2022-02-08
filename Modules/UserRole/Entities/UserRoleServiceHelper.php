<?php


namespace Modules\UserRole\Entities;

use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrder;
use DB;
use \Exception;
use Modules\Base\Entities\BaseServiceHelper;
use App\Models\User;
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

    public function getPickerDeliveryTimeSlots() {
        $statusList = $this->getPickersAllowedStatuses();
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

        $userRoleObj = new UserRole();
        $pickers = $userRoleObj->allPickers();
        if(count($pickers->mappedUsers) > 0) {
            foreach($pickers->mappedUsers as $userEl) {

                if($userEl->saleOrderProcessHistory && (count($userEl->saleOrderProcessHistory) > 0)) {

                    foreach ($userEl->saleOrderProcessHistory as $processHistory) {
                        if ($processHistory->saleOrder) {

                            $currentSaleOrder = $processHistory->saleOrder;
                            $canProceed = true;

                            if (!is_null($regionClean) && ($currentSaleOrder->region_id != $regionClean)) {
                                $canProceed = false;
                            }

                            if (!is_null($apiChannelClean) && ($currentSaleOrder->channel != $apiChannelClean)) {
                                $canProceed = false;
                            }

                            if (!is_null($pickerClean) && ($userEl->id != $pickerClean)) {
                                $canProceed = false;
                            }

                            if (!is_null($fromDate) && (date('Y-m-d', strtotime($fromDate)) > date('Y-m-d', strtotime($processHistory->done_at)))) {
                                $canProceed = false;
                            }

                            if (!is_null($toDate) && (date('Y-m-d', strtotime($toDate)) < date('Y-m-d', strtotime($processHistory->done_at)))) {
                                $canProceed = false;
                            }

                            if ($canProceed) {

                                $currentAssignCount = 0;
                                $currentPickedCount = 0;
                                $currentHoldedCount = 0;
                                if (
                                    array_key_exists($userEl->id, $statsList)
                                    && array_key_exists(date('Y-m-d', strtotime($processHistory->done_at)), $statsList[$userEl->id])
                                ) {
                                    $currentAssignCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($processHistory->done_at))]['assignedOrders'];
                                    $currentPickedCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($processHistory->done_at))]['pickedOrders'];
                                    $currentHoldedCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($processHistory->done_at))]['holdedOrders'];
                                }

                                if ($processHistory->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKED) {
                                    $currentPickedCount++;
                                } elseif ($processHistory->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP) {
                                    if ($currentSaleOrder->order_status == SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED) {
                                        $currentAssignCount++;
                                    } elseif ($currentSaleOrder->order_status == SaleOrder::SALE_ORDER_STATUS_ON_HOLD) {
                                        $currentHoldedCount++;
                                    }
                                }

                                if (($currentPickedCount > 0) || ($currentAssignCount > 0) || ($currentHoldedCount > 0)) {
                                    $statsList[$userEl->id][date('Y-m-d', strtotime($processHistory->done_at))] = [
                                        'pickerId' => $userEl->id,
                                        'picker' => $userEl->name,
                                        'active' => ($userEl->pivot->is_active == '1') ? 'Yes' : 'No',
                                        'date' => date('Y-m-d', strtotime($processHistory->done_at)),
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

            $tempStatsList = $statsList;
            $statsList = [];
            foreach ($tempStatsList as $pickerKey => $dateData) {
                foreach ($dateData as $dateKey => $reportData) {
                    $statsList[] = $reportData;
                }
            }

        }

        return $statsList;

    }

    public function getDriverOrderStats($region = '', $apiChannel = '', $driver = '', $startDate = '', $endDate = '') {

        $statsList = [];

        $regionClean = (!is_null($region) && (trim($region) != '')) ? trim($region) : null;
        $apiChannelClean = (!is_null($apiChannel) && (trim($apiChannel) != '')) ? trim($apiChannel) : null;
        $driverClean = (!is_null($driver) && (trim($driver) != '')) ? trim($driver) : null;
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

        $userRoleObj = new UserRole();
        $drivers = $userRoleObj->allDrivers();
        if(count($drivers->mappedUsers) > 0) {
            foreach($drivers->mappedUsers as $userEl) {

                if($userEl->saleOrderProcessHistory && (count($userEl->saleOrderProcessHistory) > 0)) {

                    foreach ($userEl->saleOrderProcessHistory as $processHistory) {
                        if ($processHistory->saleOrder) {

                            $currentSaleOrder = $processHistory->saleOrder;
                            $canProceed = true;

                            if (!is_null($regionClean) && ($currentSaleOrder->region_id != $regionClean)) {
                                $canProceed = false;
                            }

                            if (!is_null($apiChannelClean) && ($currentSaleOrder->channel != $apiChannelClean)) {
                                $canProceed = false;
                            }

                            if (!is_null($driverClean) && ($userEl->id != $driverClean)) {
                                $canProceed = false;
                            }

                            if (!is_null($fromDate) && (date('Y-m-d', strtotime($fromDate)) > date('Y-m-d', strtotime($processHistory->done_at)))) {
                                $canProceed = false;
                            }

                            if (!is_null($toDate) && (date('Y-m-d', strtotime($toDate)) < date('Y-m-d', strtotime($processHistory->done_at)))) {
                                $canProceed = false;
                            }

                            if ($canProceed) {

                                $currentAssignCount = 0;
                                $currentDeliveryCount = 0;
                                $currentDeliveredCount = 0;
                                $currentCanceledCount = 0;
                                if (
                                    array_key_exists($userEl->id, $statsList)
                                    && array_key_exists(date('Y-m-d', strtotime($processHistory->done_at)), $statsList[$userEl->id])
                                ) {
                                    $currentAssignCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($processHistory->done_at))]['assignedOrders'];
                                    $currentDeliveryCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($processHistory->done_at))]['deliveryOrders'];
                                    $currentDeliveredCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($processHistory->done_at))]['deliveredOrders'];
                                    $currentCanceledCount = (int) $statsList[$userEl->id][date('Y-m-d', strtotime($processHistory->done_at))]['canceledOrders'];
                                }

                                if ($processHistory->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED) {
                                    $currentDeliveredCount++;
                                } elseif ($processHistory->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED) {
                                    $currentCanceledCount++;
                                } elseif ($processHistory->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY) {
                                    if ($currentSaleOrder->order_status == SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH) {
                                        $currentAssignCount++;
                                    } elseif ($currentSaleOrder->order_status == SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY) {
                                        $currentDeliveryCount++;
                                    }
                                }

                                if (($currentDeliveredCount > 0) || ($currentAssignCount > 0) || ($currentDeliveryCount > 0) || ($currentCanceledCount > 0)) {
                                    $statsList[$userEl->id][date('Y-m-d', strtotime($processHistory->done_at))] = [
                                        'driverId' => $userEl->id,
                                        'driver' => $userEl->name,
                                        'active' => ($userEl->pivot->is_active == '1') ? 'Yes' : 'No',
                                        'date' => date('Y-m-d', strtotime($processHistory->done_at)),
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

            $tempStatsList = $statsList;
            $statsList = [];
            foreach ($tempStatsList as $pickerKey => $dateData) {
                foreach ($dateData as $dateKey => $reportData) {
                    $statsList[] = $reportData;
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
