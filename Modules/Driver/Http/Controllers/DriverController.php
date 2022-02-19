<?php

namespace Modules\Driver\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Input;
use Modules\Driver\Entities\DriverServiceHelper;
use Modules\Sales\Entities\SaleOrder;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\Sales\Jobs\SaleOrderChannelImport;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
use Spipu\Html2Pdf\Html2Pdf;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return redirect()->route('driver.dashboard');
    }

    public function dashboard(Request $request)
    {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Dashboard';

        $serviceHelper = new DriverServiceHelper();
        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $selectedEmirate = (
            $request->has('emirate')
            && (trim($request->input('emirate')) != '')
            && array_key_exists(trim($request->input('emirate')), $emirates)
        ) ? trim($request->input('emirate')) : '859';

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getDriversAllowedStatuses();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();

        $driverOrders = $serviceHelper->getDriverOrders();
        $regionOrderCount = (!is_null($driverOrders)) ? count($driverOrders) : 0;

        return view('driver::dashboard', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'selectedEmirate',
            'regionOrderCount',
            'todayDate',
            'driverOrders',
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

        $serviceHelper = new DriverServiceHelper();
        $availableStatuses = $serviceHelper->getDriversAllowedStatuses();
        $currentChannel = $serviceHelper->getApiChannel();
        $currentEnv = $serviceHelper->getApiEnvironment();

        $targetOrder = SaleOrder::firstWhere('increment_id', $incrementId)
            ->where('env', $currentEnv)
            ->where('channel', $currentChannel)
            ->whereIn('order_status', array_keys($availableStatuses));
        if ($targetOrder) {
            $saleOrder = ($targetOrder instanceof SaleOrder) ? $targetOrder : $targetOrder->first();
            return redirect('/driver/order-view/' . $saleOrder->id);
        } else {
            return back()
                ->with('error', "Sale Order #" . $incrementId . " not found!");
        }

    }

    public function searchOrderByFilters(Request $request) {

        $serviceHelper = new DriverServiceHelper();

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
            && array_key_exists(trim($request->input('emirates_region')), $emirates)
        ) ? trim($request->input('emirates_region')) : '';

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $apiChannel = (
            $request->has('channel_filter')
            && (trim($request->input('channel_filter')) != '')
            && array_key_exists(trim($request->input('channel_filter')), $availableApiChannels)
        ) ? trim($request->input('channel_filter')) : '';

        $availableStatuses = $serviceHelper->getDriversAllowedStatuses();
        $orderStatus = (
            $request->has('order_status_filter')
            && (trim($request->input('order_status_filter')) != '')
            && array_key_exists(trim($request->input('order_status_filter')), $availableStatuses)
        ) ? trim($request->input('order_status_filter')) : '';

        $deliveryDate = (
            $request->has('delivery_date_filter')
            && (trim($request->input('delivery_date_filter')) != '')
        ) ? trim($request->input('delivery_date_filter')) : '';

        $deliverySlot = (
            $request->has('delivery_slot_filter')
            && (trim($request->input('delivery_slot_filter')) != '')
        ) ? trim($request->input('delivery_slot_filter')) : '';

        $filteredOrders = $serviceHelper->getDriverOrders($region, $apiChannel, $orderStatus, $deliveryDate, $deliverySlot);
        if (!$filteredOrders) {
            return response()->json([], 200);
        }

        $userId = 0;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $userId = (int)$sessionUser['id'];
        }

        $filteredOrderData = [];
        $totalRec = 0;
        $collectRecStart = $dtStart;
        $collectRecEnd = $collectRecStart + $dtPageLength;
        $currentRec = -1;
        foreach ($filteredOrders as $record) {
            $deliveryDriverData = $record->currentDriver;
            $canProceed = false;
            $driverDetail = null;
            if ($deliveryDriverData && (count($deliveryDriverData) > 0)) {
                foreach ($deliveryDriverData as $dDeliver) {
                    if (($userId > 0) && !is_null($dDeliver->done_by) && ((int)$dDeliver->done_by == $userId)) {
                        $canProceed = true;
                        $driverDetail = $dDeliver;
                    }
                }
            }
            if ($canProceed) {
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
                $emirateId = $record->region_id;
                $tempRecord['region'] = $emirates[$emirateId];
                $shipAddress = $record->shippingAddress;
                $tempRecord['customerName'] = $shipAddress->first_name . ' ' . $shipAddress->last_name;
                $tempRecord['deliveryDate'] = $record->delivery_date;
                $tempRecord['deliveryTimeSlot'] = $record->delivery_time_slot;
                $tempRecord['deliveryPickerTime'] = '';
                $tempRecord['deliveryDriverTime'] = '';
                $orderStatusId = $record->order_status;
                $tempRecord['orderStatus'] = $availableStatuses[$orderStatusId];
                $deliveryPickerData = $record->pickedData;
                $tempRecord['actions'] = url('/driver/order-view/' . $record->id);
                if ($deliveryPickerData) {
                    if ($deliveryPickerData->action == SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKED) {
                        $tempRecord['deliveryPickerTime'] = $serviceHelper->getFormattedTime($deliveryPickerData->done_at, 'F d, Y, h:i:s A');
                    }
                }
                if (!is_null($driverDetail)) {
                    $tempRecord['deliveryDriverTime'] = $serviceHelper->getFormattedTime($driverDetail->done_at, 'F d, Y, h:i:s A');
                }
                $filteredOrderData[] = $tempRecord;
            }
        }

        $returnData = [
            'draw' => $dtDraw,
            'recordsTotal' => $totalRec,
            'recordsFiltered' => $totalRec,
            'data' => $filteredOrderData
        ];

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
        $serviceHelper = new DriverServiceHelper();
        $availableStatuses = $serviceHelper->getDriversAllowedStatuses();
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

        return view('driver::order-view', compact(
            'pageTitle',
            'pageSubTitle',
            'saleOrderObj',
            'saleOrderData',
            'customerGroups',
            'vendorList',
            'serviceHelper',
            'orderStatuses'
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
            SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH,
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY
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

        $canProceed = false;
        if ($saleOrderObj->currentDriver && (count($saleOrderObj->currentDriver) > 0)) {
            $currentHistory = $saleOrderObj->currentDriver[0];
            if ($currentHistory->done_by === $processUserId) {
                $canProceed = true;
            }
        }
        if (!$canProceed) {
            return back()
                ->with('error', 'The Sale Order is not assigned to the user!');
        }

        $driverModifiableStatuses = [
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY,
            SaleOrder::SALE_ORDER_STATUS_DELIVERED,
            SaleOrder::SALE_ORDER_STATUS_CANCELED,
        ];
        $validator = Validator::make($request->all() , [
            'sale_order_status' => ['required', Rule::in($driverModifiableStatuses)],
        ], [
            'sale_order_status.required' => 'The Sale Order Status should not be empty.',
            'sale_order_status.in' => 'Invalid Sale Order Status.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator);
        }

        $postData = $validator->validated();
        $newStatus = $postData['sale_order_status'];

        $serviceHelper = new DriverServiceHelper();
        $returnResult = $serviceHelper->changeSaleOrderStatus($saleOrderObj, $newStatus, $processUserId);
        if ($returnResult['status']) {
            return redirect('driver/dashboard')->with('success', 'The Sale Order status is updated successfully!');
        } else {
            return redirect('driver/dashboard')->with('error', $returnResult['message']);
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

            $path = public_path('ktmt/media/logos/aanacart-favicon-final.png');
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $logoEncoded = 'data:image/' . $type . ';base64,' . base64_encode($data);

            $fulfilledBy = config('fms.fulfillment.done_by');

            $pdfContent = view('driver::print-label', compact('orderData', 'logoEncoded', 'fulfilledBy'))->render();

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
