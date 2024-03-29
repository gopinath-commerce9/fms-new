<?php


namespace Modules\Picker\Entities;

use Modules\Base\Entities\BaseServiceHelper;
use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrder;
use DB;
use \Exception;
use Modules\Sales\Entities\SaleOrderItem;
use Modules\Sales\Entities\SaleOrderPayment;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\Sales\Entities\SaleOrderStatusHistory;

class PickerServiceHelper
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

    public function getDeliveryTimeSlots() {
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

        $statusList = $this->getPickersAllowedStatuses();
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

    public function getPickerOrders($region = '', $apiChannel = '', $status = '', $deliveryDate = '', $timeSlot = '') {

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

        $availableStatuses = $this->getPickersAllowedStatuses();
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
                            'status' => array_key_exists('status', $historyEl) ? $historyEl['status'] : null,
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

}
