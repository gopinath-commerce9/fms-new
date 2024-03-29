<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Input;
use Modules\Admin\Entities\AdminServiceHelper;
use Modules\API\Entities\ApiServiceHelper;
use Modules\Sales\Entities\SaleOrder;
use Modules\Sales\Jobs\SaleOrderChannelImport;
use Modules\Sales\Jobs\SalesOrderIndividualImport;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     */
    public function index()
    {
        return redirect()->route('admin.dashboard');
    }

    public function dashboard(Request $request)
    {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Dashboard';

        $serviceHelper = new AdminServiceHelper();
        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $selectedEmirate = (
            $request->has('emirate')
            && (trim($request->input('emirate')) != '')
            && array_key_exists(trim($request->input('emirate')), $emirates)
        ) ? trim($request->input('emirate')) : '859';

        $regionOrderCount = $serviceHelper->getOrdersCountByRegion($selectedEmirate);
        $todayDate = date('Y-m-d');
        $driverData = $serviceHelper->getDriversByDate($todayDate);

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getAdminAllowedStatuses();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();
        $deliveryZones = $serviceHelper->getDeliveryZones();

        return view('admin::index', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'selectedEmirate',
            'regionOrderCount',
            'todayDate',
            'driverData',
            'availableApiChannels',
            'deliveryTimeSlots',
            'deliveryZones',
            'availableStatuses'
        ));

    }

    public function filterOrders(Request $request) {

        $serviceHelper = new AdminServiceHelper();

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

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $apiChannel = (
            $request->has('api_channel_filter')
            && (trim($request->input('api_channel_filter')) != '')
            && array_key_exists(trim($request->input('api_channel_filter')), $availableApiChannels)
        ) ? trim($request->input('api_channel_filter')) : null;

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();
        $region = (
            $request->has('emirates_region')
            && (trim($request->input('emirates_region')) != '')
        ) ? explode(',', trim($request->input('emirates_region'))) : [];

        $allowedOrderStatuses = $serviceHelper->getAdminAllowedStatuses();
        $orderStatus = (
            $request->has('order_status_filter')
            && (trim($request->input('order_status_filter')) != '')
            && array_key_exists(trim($request->input('order_status_filter')), $allowedOrderStatuses)
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

            $filteredOrders = $serviceHelper->getAdminSaleOrders($region, $apiChannel, $orderStatus, $startDate, $endDate, $deliverySlot, $zone);
            if ($filteredOrders) {

                $filteredOrderData = [];
                $orderStatuses = $serviceHelper->getAdminAllowedStatuses();
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
                    $emirateId = $record->region_id;
                    $tempRecord['region'] = $emirates[$emirateId];
                    $tempRecord['zone'] = ucwords($record->zone_id);
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
                    $tempRecord['deliveryDate'] = (!is_null($record->delivery_date)) ? date('d-m-Y', strtotime($record->delivery_date)) : '';
                    $tempRecord['deliveryTimeSlot'] = $record->delivery_time_slot;
                    $tempRecord['deliveryPicker'] = '';
                    $tempRecord['deliveryPickerTime'] = '';
                    $tempRecord['deliveryDriver'] = '';
                    $tempRecord['deliveryDriverTime'] = '';
                    $orderStatusId = $record->order_status;
                    $tempRecord['orderStatus'] = $orderStatuses[$orderStatusId];
                    $deliveryPickerData = $record->currentPicker;
                    $deliveryDriverData = $record->currentDriver;
                    $tempRecord['actions'] = url('/admin/order-view/' . $record->id);
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
            $orderStatuses = $serviceHelper->getAdminAllowedStatuses();
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
                $xAxisData[] = date('d-m-Y', strtotime($dateKey));
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

    public function deliveryDetails(Request $request) {

        $region = (
            $request->has('region')
            && (trim($request->input('region')) != '')
        ) ? urldecode(trim($request->input('region'))) : null;

        $interval = (
            $request->has('interval')
            && (trim($request->input('interval')) != '')
        ) ? urldecode(trim($request->input('interval'))) : null;

        $date = (
            $request->has('date')
            && (trim($request->input('date')) != '')
        ) ? urldecode(trim($request->input('date'))) : null;

        $pageNo = (
            $request->has('pageno')
            && (trim($request->input('pageno')) != '')
        ) ? urldecode(trim($request->input('pageno'))) : 1;

        if (is_null($region) || is_null($interval) || is_null($date)) {
            return back()
                ->with('error', "Requested Parameters are Empty!!");
        }

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Order List';

        $orderStatuses = config('fms.order_statuses');
        $serviceHelper = new AdminServiceHelper();

        $customerGroups = [];
        $customerGroupData = $serviceHelper->getCustomerGroups();
        if (array_key_exists('items', $customerGroupData)) {
            foreach($customerGroupData['items'] as $group) {
                $customerGroups[$group['id']] = $group['code'];
            }
        }

        $startPageLink = $pageNo;
        $endPageLink = $pageNo + 3;
        $pageSize = 20;
        $offset = ($pageNo - 1) * $pageSize;

        $orderData = $serviceHelper->getOrdersByRegion($region, $interval, $date);
        $totalRows = count($orderData);

        $totalPages = ceil($totalRows / $pageSize);
        if($endPageLink > $totalPages) {
            $endPageLink = $totalPages;
        }

        $orderData = $serviceHelper->getOrdersByRegion($region, $interval, $date, $pageSize, $pageNo);
        $orderIds = [];

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();

        return view('admin::delivery-details', compact(
            'pageTitle',
            'pageSubTitle',
            'region',
            'interval',
            'date',
            'customerGroups',
            'orderData',
            'availableApiChannels',
            'totalRows',
            'startPageLink',
            'endPageLink',
            'totalPages',
            'pageNo',
            'orderIds',
            'orderStatuses',
            'serviceHelper'
        ));

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
        $serviceHelper = new AdminServiceHelper();

        $customerGroups = [];
        $customerGroupData = $serviceHelper->getCustomerGroups();
        if (is_array($customerGroupData) && (count($customerGroupData) > 0) && array_key_exists('items', $customerGroupData)) {
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

        $resyncStatuses = $serviceHelper->getResyncStatuses();

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

        return view('admin::order-view', compact(
            'pageTitle',
            'pageSubTitle',
            'saleOrderObj',
            'saleOrderData',
            'customerGroups',
            'vendorList',
            'serviceHelper',
            'orderStatuses',
            'resyncStatuses'
        ));

    }

    public function getVendorStatus(Request $request) {

        $orderIds = (
            $request->has('orderids')
            && (trim($request->input('orderids')) != '')
        ) ? trim($request->input('orderids')) : '';

        if ($orderIds == '') {
            return response()->json([], 200);
        }

        $serviceHelper = new AdminServiceHelper();

        $orderIdArray = explode(',', $orderIds);
        $orderIdsClean = array_map('trim', $orderIdArray);
        $vendorResponse = $serviceHelper->getOrderVendorStatus($orderIdsClean);

        $vendorStatusList = [];
        foreach($vendorResponse as $vendor) {
            $vendorStatusList[$vendor['main_order_id']] = $vendor['status'];
        }

        return response()->json($vendorStatusList, 200);

    }

    public function fetchChannelOrders(Request $request) {

        $serviceHelper = new AdminServiceHelper();

        $apiChannel = (
            $request->has('api_channel')
            && (trim($request->input('api_channel')) != '')
        ) ? trim($request->input('api_channel')) : $serviceHelper->getApiChannel();

        $startDate = (
            $request->has('api_channel_date_start')
            && (trim($request->input('api_channel_date_start')) != '')
        ) ? trim($request->input('api_channel_date_start')) : date('Y-m-d', strtotime('-3 days'));

        $endDate = (
            $request->has('api_channel_date_end')
            && (trim($request->input('api_channel_date_end')) != '')
        ) ? trim($request->input('api_channel_date_end')) : date('Y-m-d', strtotime('+10 days'));

        $sessionUser = session('authUserData');
        SaleOrderChannelImport::dispatch($apiChannel, date('Y-m-d 00:00:00', strtotime($startDate)), date('Y-m-d 23:59:59', strtotime($endDate)), $sessionUser['id']);

        return response()->json([ 'message' => 'The sale orders will be fetched in the background' ], 200);

    }

    public function fetchChannelIndividualOrders(Request $request) {

        $serviceHelper = new AdminServiceHelper();

        $apiChannel = (
            $request->has('api_channel')
            && (trim($request->input('api_channel')) != '')
        ) ? trim($request->input('api_channel')) : $serviceHelper->getApiChannel();

        $orderNumberString = (
            $request->has('api_channel_order_numbers')
            && (trim($request->input('api_channel_order_numbers')) != '')
        ) ? trim($request->input('api_channel_order_numbers')) : '';

        $sessionUser = session('authUserData');
        SalesOrderIndividualImport::dispatch($apiChannel, $orderNumberString, $sessionUser['id']);

        return response()->json([ 'message' => 'The sale orders will be fetched in the background' ], 200);

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

        $serviceHelper = new AdminServiceHelper();
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

        $targetOrder = $targetOrderQ->get();
        if ($targetOrder) {
            $saleOrder = ($targetOrder instanceof SaleOrder) ? $targetOrder : $targetOrder->first();
            if (is_null($saleOrder)) {
                return back()
                    ->with('error', "Sale Order #" . $incrementId . " not found!");
            }
            return redirect('/admin/order-view/' . $saleOrder->id);
        } else {
            return back()
                ->with('error', "Sale Order #" . $incrementId . " not found!");
        }

    }

    public function downloadItemsDateCsv(Request $request) {

        $region = (
            $request->has('region')
            && (trim($request->input('region')) != '')
        ) ? urldecode(trim($request->input('region'))) : null;

        $date = (
            $request->has('date')
            && (trim($request->input('date')) != '')
        ) ? urldecode(trim($request->input('date'))) : null;

        if (is_null($region) || is_null($date)) {
            return back()
                ->with('error', "Requested Parameters are Empty!!");
        }

        $serviceHelper = new AdminServiceHelper();
        $records = $serviceHelper->getSaleOrderItemsByDate($region, $date);

        if (count($records) <= 0) {
            return back()
                ->with('error', "There is no record to export the CSV file.");
        }

        $fileName = $region . "_" . $date . ".csv";
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $headingColumns = ["SKU", "Name", "Country", "Qty", "Selling Format","Pack and Weight Info", "Scale Number", "Shelf Number"];

        $callback = function() use($records, $headingColumns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($headingColumns));
            if(!empty($records)) {
                foreach($records as $row) {
                    fputcsv($file, [
                        $row['item_sku'],
                        $row['item_name'],
                        $row['country_label'],
                        $row['total_qty'],
                        $row['selling_unit'],
                        $row['item_info'],
                        $row['scale_number'],
                        (array_key_exists('shelf_number', $row) ? $row['shelf_number'] : ''),
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);

    }

    public function downloadItemsScheduleCsv(Request $request) {

        $region = (
            $request->has('region')
            && (trim($request->input('region')) != '')
        ) ? urldecode(trim($request->input('region'))) : null;

        $date = (
            $request->has('date')
            && (trim($request->input('date')) != '')
        ) ? urldecode(trim($request->input('date'))) : null;

        $interval = (
            $request->has('interval')
            && (trim($request->input('interval')) != '')
        ) ? urldecode(trim($request->input('interval'))) : null;

        if (is_null($region) || is_null($date) || is_null($interval)) {
            return back()
                ->with('error', "Requested Parameters are Empty!!");
        }

        $serviceHelper = new AdminServiceHelper();
        $records = $serviceHelper->getSaleOrderItemsBySchedule($region, $date, $interval);

        if (count($records) <= 0) {
            return back()
                ->with('error', "There is no record to export the CSV file.");
        }

        $fileName = $region . "_" . $date . "_" . $interval . ".csv";
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $headingColumns = ["SKU", "Name", "Country", "Qty", "Selling Format","Pack and Weight Info", "Scale Number", "Shelf Number"];

        $callback = function() use($records, $headingColumns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($headingColumns));
            if(!empty($records)) {
                foreach($records as $row) {
                    fputcsv($file, [
                        $row['item_sku'],
                        $row['item_name'],
                        $row['country_label'],
                        $row['qty_ordered'],
                        $row['selling_unit'],
                        $row['item_info'],
                        $row['scale_number'],
                        (array_key_exists('shelf_number', $row) ? $row['shelf_number'] : ''),
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);

    }

    public function exportOrderWiseItems(Request $request) {

        $orders = (
            $request->has('order')
            && is_array($request->input('order'))
        ) ? $request->input('order') : null;

        if (is_null($orders)) {
            return back()
                ->with('error', "Requested Parameters are Empty!!");
        }

        $serviceHelper = new AdminServiceHelper();
        $records = $serviceHelper->getSaleOrderItemsByOrderIds($orders);

        if (count($records) <= 0) {
            return back()
                ->with('error', "There is no record to export the CSV file.");
        }

        $fileName = "orderwiseitems.csv";
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $headingColumns = ["SKU", "Name", "Country", "Qty", "Selling Format","Pack and Weight Info", "Scale Number", "Shelf Number"];

        $callback = function() use($records, $headingColumns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($headingColumns));
            if(!empty($records)) {
                foreach($records as $row) {
                    fputcsv($file, [
                        $row['item_sku'],
                        $row['item_name'],
                        $row['country_label'],
                        $row['total_qty'],
                        $row['selling_unit'],
                        $row['item_info'],
                        $row['scale_number'],
                        (array_key_exists('shelf_number', $row) ? $row['shelf_number'] : ''),
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);

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

        $serviceHelper = new AdminServiceHelper();
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

}
