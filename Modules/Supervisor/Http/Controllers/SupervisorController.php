<?php

namespace Modules\Supervisor\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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

        $emirates = config('fms.emirates');
        $serviceHelper = new SupervisorServiceHelper();

        $selectedEmirate = (
            $request->has('emirate')
            && (trim($request->input('emirate')) != '')
            && array_key_exists(trim($request->input('emirate')), $emirates)
        ) ? trim($request->input('emirate')) : 'DXB';

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getSupervisorsAllowedStatuses();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();

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

        $targetOrder = SaleOrder::firstWhere('increment_id', $incrementId)
            ->where('env', $currentEnv)
            ->where('channel', $currentChannel)
            ->whereIn('order_status', array_keys($availableStatuses));
        if ($targetOrder) {
            return redirect('/supervisor/order-view/' . $targetOrder->id);
        } else {
            return back()
                ->with('error', "Sale Order #" . $incrementId . " not found!");
        }

    }

    public function searchOrderByFilters(Request $request) {

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

        $emirates = config('fms.emirates');
        $region = (
            $request->has('emirates_region')
            && (trim($request->input('emirates_region')) != '')
            && array_key_exists(trim($request->input('emirates_region')), $emirates)
        ) ? trim($request->input('emirates_region')) : '';

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

        $returnData = [];
        if ($methodAction == 'datatable') {
            $filteredOrders = $serviceHelper->getSupervisorOrders($region, $apiChannel, $orderStatus, $startDate, $endDate, $deliverySlot);
            if ($filteredOrders) {

                $filteredOrderData = [];
                $totalRec = 0;
                $collectRecStart = $dtStart;
                $collectRecEnd = $collectRecStart + $dtPageLength;
                $currentRec = -1;
                foreach ($filteredOrders as $record) {
                    $totalRec++;
                    $currentRec++;
                    if (($currentRec < $collectRecStart) || ($currentRec >= $collectRecEnd)) {
                        continue;
                    }
                    $tempRecord = [];
                    $tempRecord['recordId'] = $record->id;
                    $tempRecord['orderId'] = $record->order_id;
                    $tempRecord['incrementId'] = $record->increment_id;
                    $apiChannelId = $record->channel;
                    $tempRecord['channel'] = $availableApiChannels[$apiChannelId]['name'];
                    $emirateId = $record->region_code;
                    $tempRecord['region'] = $emirates[$emirateId];
                    $shipAddress = $record->shippingAddress;
                    $tempRecord['customerName'] = $shipAddress->first_name . ' ' . $shipAddress->last_name;
                    $tempRecord['deliveryDate'] = $record->delivery_date;
                    $tempRecord['deliveryTimeSlot'] = $record->delivery_time_slot;
                    $tempRecord['deliveryPicker'] = '';
                    $tempRecord['deliveryPickerTime'] = '';
                    $tempRecord['deliveryDriver'] = '';
                    $tempRecord['deliveryDriverTime'] = '';
                    $orderStatusId = $record->order_status;
                    $tempRecord['orderStatus'] = $availableStatuses[$orderStatusId];
                    $deliveryPickerData = $record->pickupData;
                    $deliveryDriverData = $record->deliveryData;
                    $tempRecord['actions'] = url('/supervisor/order-view/' . $record->id);
                    if ($deliveryPickerData && (count($deliveryPickerData) > 0)) {
                        $pickerDetail = $deliveryPickerData[0];
                        $tempRecord['deliveryPickerTime'] = $serviceHelper->getFormattedTime($pickerDetail->done_at, 'F d, Y, h:i:s A');
                        if ($pickerDetail->actionDoer) {
                            $tempRecord['deliveryPicker'] = $pickerDetail->actionDoer->name;
                        }
                    }
                    if ($deliveryDriverData && (count($deliveryDriverData) > 0)) {
                        $driverDetail = $deliveryDriverData[0];
                        $tempRecord['deliveryDriverTime'] = $serviceHelper->getFormattedTime($driverDetail->done_at, 'F d, Y, h:i:s A');
                        if ($driverDetail->actionDoer) {
                            $tempRecord['deliveryDriver'] = $driverDetail->actionDoer->name;
                        }
                    }
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

            $chartData = $serviceHelper->getSaleOrderStatusChartData($apiChannel, $region, $orderStatus, $startDate, $endDate, $deliverySlot);

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
                $xAxisData[] = $dateKey;
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

            $chartData = $serviceHelper->getSaleOrderSalesChartData($apiChannel, $region, $orderStatus, $startDate, $endDate, $deliverySlot);

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
                $xAxisData[] = $dateKey;
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
        if (array_key_exists('items', $customerGroupData)) {
            foreach($customerGroupData['items'] as $group) {
                $customerGroups[$group['id']] = $group['code'];
            }
        }

        $vendorList = [];
        if (session()->has('salesOrderVendorList')) {
            $vendorList = session()->get('salesOrderVendorList');
        } else {
            $vendorResponse = $serviceHelper->getVendorsList();
            foreach($vendorResponse as $vendor)
            {
                $vendorList[$vendor['vendor_id']] = $vendor['vendor_name'];
            }
            session()->put('salesOrderVendorList', $vendorList);
        }

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
            'drivers'
        ));

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

            $path = public_path('ktmt/media/logos/c9_logo_long.svg');
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $logoEncoded = 'data:image/' . $type . ';base64,' . base64_encode($data);

            $fulfilledBy = config('fms.fulfillment.done_by');

            $pdfContent = view('supervisor::print-label', compact('orderData', 'logoEncoded', 'fulfilledBy'))->render();

            $pdfName = "print-label-order-" . $saleOrderObj->increment_id . ".pdf";
            $outputMode = 'D';

            $html2pdf = new Html2Pdf($pdfOrientation, $pdfPaperSize, $pdfUseLang);
            $html2pdf->setDefaultFont($pdfDefaultFont);
            $html2pdf->writeHTML($pdfContent);

            $pdfOutput = $html2pdf->output($pdfName, $outputMode);

        } catch (Html2PdfException $e) {
            $html2pdf->clean();
            $formatter = new ExceptionFormatter($e);
            return back()
                ->with('error', $formatter->getMessage());
        }

    }

}
