<?php


namespace Modules\Driver\Entities;

use Modules\Base\Entities\BaseServiceHelper;
use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrder;
use DB;
use Modules\Sales\Entities\SaleOrderPayment;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\Sales\Entities\SaleOrderStatusHistory;
use App\Models\User;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;
use Modules\API\Entities\ApiServiceHelper;

class DriverApiServiceHelper
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

    public function getDeliveryTimeSlots() {
        $statusList = $this->getDriversAllowedStatuses();
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

    public function isValidApiUser($userId = 0) {

        if (is_null($userId) || !is_numeric($userId) || ((int)$userId <= 0)) {
            return [
                'success' => false,
                'message' => 'Invalid User!',
                'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED,
            ];
        }

        $user = User::find($userId);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid User!',
                'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED,
            ];
        }

        $roleMapData = UserRoleMap::firstWhere('user_id', $user->id);
        if (!$roleMapData) {
            return [
                'success' => false,
                'message' => 'The User not assigned to any role!',
                'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED,
            ];
        }

        $mappedRoleId = $roleMapData->role_id;
        $roleData = UserRole::find($mappedRoleId);
        if (!$roleData) {
            return [
                'success' => false,
                'message' => 'The User not assigned to any role!',
                'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED,
            ];
        }

        if (!$roleData->isDriver()) {
            return [
                'success' => false,
                'message' => 'The User is not a Driver!',
                'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED,
            ];
        }

        return [
            'success' => true,
            'message' => 'Authorized User.',
            'error' => '',
            'httpStatus' => ApiServiceHelper::HTTP_STATUS_CODE_OK,
        ];

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

    public function getDriverOrders($region = '', $apiChannel = '', $status = [], $deliveryDate = '', $timeSlot = '') {

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

        $availableStatuses = $this->getDriversAllowedStatuses();
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

    public function changeSaleOrderStatus(SaleOrder $order = null, $orderStatus = '', $driverId = 0) {

        if (is_null($order)) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        $allowedCurrentStatuses = [
            SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH,
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY,
        ];
        if (!in_array($order->order_status, $allowedCurrentStatuses)) {
            return [
                'status' => false,
                'message' => 'Sale Order status cannot be changed!'
            ];
        }

        $driverModifiableStatuses = [
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY,
            SaleOrder::SALE_ORDER_STATUS_DELIVERED,
            SaleOrder::SALE_ORDER_STATUS_CANCELED,
        ];
        if (is_null($orderStatus) || (trim($orderStatus) == '') || !in_array(trim($orderStatus), $driverModifiableStatuses)) {
            return [
                'status' => false,
                'message' => 'Invalid Sale Order Status!'
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
            'state' => trim($orderStatus),
            'status' => trim($orderStatus)
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

            if (trim($orderStatus) === SaleOrder::SALE_ORDER_STATUS_DELIVERED) {
                $saleOrderProcessHistoryAssigned = (new SaleOrderProcessHistory())->create([
                    'order_id' => $order->id,
                    'action' => SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED,
                    'status' => 1,
                    'comments' => 'The Sale Order Id #' . $order->order_id . ' is delivered to customer.',
                    'extra_info' => null,
                    'done_by' => ($driverId !== 0) ? $driverId : null,
                    'done_at' => date('Y-m-d H:i:s'),
                ]);
            } elseif (trim($orderStatus) === SaleOrder::SALE_ORDER_STATUS_CANCELED) {
                $saleOrderProcessHistoryAssigned = (new SaleOrderProcessHistory())->create([
                    'order_id' => $order->id,
                    'action' => SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED,
                    'status' => 1,
                    'comments' => 'The Sale Order Id #' . $order->order_id . ' is canceled by the user for the customer.',
                    'extra_info' => null,
                    'done_by' => ($driverId !== 0) ? $driverId : null,
                    'done_at' => date('Y-m-d H:i:s'),
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

    public function changeSaleOrderAssignment(SaleOrder $order = null, $driverId = 0) {

        if (is_null($order)) {
            return [
                'status' => false,
                'message' => 'Sale Order is empty!'
            ];
        }

        if (is_null($driverId) || !is_numeric($driverId) || ((int)$driverId <= 0)) {
            return [
                'status' => false,
                'message' => 'Invalid driver!'
            ];
        }

        $allowedCurrentStatuses = [
            SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH,
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY,
        ];
        if (!in_array($order->order_status, $allowedCurrentStatuses)) {
            return [
                'status' => false,
                'message' => 'Sale Order status cannot be changed!'
            ];
        }

        $saleOrderProcessHistoryAssigner = (new SaleOrderProcessHistory())->create([
            'order_id' => $order->id,
            'action' => SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY_ASSIGN,
            'status' => 1,
            'comments' => 'The Sale Order Id #' . $order->order_id . ' is assigned for delivery.',
            'extra_info' => null,
            'done_by' => ($driverId !== 0) ? $driverId : null,
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

    }

}
