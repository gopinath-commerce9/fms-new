<?php

namespace Modules\Supervisor\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\API\Entities\ApiServiceHelper;
use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrderAddress;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Entities\SaleOrderItem;
use Modules\Sales\Entities\SalesRegion;
use Modules\Supervisor\Entities\SupervisorServiceHelper;
use Modules\Sales\Entities\SaleOrder;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\UserRole\Entities\UserRole;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;

class SupervisorController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return redirect()->route('supervisor.dashboard');
    }

    public function dashboard(Request $request)
    {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Dashboard';

        $serviceHelper = new SupervisorServiceHelper();
        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $selectedEmirate = (
            $request->has('emirate')
            && (trim($request->input('emirate')) != '')
            && array_key_exists(trim($request->input('emirate')), $emirates)
        ) ? trim($request->input('emirate')) : 'DXB';

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getSupervisorsAllowedStatuses();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();
        $deliveryZones = $serviceHelper->getDeliveryZones();

        $supervisorOrders = $serviceHelper->getSupervisorOrders();
        $regionOrderCount = (!is_null($supervisorOrders)) ? count($supervisorOrders) : 0;

        return view('supervisor::dashboard', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'selectedEmirate',
            'regionOrderCount',
            'todayDate',
            'supervisorOrders',
            'availableApiChannels',
            'availableStatuses',
            'deliveryTimeSlots',
            'deliveryZones',
            'serviceHelper'
        ));

    }

    public function searchOrderByIncrementId(Request $request) {

        $incrementId = (
            $request->has('order_number')
            && (trim($request->input('order_number')) != '')
        ) ? trim($request->input('order_number')) : '';

        if ($incrementId == '') {
            return back()
                ->with('error', "Requested Order Number value is invalid!");
        }

        $serviceHelper = new SupervisorServiceHelper();
        $availableStatuses = $serviceHelper->getSupervisorsAllowedStatuses();
        $currentChannel = $serviceHelper->getApiChannel();
        $currentEnv = $serviceHelper->getApiEnvironment();

        $targetOrderQ = SaleOrder::select('*');
        $targetOrderQ->where('increment_id', $incrementId);
        if (!is_null($currentEnv)) {
            $targetOrderQ->where('env', $currentEnv);
        }
        if (!is_null($currentChannel)) {
            $targetOrderQ->where('channel', $currentChannel);
        }
        if (count(array_keys($availableStatuses)) > 0) {
            $targetOrderQ->whereIn('order_status', array_keys($availableStatuses));
        }

        $targetOrder = $targetOrderQ->get();
        if ($targetOrder) {
            $saleOrder = ($targetOrder instanceof SaleOrder) ? $targetOrder : $targetOrder->first();
            if (is_null($saleOrder)) {
                return back()
                    ->with('error', "Sale Order #" . $incrementId . " not found!");
            }
            return redirect('/supervisor/order-view/' . $saleOrder->id);
        } else {
            return back()
                ->with('error', "Sale Order #" . $incrementId . " not found!");
        }

    }

    public function searchOrderByFilters(Request $request) {

        set_time_limit(600);
        ini_set("memory_limit","1024M");

        $serviceHelper = new SupervisorServiceHelper();

        $availableActions = ['datatable', 'status_chart', 'sales_chart'];
        $methodAction = (
            $request->has('action')
            && (trim($request->input('action')) != '')
            && in_array(trim($request->input('action')), $availableActions)
        ) ? trim($request->input('action')) : 'datatable';

        $dtDraw = (
            $request->has('draw')
            && (trim($request->input('draw')) != '')
        ) ? (int)trim($request->input('draw')) : 1;

        $dtStart = (
            $request->has('start')
            && (trim($request->input('start')) != '')
        ) ? (int)trim($request->input('start')) : 0;

        $dtPageLength = (
            $request->has('length')
            && (trim($request->input('length')) != '')
        ) ? (int)trim($request->input('length')) : 10;

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();
        $region = (
            $request->has('emirates_region')
            && (trim($request->input('emirates_region')) != '')
        ) ? explode(',', trim($request->input('emirates_region'))) : [];

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $apiChannel = (
            $request->has('channel_filter')
            && (trim($request->input('channel_filter')) != '')
            && array_key_exists(trim($request->input('channel_filter')), $availableApiChannels)
        ) ? trim($request->input('channel_filter')) : '';

        $availableStatuses = $serviceHelper->getSupervisorsAllowedStatuses();
        $orderStatus = (
            $request->has('order_status_filter')
            && (trim($request->input('order_status_filter')) != '')
            && array_key_exists(trim($request->input('order_status_filter')), $availableStatuses)
        ) ? trim($request->input('order_status_filter')) : '';

        $startDate = (
            $request->has('delivery_date_start_filter')
            && (trim($request->input('delivery_date_start_filter')) != '')
        ) ? trim($request->input('delivery_date_start_filter')) : date('Y-m-d');

        $endDate = (
            $request->has('delivery_date_end_filter')
            && (trim($request->input('delivery_date_end_filter')) != '')
        ) ? trim($request->input('delivery_date_end_filter')) : date('Y-m-d');

        $deliverySlot = (
            $request->has('delivery_slot_filter')
            && (trim($request->input('delivery_slot_filter')) != '')
        ) ? trim($request->input('delivery_slot_filter')) : '';

        $zone = (
            $request->has('region_zone')
            && (trim($request->input('region_zone')) != '')
        ) ? explode(',', trim($request->input('region_zone'))) : [];

        $returnData = [];
        if ($methodAction == 'datatable') {
            $filteredOrders = $serviceHelper->getSupervisorOrders($region, $apiChannel, $orderStatus, $startDate, $endDate, $deliverySlot, $zone);
            if ($filteredOrders) {

                $pickerStatues = [
                    SaleOrder::SALE_ORDER_STATUS_PENDING,
                    SaleOrder::SALE_ORDER_STATUS_PROCESSING,
                    SaleOrder::SALE_ORDER_STATUS_NGENIUS_COMPLETE,
                    SaleOrder::SALE_ORDER_STATUS_ON_HOLD,
                ];

                $driverStatues = [
                    SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH,
                    SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY,
                    SaleOrder::SALE_ORDER_STATUS_DELIVERED,
                ];

                $pickerListArray = [];
                $userRoleObj = new UserRole();
                $pickers = $userRoleObj->allPickers();
                if(count($pickers->mappedUsers) > 0) {
                    $pickerListArray = $pickers->mappedUsers->toArray();
                }

                $orderListArray = $filteredOrders->toArray();
                $orderListProperArray = [];

                $totalRec = 0;
                $collectRecStart = $dtStart;
                $collectRecEnd = $collectRecStart + $dtPageLength;
                $currentRec = -1;
                foreach ($orderListArray as $record) {
                    $totalRec++;
                    $currentRec++;
                    if (($currentRec < $collectRecStart) || ($currentRec >= $collectRecEnd)) {
                        continue;
                    }
                    $orderListProperArray[$record['id']] = $record;
                }

                $orderRecordIds = array_keys($orderListProperArray);

                $chunkedArraySize = 2000;
                foreach (array_chunk($orderRecordIds, $chunkedArraySize) as $chunkedKey => $chunkedSaleOrderIds) {

                    $shippingAddressRequestArray = SaleOrderAddress::select('*')
                        ->whereIn('order_id', $chunkedSaleOrderIds)
                        ->where('type', 'shipping')
                        ->get()->toArray();
                    foreach ($shippingAddressRequestArray as $currentRec) {
                        $orderListProperArray[$currentRec['order_id']]['shipping_address'] = $currentRec;
                    }

                    $currentDeliveryPickersArrayRequestArray = SaleOrderProcessHistory::select('*')
                        ->whereIn('id', function ($query) use ($chunkedSaleOrderIds) {
                            $query->select(DB::raw('MAX(id)'))
                                ->from(with(new SaleOrderProcessHistory)->getTable())
                                ->whereIn('order_id', $chunkedSaleOrderIds)
                                ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP)
                                ->groupBy('order_id');
                        })
                        ->with('actionDoer')->get()->toArray();
                    foreach ($currentDeliveryPickersArrayRequestArray as $currentRec) {
                        $orderListProperArray[$currentRec['order_id']]['current_picker'] = $currentRec;
                    }

                    $currentDeliveryDriversArrayRequestArray = SaleOrderProcessHistory::select('*')
                        ->whereIn('id', function ($query) use ($chunkedSaleOrderIds) {
                            $query->select(DB::raw('MAX(id)'))
                                ->from(with(new SaleOrderProcessHistory)->getTable())
                                ->whereIn('order_id', $chunkedSaleOrderIds)
                                ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY)
                                ->groupBy('order_id');
                        })
                        ->with('actionDoer')->get()->toArray();
                    foreach ($currentDeliveryDriversArrayRequestArray as $currentRec) {
                        $orderListProperArray[$currentRec['order_id']]['current_driver'] = $currentRec;
                    }

                }

                $filteredOrderData = [];
                foreach ($orderListProperArray as $orderIdKey => $record) {

                    $shipAddress = array_key_exists('shipping_address', $record) ?  $record['shipping_address'] : [];
                    $deliveryPickerData = array_key_exists('current_picker', $record) ?  $record['current_picker'] : [];
                    $deliveryDriverData = array_key_exists('current_driver', $record) ?  $record['current_driver'] : [];

                    $tempRecord = [];
                    $tempRecord['recordId'] = $record['id'];
                    $tempRecord['orderId'] = $record['order_id'];
                    $tempRecord['incrementId'] = $record['increment_id'];
                    $apiChannelId = $record['channel'];
                    $tempRecord['channel'] = $availableApiChannels[$apiChannelId]['name'];
                    $emirateId = $record['region_id'];
                    $tempRecord['region'] = $emirates[$emirateId];
                    $tempRecord['zone'] = ucwords($record['zone_id']);
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
                    $tempRecord['customerName'] = $customerName;
                    $tempRecord['customerAddress'] = $shipAddressString;
                    $tempRecord['deliveryDate'] = date('d-m-Y', strtotime($record['delivery_date']));
                    $tempRecord['deliveryTimeSlot'] = $record['delivery_time_slot'];
                    $tempRecord['deliveryPicker'] = '';
                    $tempRecord['deliveryPickerTime'] = '';
                    $tempRecord['deliveryDriver'] = '';
                    $tempRecord['deliveryDriverTime'] = '';
                    $orderStatusId = $record['order_status'];
                    $tempRecord['orderStatus'] = $availableStatuses[$orderStatusId];
                    $pickerSelectedId = '';
                    $pickerSelectedName = '';
                    $actionSection = '';
                    $actionSection .= '<a href="' . url('/supervisor/order-view/' . $record['id']) . '" class="btn btn-hover-bg-secondary btn-text-secondary btn-hover-text-black border-0" target="_blank">View Order</a>';
                    if ($deliveryPickerData && (count($deliveryPickerData) > 0)) {
                        $pickerDetail = $deliveryPickerData;
                        $tempRecord['deliveryPickerTime'] = $serviceHelper->getFormattedTime($pickerDetail['done_at'], 'F d, Y, h:i:s A');
                        if ($pickerDetail['action_doer']) {
                            $pickerSelectedId = $pickerDetail['action_doer']['id'];
                            $pickerSelectedName = $pickerDetail['action_doer']['name'];
                        }
                    }
                    if ($deliveryDriverData && (count($deliveryDriverData) > 0)) {
                        $driverDetail = $deliveryDriverData;
                        $tempRecord['deliveryDriverTime'] = $serviceHelper->getFormattedTime($driverDetail['done_at'], 'F d, Y, h:i:s A');
                        if ($driverDetail['action_doer']) {
                            $tempRecord['deliveryDriver'] = $driverDetail['action_doer']['name'];
                        }
                    }
                    $pickerValues = $pickerSelectedName;
                    if (in_array($record['order_status'], $pickerStatues)) {
                        $pickerValues = '<select class="form-control datatable-input sale-order-picker-assigner" id="picker_assigner_' . $record['id'] . '" name="picker_assigner_';
                        $pickerValues .= $record['id'] . '" data-order-id="' . $record['id'] . '" data-order-number="' . $record['increment_id'] . '" data-action-loc="' . url('/supervisor/assign-order-oms-status/' . $record['id']) . '" >';
                        $pickerValues .= '<option value="" '. (($pickerSelectedId == '') ? 'selected' : '') . ' >Unassigned</option>';
                        if(count($pickerListArray) > 0) {
                            foreach($pickerListArray as $userEl) {
                                if ($userEl['pivot']['is_active'] === UserRole::ROLE_USER_ACTIVE_YES) {
                                    $pickerValues .= '<option value="' . $userEl['id'] . '" '. (($pickerSelectedId == $userEl['id']) ? 'selected' : '') . ' >' . $userEl['name'] . '</option>';
                                }
                            }
                        }
                        $pickerValues .= '</select>';
                    }
                    if (in_array($record['order_status'], $driverStatues)) {
                        $actionSection .= '<a href="' . url('/supervisor/print-order-invoice/' . $record['id']) . '" class="btn btn-hover-bg-primary btn-text-primary btn-hover-text-white border-0">Print Invoice</a>';
                    }
                    $tempRecord['deliveryPicker'] = $pickerValues;
                    $tempRecord['actions'] = $actionSection;
                    $filteredOrderData[] = $tempRecord;
                }

                $returnData = [
                    'draw' => $dtDraw,
                    'recordsTotal' => $totalRec,
                    'recordsFiltered' => $totalRec,
                    'data' => $filteredOrderData
                ];

            } else {
                $returnData = [
                    'draw' => $dtDraw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ];
            }
        } elseif ($methodAction == 'status_chart') {

            $chartData = $serviceHelper->getSaleOrderStatusChartData($apiChannel, $region, $orderStatus, $startDate, $endDate, $deliverySlot, $zone);

            $xAxisData = [];
            $seriesData = [];
            $seriesNameArray = [];
            $seriesPointsArray = [];

            foreach ($chartData as $dateKey => $dateData) {
                foreach ($dateData as $statusKey => $statusValue) {
                    $seriesNameArray[$statusKey] = $statusKey;
                }
            }

            $nameArray = $seriesNameArray;
            $seriesNameArray = [];
            $orderStatuses = $serviceHelper->getSupervisorsAllowedStatuses();
            foreach ($orderStatuses as $statusKey => $statusValue) {
                if (array_key_exists($statusKey, $nameArray)) {
                    $seriesNameArray[$statusKey] = $statusValue;
                }
            }

            foreach ($chartData as $dateKey => $dateData) {
                $xAxisData[] = date('d-m-Y', strtotime($dateKey));
                foreach ($seriesNameArray as $statusKey => $statusValue) {
                    $seriesPointsArray[$statusKey][] = (array_key_exists($statusKey, $dateData)) ? $dateData[$statusKey]['total_orders'] : 0;
                }
            }

            foreach ($seriesNameArray as $statusKey => $statusData) {
                $seriesData[] = [
                    'name' => $statusData,
                    'data' => $seriesPointsArray[$statusKey]
                ];
            }

            $returnData = [
                'success' => true,
                'xaxis' => $xAxisData,
                'series' => $seriesData,
            ];

        } elseif ($methodAction == 'sales_chart') {

            $chartData = $serviceHelper->getSaleOrderSalesChartData($apiChannel, $region, $orderStatus, $startDate, $endDate, $deliverySlot, $zone);

            $xAxisData = [];
            $seriesData = [];
            $seriesNameArray = [];
            $seriesPointsArray = [];

            foreach ($chartData as $dateKey => $dateData) {
                foreach ($dateData as $currencyKey => $currencyData) {
                    $seriesNameArray[$currencyKey] = $currencyKey;
                }
            }
            foreach ($chartData as $dateKey => $dateData) {
                $xAxisData[] = date('d-m-Y', strtotime($dateKey));;
                foreach ($seriesNameArray as $currencyKey => $currencyData) {
                    $seriesPointsArray[$currencyKey][] = (array_key_exists($currencyKey, $dateData)) ? $dateData[$currencyKey]['total_sum'] : 0;
                }
            }

            foreach ($seriesNameArray as $statusKey => $statusData) {
                $seriesData[] = [
                    'name' => $statusData,
                    'data' => $seriesPointsArray[$statusKey]
                ];
            }

            $returnData = [
                'success' => true,
                'xaxis' => $xAxisData,
                'series' => $seriesData,
            ];

        }

        return response()->json($returnData, 200);

    }

    public function viewOrder($orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Sale Order #' . $saleOrderObj->increment_id;

        $orderStatuses = config('fms.order_statuses');
        $serviceHelper = new SupervisorServiceHelper();
        $availableStatuses = $serviceHelper->getSupervisorsAllowedStatuses();
        $statusKeys = array_keys($availableStatuses);
        if(!in_array($saleOrderObj->order_status, $statusKeys)) {
            return back()
                ->with('error', 'The Sale Order not accessible!');
        }

        $customerGroups = [];
        $customerGroupData = $serviceHelper->getCustomerGroups();
        if (is_array($customerGroupData) && (count($customerGroupData) > 0) && array_key_exists('items', $customerGroupData)) {
            foreach($customerGroupData['items'] as $group) {
                $customerGroups[$group['id']] = $group['code'];
            }
        }

        $vendorList = [];
        /*if (session()->has('salesOrderVendorList')) {
            $vendorList = session()->get('salesOrderVendorList');
        } else {
            $vendorResponse = $serviceHelper->getVendorsList();
            foreach($vendorResponse as $vendor)
            {
                $vendorList[$vendor['vendor_id']] = $vendor['vendor_name'];
            }
            session()->put('salesOrderVendorList', $vendorList);
        }*/

        $saleOrderObj->saleCustomer;
        $saleOrderObj->orderItems;
        $saleOrderObj->billingAddress;
        $saleOrderObj->shippingAddress;
        $saleOrderObj->paymentData;
        $saleOrderObj->statusHistory;
        $saleOrderObj->processHistory;
        if ($saleOrderObj->processHistory && (count($saleOrderObj->processHistory) > 0)) {
            foreach($saleOrderObj->processHistory as $processHistory) {
                $processHistory->actionDoer;
            }
        }
        $saleOrderData = $saleOrderObj->toArray();

        $userRoleObj = new UserRole();
        $pickers = $userRoleObj->allPickers();
        $drivers = $userRoleObj->allDrivers();

        $resyncStatuses = $serviceHelper->getResyncStatuses();

        $saleRegionDetails = SalesRegion::firstWhere('region_id', $saleOrderData['region_id']);

        if ($saleOrderObj->order_status == SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED) {
            return view('supervisor::prepare-order-view', compact(
                'pageTitle',
                'pageSubTitle',
                'saleOrderObj',
                'saleOrderData',
                'customerGroups',
                'vendorList',
                'serviceHelper',
                'orderStatuses',
                'pickers',
                'drivers',
                'resyncStatuses',
                'saleRegionDetails'
            ));
        } else {
            return view('supervisor::order-view', compact(
                'pageTitle',
                'pageSubTitle',
                'saleOrderObj',
                'saleOrderData',
                'customerGroups',
                'vendorList',
                'serviceHelper',
                'orderStatuses',
                'pickers',
                'drivers',
                'resyncStatuses',
                'saleRegionDetails'
            ));
        }

    }

    public function orderStatusChange(Request $request, $orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        $allowedStatuses = [
            SaleOrder::SALE_ORDER_STATUS_PENDING,
            SaleOrder::SALE_ORDER_STATUS_PROCESSING,
            SaleOrder::SALE_ORDER_STATUS_NGENIUS_COMPLETE,
            SaleOrder::SALE_ORDER_STATUS_ON_HOLD,
            SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH
        ];
        if (!in_array($saleOrderObj->order_status, $allowedStatuses)) {
            return back()
                ->with('error', 'The Sale Order Status cannot be changed!');
        }

        $userRoleObj = new UserRole();
        $pickers = $userRoleObj->allPickers();
        $drivers = $userRoleObj->allDrivers();

        $pickerIds = [];
        $driverIds = [];

        $serviceHelper = new SupervisorServiceHelper();
        if(count($pickers->mappedUsers) > 0) {
            foreach($pickers->mappedUsers as $userEl) {
                /*if(is_null($serviceHelper->isPickerAssigned($userEl))) {
                    $pickerIds[] = $userEl->id;
                }*/
                $pickerIds[] = $userEl->id;
            }
        }
        if(count($drivers->mappedUsers) > 0) {
            foreach($drivers->mappedUsers as $userEl) {
                /*if(is_null($serviceHelper->isDriverAssigned($userEl))) {
                    $driverIds[] = $userEl->id;
                }*/
                $driverIds[] = $userEl->id;
            }
        }

        $validator = Validator::make($request->all() , [
            'assign_pickup_to' => [
                Rule::requiredIf(function () use ($request, $saleOrderObj) {
                    return (
                        ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_PENDING)
                        || ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_PROCESSING)
                        || ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_NGENIUS_COMPLETE)
                        || ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_ON_HOLD)
                    );
                }),
                Rule::in($pickerIds)
            ],
            'assign_delivery_to' => [
                Rule::requiredIf(function () use ($request, $saleOrderObj) {
                    return $saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH;
                }),
                Rule::in($driverIds)
            ],
        ], [
            'assign_pickup_to.requiredIf' => 'The Picker is not selected.',
            'assign_pickup_to.in' => 'The selected Picker does not exist (or) is not available .',
            'assign_delivery_to.requiredIf' => 'The Driver is not selected.',
            'assign_delivery_to.in' => 'The selected Driver does not exist (or) is not available .',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator);
        }

        $processUserId = 0;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $processUserId = $sessionUser['id'];
        }

        $postData = $validator->validated();
        $assignedPickerId = (array_key_exists('assign_pickup_to', $postData)) ? $postData['assign_pickup_to'] : null;
        $assignedDriverId = (array_key_exists('assign_delivery_to', $postData)) ? $postData['assign_delivery_to'] : null;

        if ($assignedPickerId) {
            $assignedPicker = (!is_null($assignedPickerId)) ? User::find($assignedPickerId) : null;
            $returnResult = $serviceHelper->setOrderAsBeingPrepared($saleOrderObj, $assignedPicker->id, $processUserId);
            if ($returnResult) {
                return back()->with('success', 'The Sale Order is assigned to the Picker successfully!');
            } else {
                return back()->with('error', $returnResult['message']);
            }
        } elseif ($assignedDriverId) {
            $assignedDriver = (!is_null($assignedDriverId)) ? User::find($assignedDriverId) : null;
            $returnResult = $serviceHelper->assignOrderToDriver($saleOrderObj, $assignedDriver->id, $processUserId);
            if ($returnResult) {
                return back()->with('success', 'The Sale Order is assigned to the Driver successfully!');
            } else {
                return back()->with('error', $returnResult['message']);
            }
        } else {
            return back()->with('error', 'No process happened!');
        }

    }

    public function prepareOrderStatusChange(Request $request, $orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        $allowedStatuses = [
            SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED
        ];
        if (!in_array($saleOrderObj->order_status, $allowedStatuses)) {
            return back()
                ->with('error', 'The Sale Order Status cannot be changed!');
        }

        $processUserId = 0;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $processUserId = $sessionUser['id'];
        }

        $orderItemCount = count($saleOrderObj->orderItems);
        $allowedAvailabilityValues = [
            SaleOrderItem::STORE_AVAILABLE_YES,
            SaleOrderItem::STORE_AVAILABLE_NO
        ];

        $validator = Validator::make($request->all() , [
            'box_qty' => ['required', 'numeric', 'integer', 'min:1'],
            'store_availability' => ['required', 'array', 'max:' . $orderItemCount],
            'store_availability.*' => [Rule::in($allowedAvailabilityValues)],
        ], [
            'box_qty.required' => 'The Box Count should not be empty.',
            'box_qty.min' => 'The Box Count should be atleast :min.',
            'store_availability.required' => 'The Order Items are not checked for Store Availability.',
            'store_availability.*.in' => 'Some of the Order Items are not checked for Store Availability.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('box_qty'));
        }

        $postData = $validator->validated();
        $boxCount = $postData['box_qty'];
        $storeAvailabilityArray = $postData['store_availability'];

        $serviceHelper = new SupervisorServiceHelper();
        $pickerId = $processUserId;
        if ($saleOrderObj->currentPicker && (count($saleOrderObj->currentPicker) > 0)) {
            $currentHistory = $saleOrderObj->currentPicker[0];
            $pickerId = $currentHistory->done_by;
        }
        $returnResult = $serviceHelper->setOrderAsDispatchReady($saleOrderObj, $boxCount, $storeAvailabilityArray, $pickerId);
        if ($returnResult['status']) {
            return back()->with('success', 'The Sale Order status is updated successfully!');
        } else {
            return back()->with('error', $returnResult['message']);
        }

    }

    public function printOrderItemList($orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        try {

            $pdfOrientation = 'P';
            $pdfPaperSize = 'A4';
            $pdfUseLang = 'en';
            $pdfDefaultFont = 'Arial';

            $saleOrderObj->saleCustomer;
            $saleOrderObj->orderItems;
            $saleOrderObj->billingAddress;
            $saleOrderObj->shippingAddress;
            $saleOrderObj->paymentData;
            $saleOrderObj->statusHistory;
            $saleOrderObj->processHistory;
            $saleOrderObj->currentPicker;
            if ($saleOrderObj->currentPicker && (count($saleOrderObj->currentPicker) > 0)) {
                foreach ($saleOrderObj->currentPicker as $dPicker) {
                    $dPicker->actionDoer;
                }
            }
            if ($saleOrderObj->processHistory && (count($saleOrderObj->processHistory) > 0)) {
                foreach($saleOrderObj->processHistory as $processHistory) {
                    $processHistory->actionDoer;
                }
            }
            $orderData = $saleOrderObj->toArray();

            $path = public_path('ktmt/media/logos/aanacart-favicon-final.png');
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $logoEncoded = 'data:image/' . $type . ';base64,' . base64_encode($data);

            $fulfilledBy = config('fms.fulfillment.done_by');

            $pdfContent = view('supervisor::print-order-item-list', compact('orderData', 'logoEncoded', 'fulfilledBy'))->render();

            $pdfName = "print-item-list-order-" . $saleOrderObj->increment_id . ".pdf";
            $outputMode = 'D';

            $html2pdf = new Html2Pdf($pdfOrientation, $pdfPaperSize, $pdfUseLang);
            $html2pdf->setDefaultFont($pdfDefaultFont);
            $html2pdf->setTestTdInOnePage(false);
            $html2pdf->writeHTML($pdfContent);

            $pdfOutput = $html2pdf->output($pdfName, $outputMode);

        } catch (Html2PdfException $e) {
            $html2pdf->clean();
            $formatter = new ExceptionFormatter($e);
            return back()
                ->with('error', $formatter->getMessage());
        }

    }

    public function printOrderDeliveryKerabiya($orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        if ($saleOrderObj->is_kerabiya_delivery === SaleOrder::KERABIYA_DELIVERY_NO) {
            return back()
                ->with('error', 'The Sale Order is not synced to the Kerabiya Logistics!');
        }

        $apiService = new RestApiService();

        $source = $apiService->getKerabiyaApiUrl() . 'GetPdf/' . $saleOrderObj->kerabiya_awb_number;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $source,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'API-KEY: ' . $apiService->getKerabiyaApiKey()
            ]
        ]);
        $pdfResponse = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $fileName =  "aanacart_" . $saleOrderObj->increment_id . "_kerabiya_awb_" . $saleOrderObj->kerabiya_awb_number . ".pdf";
        $headers = array(
            "Content-type"              => "application/pdf",
            "Content-Disposition"       => "attachment; filename=$fileName",
            "Content-Transfer-Encoding" => "binary",
            "Pragma"                    => "no-cache",
            "Cache-Control"             => "must-revalidate, post-check=0, pre-check=0",
            "Expires"                   => "0"
        );

        $callback = function() use($pdfResponse) {
            $file = fopen('php://output', 'w');
            fputs($file, $pdfResponse);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);

    }

    public function printShippingLabel($orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        if($saleOrderObj->order_status !== SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH) {
            return back()
                ->with('error', 'Cannot print the Shipping Label of the Sale Order.!');
        }

        try {

            $pdfOrientation = 'P';
            $pdfPaperSize = 'A5';
            $pdfUseLang = 'en';
            $pdfDefaultFont = 'Arial';

            $saleOrderObj->saleCustomer;
            $saleOrderObj->orderItems;
            $saleOrderObj->billingAddress;
            $saleOrderObj->shippingAddress;
            $saleOrderObj->paymentData;
            $saleOrderObj->statusHistory;
            $saleOrderObj->processHistory;
            if ($saleOrderObj->processHistory && (count($saleOrderObj->processHistory) > 0)) {
                foreach($saleOrderObj->processHistory as $processHistory) {
                    $processHistory->actionDoer;
                }
            }
            $orderData = $saleOrderObj->toArray();

            $path = public_path('ktmt/media/logos/aanacart-favicon-final.png');
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $logoEncoded = 'data:image/' . $type . ';base64,' . base64_encode($data);

            $fulfilledBy = config('fms.fulfillment.done_by');

            $pdfContent = view('supervisor::print-label', compact('orderData', 'logoEncoded', 'fulfilledBy'))->render();

            $pdfName = "print-label-order-" . $saleOrderObj->increment_id . ".pdf";
            $outputMode = 'D';

            $html2pdf = new Html2Pdf($pdfOrientation, $pdfPaperSize, $pdfUseLang);
            $html2pdf->setDefaultFont($pdfDefaultFont);
            $html2pdf->setTestTdInOnePage(false);
            $html2pdf->writeHTML($pdfContent);

            $pdfOutput = $html2pdf->output($pdfName, $outputMode);

        } catch (Html2PdfException $e) {
            $html2pdf->clean();
            $formatter = new ExceptionFormatter($e);
            return back()
                ->with('error', $formatter->getMessage());
        }

    }

    public function printOrderInvoice($orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        $invoiceNotAllowedStatues = [
            SaleOrder::SALE_ORDER_STATUS_PENDING,
            SaleOrder::SALE_ORDER_STATUS_NGENIUS_PENDING,
            SaleOrder::SALE_ORDER_STATUS_PROCESSING,
            SaleOrder::SALE_ORDER_STATUS_NGENIUS_PROCESSING,
            SaleOrder::SALE_ORDER_STATUS_NGENIUS_COMPLETE,
            SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED,
            SaleOrder::SALE_ORDER_STATUS_ON_HOLD,
            SaleOrder::SALE_ORDER_STATUS_ORDER_UPDATED,
        ];
        if(in_array($saleOrderObj->order_status, $invoiceNotAllowedStatues)) {
            return back()
                ->with('error', 'Cannot print the Invoice of the Sale Order.!');
        }

        $serviceHelper = new SupervisorServiceHelper();
        $invoiceResult = $serviceHelper->getInvoiceDetails($saleOrderObj);
        $fetchedOrderResult = $serviceHelper->getServerOrderDetails($saleOrderObj);
        if ($invoiceResult['status'] === false) {
            return back()
                ->with('error', 'Cannot print the Invoice of the Sale Order. ' . $invoiceResult['message']);
        }

        try {

            $invoiceData = $invoiceResult['invoiceData'];
            $saleOrderObj->refresh();

            $pdfOrientation = 'P';
            $pdfPaperSize = 'A4';
            $pdfUseLang = 'en';
            $pdfDefaultFont = 'Arial';

            $saleOrderObj->saleCustomer;
            $saleOrderObj->orderItems;
            $saleOrderObj->billingAddress;
            $saleOrderObj->shippingAddress;
            $saleOrderObj->paymentData;
            $saleOrderObj->statusHistory;
            $saleOrderObj->processHistory;
            if ($saleOrderObj->processHistory && (count($saleOrderObj->processHistory) > 0)) {
                foreach($saleOrderObj->processHistory as $processHistory) {
                    $processHistory->actionDoer;
                }
            }
            $orderData = $saleOrderObj->toArray();

            $storeCredits = 0;
            $storeCreditCheckArray = [
                [
                    'amstorecredit_invoiced_amount',
                    'amstorecredit_amount',
                ]
            ];
            if ($fetchedOrderResult['status'] === true) {
                $fetchedOrderData = $fetchedOrderResult['orderData'];
                $orderExtData = array_key_exists('extension_attributes', $fetchedOrderData) ? $fetchedOrderData['extension_attributes'] : [];
                foreach ($storeCreditCheckArray as $mainLoopKey => $mainLoopEl) {
                    $tempStoreCredit = null;
                    foreach ($mainLoopEl as $secondaryLoopKey => $secondaryLoopEl) {
                        if (is_null($tempStoreCredit) && array_key_exists($secondaryLoopEl, $orderExtData) && ((float)trim($orderExtData[$secondaryLoopEl]) > 0)) {
                            $tempStoreCredit = (float)trim($orderExtData[$secondaryLoopEl]);
                        }
                    }
                    if (!is_null($tempStoreCredit)) {
                        $storeCredits += $tempStoreCredit;
                    }
                }
            }
            $orderData['storeCredits'] = $storeCredits;

            /*$logoPath = public_path('ktmt/media/logos/aanacart-favicon-final.png');*/
            $logoPath = public_path('ktmt/media/logos/aanacart-logo.png');
            $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoImageData = file_get_contents($logoPath);
            $logoEncoded = 'data:image/' . $logoType . ';base64,' . base64_encode($logoImageData);

            $companyInfo = config('fms.company_info');

            $pdfContent = view('supervisor::print-invoice', compact('orderData', 'invoiceData', 'logoEncoded', 'companyInfo', 'serviceHelper'))->render();

            $pdfName = "print-invoice-order-" . $saleOrderObj->increment_id . ".pdf";
            $outputMode = 'D';

            $html2pdf = new Html2Pdf($pdfOrientation, $pdfPaperSize, $pdfUseLang);
            $html2pdf->setDefaultFont($pdfDefaultFont);
            $html2pdf->setTestTdInOnePage(false);
            $html2pdf->writeHTML($pdfContent);

            $pdfOutput = $html2pdf->output($pdfName, $outputMode);

        } catch (Html2PdfException $e) {
            $html2pdf->clean();
            $formatter = new ExceptionFormatter($e);
            return back()
                ->with('error', $formatter->getMessage());
        }

    }

    public function syncOrderDeliveryKerabiya($orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        $invoiceNotAllowedStatues = [
            SaleOrder::SALE_ORDER_STATUS_PENDING,
            SaleOrder::SALE_ORDER_STATUS_NGENIUS_PENDING,
            SaleOrder::SALE_ORDER_STATUS_PROCESSING,
            SaleOrder::SALE_ORDER_STATUS_NGENIUS_PROCESSING,
            SaleOrder::SALE_ORDER_STATUS_NGENIUS_COMPLETE,
            SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED,
            SaleOrder::SALE_ORDER_STATUS_ON_HOLD,
            SaleOrder::SALE_ORDER_STATUS_ORDER_UPDATED,
        ];
        if(in_array($saleOrderObj->order_status, $invoiceNotAllowedStatues)) {
            return back()
                ->with('error', 'Cannot sync to the Kerabiya Logistics for the Sale Order!');
        }

        if ($saleOrderObj->is_kerabiya_delivery === SaleOrder::KERABIYA_DELIVERY_YES) {
            return back()
                ->with('error', 'Already synced to the Kerabiya Logistics for the Sale Order!');
        }

        $saleRegionDetails = SalesRegion::firstWhere('region_id', $saleOrderObj->region_id);
        if ($saleRegionDetails->kerabiya_access === SalesRegion::KERABIYA_ACCESS_DISABLED) {
            return back()
                ->with('error', 'The Region of the Sale Order currently does not support Kerabiya Logistics!');
        }

        $processUserId = 0;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $processUserId = $sessionUser['id'];
        }

        $serviceHelper = new SupervisorServiceHelper();
        $kerabiyaResult = $serviceHelper->syncOrderToKerabiya($saleOrderObj, $processUserId);
        if ($kerabiyaResult['status'] === false) {
            return back()
                ->with('error', 'Cannot sync the Sale Order to the Kerabiya Logistics. ' . $kerabiyaResult['message']);
        }

        return back()->with('success', 'The Sale Order is synced to the Kerabiya Logistics successfully!');

    }

    public function orderResync($orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order Id input is invalid!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order does not exist!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $serviceHelper = new SupervisorServiceHelper();
        $availableStatuses = $serviceHelper->getResyncStatuses();

        if (!in_array($saleOrderObj->order_status, array_keys($availableStatuses))) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order cannot resync!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $processUserId = 0;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $processUserId = $sessionUser['id'];
        }

        $resyncResult = $serviceHelper->resyncSaleOrderFromServer($saleOrderObj, $processUserId);

        if ($resyncResult['status'] === false) {
            return response()->json([
                'success' => false,
                'message' => $resyncResult['message'],
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'The Sale Order resynced and updated!',
        ], ApiServiceHelper::HTTP_STATUS_CODE_OK);

    }

    public function setOrderOmsStatus(Request $request, $orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order Id input is invalid!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order does not exist!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $allowedStatuses = [
            SaleOrder::SALE_ORDER_STATUS_PENDING,
            SaleOrder::SALE_ORDER_STATUS_PROCESSING,
            SaleOrder::SALE_ORDER_STATUS_NGENIUS_COMPLETE,
            SaleOrder::SALE_ORDER_STATUS_ON_HOLD,
            SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH
        ];
        if (!in_array($saleOrderObj->order_status, $allowedStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order Status cannot be changed!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $userRoleObj = new UserRole();
        $pickers = $userRoleObj->allPickers();
        $drivers = $userRoleObj->allDrivers();

        $pickerIds = [];
        $driverIds = [];

        $serviceHelper = new SupervisorServiceHelper();
        if(count($pickers->mappedUsers) > 0) {
            foreach($pickers->mappedUsers as $userEl) {
                /*if(is_null($serviceHelper->isPickerAssigned($userEl))) {
                    $pickerIds[] = $userEl->id;
                }*/
                $pickerIds[] = $userEl->id;
            }
        }
        if(count($drivers->mappedUsers) > 0) {
            foreach($drivers->mappedUsers as $userEl) {
                /*if(is_null($serviceHelper->isDriverAssigned($userEl))) {
                    $driverIds[] = $userEl->id;
                }*/
                $driverIds[] = $userEl->id;
            }
        }

        $validator = Validator::make($request->all() , [
            'picker' => [
                Rule::requiredIf(function () use ($request, $saleOrderObj) {
                    return (
                        ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_PENDING)
                        || ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_PROCESSING)
                        || ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_NGENIUS_COMPLETE)
                        || ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_ON_HOLD)
                    );
                }),
                Rule::in($pickerIds)
            ],
            'driver' => [
                Rule::requiredIf(function () use ($request, $saleOrderObj) {
                    return $saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH;
                }),
                Rule::in($driverIds)
            ],
        ], [
            'picker.requiredIf' => 'The Picker is not selected.',
            'picker.in' => 'The selected Picker does not exist (or) is not available .',
            'driver.requiredIf' => 'The Driver is not selected.',
            'driver.in' => 'The selected Driver does not exist (or) is not available .',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(" | ", $validator->errors()->all()),
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $processUserId = 0;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $processUserId = $sessionUser['id'];
        }

        $postData = $validator->validated();
        $assignedPickerId = (array_key_exists('picker', $postData)) ? $postData['picker'] : null;
        $assignedDriverId = (array_key_exists('driver', $postData)) ? $postData['driver'] : null;

        if ($assignedPickerId) {
            $assignedPicker = (!is_null($assignedPickerId)) ? User::find($assignedPickerId) : null;
            $returnResult = $serviceHelper->setOrderAsBeingPrepared($saleOrderObj, $assignedPicker->id, $processUserId);
            if ($returnResult['status']) {
                return response()->json([
                    'success' => true,
                    'data'    => [],
                    'message' => 'The Sale Order is assigned to the Picker successfully!',
                ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $returnResult['message'],
                ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
            }
        } elseif ($assignedDriverId) {
            $assignedDriver = (!is_null($assignedDriverId)) ? User::find($assignedDriverId) : null;
            $returnResult = $serviceHelper->assignOrderToDriver($saleOrderObj, $assignedDriver->id, $processUserId);
            if ($returnResult['status']) {
                return response()->json([
                    'success' => true,
                    'data'    => [],
                    'message' => 'The Sale Order is assigned to the Driver successfully!',
                ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $returnResult['message'],
                ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No process happened!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

    }

}
