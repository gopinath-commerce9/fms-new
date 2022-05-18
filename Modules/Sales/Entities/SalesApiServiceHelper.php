<?php

namespace Modules\Sales\Entities;

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

class SalesApiServiceHelper
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

    public function getCollectionVerifiedSaleOrders($verifiedDate = null, $limit = 100) {

        if (is_null($verifiedDate) || (trim($verifiedDate) == '') || ((bool)strtotime(trim($verifiedDate)) === false)) {
            return [];
        }

        $returnData = [];
        $returnDataCount = 0;

        $lastVerifiedDate = date('Y-m-d H:i:s', strtotime($verifiedDate));
        $limitClean = (is_numeric($limit)) ? (int)$limit : 100;

        $orderRequest = SaleOrder::select('*');

        $orderRequest->where('env', $this->getApiEnvironment());
        $orderRequest->where('channel', $this->getApiChannel());
        $orderRequest->where('is_amount_verified', 1);
        $orderRequest->where('amount_verified_at', '>', $lastVerifiedDate);

        $orderRequest->orderBy('amount_verified_at', 'asc');

        $orderList = $orderRequest->get();
        if ($orderList && (count($orderList) > 0)) {
            foreach($orderList as $orderEl) {

                if ($returnDataCount > $limitClean) {
                    continue;
                }

                $deliveredData = SaleOrderProcessHistory::select('*')
                    ->where('order_id', $orderEl->id)
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED)
                    ->limit(1)->get();

                $canceledData = SaleOrderProcessHistory::select('*')
                    ->where('order_id', $orderEl->id)
                    ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED)
                    ->limit(1)->get();

                $currentDriver = SaleOrderProcessHistory::select('*')
                    ->where('order_id', $orderEl->id)
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

                    $userElQ = User::select('*')
                        ->where('id', $historyObj->done_by)->get();
                    $userEl = ($userElQ) ? $userElQ->first() : $historyObj->actionDoer;

                    $orderEl->paymentData;
                    $orderEl->paidAmountCollections;
                    $saleOrderData = $orderEl->toArray();

                    $amountCollectionData = [];
                    foreach(SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS as $cMethod) {
                        $amountCollectionData[$cMethod] = 0;
                    }

                    if (
                        isset($saleOrderData['paid_amount_collections'])
                        && is_array($saleOrderData['paid_amount_collections'])
                        && (count($saleOrderData['paid_amount_collections']) > 0)
                    ) {
                        foreach ($saleOrderData['paid_amount_collections'] as $paidCollEl) {
                            $amountCollectionData[$paidCollEl['method']] += (float) $paidCollEl['amount'];
                        }
                    }

                    /*foreach ($amountCollectionData as $cMethod => $cAmount) {
                        if ((float)$cAmount > 0) {
                            $returnData[] = [
                                'driverCode' => $userEl->id,
                                'driverName' => $userEl->name,
                                'paymentMode' => ucwords($cMethod),
                                'orderNumber' => $saleOrderData['increment_id'],
                                'collectionAmount' => $cAmount,
                                'collectionCurrency' => $saleOrderData['order_currency'],
                                'collectionDate' => date('Y-m-d H:i:s', strtotime($historyObj->done_at)),
                                'collectionVerificationDate' => date('Y-m-d H:i:s', strtotime($saleOrderData['amount_verified_at'])),
                            ];
                            $returnDataCount++;
                        }
                    }*/

                    $paymentMethodString = '';
                    $collectedAmount = 0;
                    foreach ($amountCollectionData as $cMethod => $cAmount) {
                        if ((float)$cAmount > 0) {
                            $paymentMethodString .= ((trim($paymentMethodString) == '') ? '' : ' and ') . ucwords($cMethod);
                            $collectedAmount += (float) $cAmount;
                        }
                    }

                    $returnData[] = [
                        'driverCode' => $userEl->id,
                        'driverName' => $userEl->name,
                        'paymentMode' => $paymentMethodString,
                        'orderNumber' => $saleOrderData['increment_id'],
                        'collectionAmount' => $collectedAmount,
                        'collectionCurrency' => $saleOrderData['order_currency'],
                        'collectionDate' => date('Y-m-d H:i:s', strtotime($historyObj->done_at)),
                        'collectionVerificationDate' => date('Y-m-d H:i:s', strtotime($saleOrderData['amount_verified_at'])),
                    ];
                    $returnDataCount++;

                }

            }
        }

        return $returnData;

    }

}
