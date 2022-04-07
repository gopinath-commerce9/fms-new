<?php

namespace Modules\Picker\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Picker\Entities\PickerServiceHelper;
use Modules\Sales\Entities\SaleOrder;
use Modules\Sales\Entities\SaleOrderItem;
use Modules\UserRole\Entities\UserRole;

class PickerController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return redirect()->route('picker.dashboard');
    }

    public function dashboard(Request $request)
    {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Dashboard';

        $serviceHelper = new PickerServiceHelper();
        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $selectedEmirate = (
            $request->has('emirate')
            && (trim($request->input('emirate')) != '')
            && array_key_exists(trim($request->input('emirate')), $emirates)
        ) ? trim($request->input('emirate')) : '859';

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getPickersAllowedStatuses();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();

        $pickerOrders = $serviceHelper->getPickerOrders();
        $regionOrderCount = (!is_null($pickerOrders)) ? count($pickerOrders) : 0;

        return view('picker::dashboard', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'selectedEmirate',
            'regionOrderCount',
            'todayDate',
            'pickerOrders',
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

        $serviceHelper = new PickerServiceHelper();
        $availableStatuses = $serviceHelper->getPickersAllowedStatuses();
        $currentChannel = $serviceHelper->getApiChannel();
        $currentEnv = $serviceHelper->getApiEnvironment();

        $userId = 0;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $userId = (int)$sessionUser['id'];
        }

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
            $deliveryPickerData = $saleOrder->currentPicker;
            $canProceed = false;
            if ($deliveryPickerData && (count($deliveryPickerData) > 0)) {
                foreach ($deliveryPickerData as $dPicker) {
                    if (($userId > 0) && !is_null($dPicker->done_by) && ((int)$dPicker->done_by == $userId)) {
                        $canProceed = true;
                    }
                }
            }
            if ($canProceed) {
                return redirect('/picker/order-view/' . $saleOrder->id);
            } else {
                return back()
                    ->with('error', "Sale Order #" . $incrementId . " not accessible!");
            }

        } else {
            return back()
                ->with('error', "Sale Order #" . $incrementId . " not found!");
        }

    }

    public function searchOrderByFilters(Request $request) {

        $serviceHelper = new PickerServiceHelper();

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

        $availableStatuses = $serviceHelper->getPickersAllowedStatuses();
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

        $filteredOrders = $serviceHelper->getPickerOrders($region, $apiChannel, $orderStatus, $deliveryDate, $deliverySlot);
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
            $deliveryPickerData = $record->currentPicker;
            $canProceed = false;
            $pickerDetail = null;
            if ($deliveryPickerData && (count($deliveryPickerData) > 0)) {
                foreach ($deliveryPickerData as $dPicker) {
                    if (($userId > 0) && !is_null($dPicker->done_by) && ((int)$dPicker->done_by == $userId)) {
                        $canProceed = true;
                        $pickerDetail = $dPicker;
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
                $shipAddressString = '';
                $shipAddressString .= (isset($shipAddress->company)) ? $shipAddress->company . ' ' : '';
                $shipAddressString .= (isset($shipAddress->address_1)) ? $shipAddress->address_1 : '';
                $shipAddressString .= (isset($shipAddress->address_2)) ? ', ' . $shipAddress->address_2 : '';
                $shipAddressString .= (isset($shipAddress->address_3)) ? ', ' . $shipAddress->address_3 : '';
                $shipAddressString .= (isset($shipAddress->city)) ? ', ' . $shipAddress->city : '';
                $shipAddressString .= (isset($shipAddress->region)) ? ', ' . $shipAddress->region : '';
                $shipAddressString .= (isset($shipAddress->post_code)) ? ', ' . $shipAddress->post_code : '';
                $tempRecord['customerName'] = $shipAddress->first_name . ' ' . $shipAddress->last_name;
                $tempRecord['customerAddress'] = $shipAddressString;
                $tempRecord['deliveryDate'] = date('d-m-Y', strtotime($record->delivery_date));
                $tempRecord['deliveryTimeSlot'] = $record->delivery_time_slot;
                $tempRecord['deliveryPickerTime'] = '';
                $orderStatusId = $record->order_status;
                $tempRecord['orderStatus'] = $availableStatuses[$orderStatusId];
                $tempRecord['actions'] = url('/picker/order-view/' . $record->id);
                if (!is_null($pickerDetail)) {
                    $tempRecord['deliveryPickerTime'] = $serviceHelper->getFormattedTime($pickerDetail->done_at, 'F d, Y, h:i:s A');
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
        $serviceHelper = new PickerServiceHelper();
        $availableStatuses = $serviceHelper->getPickersAllowedStatuses();
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

        return view('picker::order-view', compact(
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

        $canProceed = false;
        if ($saleOrderObj->currentPicker && (count($saleOrderObj->currentPicker) > 0)) {
            $currentHistory = $saleOrderObj->currentPicker[0];
            if ($currentHistory->done_by === $processUserId) {
                $canProceed = true;
            }
        }
        if (!$canProceed) {
            return back()
                ->with('error', 'The Sale Order is not assigned to the user!');
        }

        $orderItemCount = count($saleOrderObj->orderItems);
        $allowedAvailabilityValues = [
            SaleOrderItem::STORE_AVAILABLE_YES,
            SaleOrderItem::STORE_AVAILABLE_NO
        ];

        $validator = Validator::make($request->all() , [
            'box_qty' => ['required', 'numeric', 'integer', 'min:1'],
            'store_availability' => ['required', 'array', 'size:' . $orderItemCount],
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

        $serviceHelper = new PickerServiceHelper();
        $returnResult = $serviceHelper->setOrderAsDispatchReady($saleOrderObj, $boxCount, $storeAvailabilityArray, $processUserId);
        if ($returnResult['status']) {
            return redirect('picker/dashboard')->with('success', 'The Sale Order status is updated successfully!');
        } else {
            return redirect('picker/dashboard')->with('error', $returnResult['message']);
        }

    }

}
