<?php

namespace Modules\UserRole\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Input;
use Modules\Sales\Entities\SaleOrderAmountCollection;
use Modules\UserRole\Entities\Permission;
use Modules\UserRole\Entities\PermissionMap;
use Modules\UserRole\Entities\UserRoleMap;
use Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use App\Models\User;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleServiceHelper;

class UserRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'User Roles';

        $userRoleList = UserRole::all();

        $userRolesTotal = $userRoleList->count();

        return view('userrole::roles.list', compact(
            'pageTitle',
            'pageSubTitle',
            'userRoleList',
            'userRolesTotal'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'New User Role';

        return view('userrole::roles.new', compact(
            'pageTitle',
            'pageSubTitle'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all() , [
            'role_code'   => ['required', 'alpha_dash'],
            'role_name' => ['nullable', 'string', 'min:6'],
            'role_desc' => ['nullable', 'string', 'min:6'],
            'role_active' => ['required', 'boolean'],
        ], [
            'role_code.required' => 'The Role Code should be provided.',
            'role_code.alpha_dash' => 'The Role Code should contain only alphabets, numbers, dashes(-) or underscores(_).',
            'role_name.string' => 'The Role Name should be a string value.',
            'role_name.min' => 'The Role Name should be minimum :min characters.',
            'role_desc.string' => 'The Role Description should be a string value.',
            'role_desc.min' => 'The Role Description should be minimum :min characters.',
            'role_active.required' => 'The Role Active status should be provided.',
            'role_active.boolean' => 'The Role Active status should be boolean ("1" or "0") value.'
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('role_code', 'role_name', 'role_desc', 'role_active'));
        }

        $postData = $validator->validated();
        $roleCode = $postData['role_code'];
        $roleName = $postData['role_name'];
        $roleDesc = $postData['role_desc'];
        $roleActive = $postData['role_active'];

        $cleanRoleCode = strtolower(str_replace(' ', '_', trim($roleCode)));

        if (UserRole::firstWhere('code', $cleanRoleCode)) {
            return back()
                ->with('error', 'The User Role Code is already used!')
                ->withInput($request->only('role_code', 'role_name', 'role_desc', 'role_active'));
        }

        $cleanRoleName = ($roleName) ? $roleName : ucwords(str_replace('_', ' ', $cleanRoleCode));

        try {

            $roleObj = new UserRole();
            $roleObj->code = $cleanRoleCode;
            $roleObj->display_name = $cleanRoleName;
            $roleObj->description = $roleDesc;
            $roleObj->is_active = $roleActive;
            $roleObj->save();

            return redirect()->route('roles.index')->with('success', 'The User Role is added successfully!');

        } catch(Exception $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput($request->only('role_code', 'role_name', 'role_desc', 'role_active'));
        }

    }

    /**
     * Show the specified resource.
     * @param int $roleId
     * @return Renderable
     */
    public function show($roleId)
    {

        if (is_null($roleId) || !is_numeric($roleId) || ((int)$roleId <= 0)) {
            return back()
                ->with('error', 'The User Role Id input is invalid!');
        }

        $givenUserRole = UserRole::find($roleId);
        if(!$givenUserRole) {
            return back()
                ->with('error', 'The User Role does not exist!');
        }

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'User Role #' . $givenUserRole->code;

        return view('userrole::roles.view', compact(
            'pageTitle',
            'pageSubTitle',
            'givenUserRole'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $roleId
     * @return Renderable
     */
    public function edit($roleId)
    {

        if (is_null($roleId) || !is_numeric($roleId) || ((int)$roleId <= 0)) {
            return back()
                ->with('error', 'The User Role Id input is invalid!');
        }

        $givenUserRole = UserRole::find($roleId);
        if(!$givenUserRole) {
            return back()
                ->with('error', 'The User Role does not exist!');
        }

        $givenPermissionList = Permission::all();

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Edit User Role #' . $givenUserRole->code;

        return view('userrole::roles.edit', compact(
            'pageTitle',
            'pageSubTitle',
            'givenUserRole',
            'givenPermissionList'
        ));

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $roleId
     * @return Renderable
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $roleId)
    {

        if (is_null($roleId) || !is_numeric($roleId) || ((int)$roleId <= 0)) {
            return back()
                ->with('error', 'The User Role Id input is invalid!');
        }

        $givenUserRole = UserRole::find($roleId);
        if(!$givenUserRole) {
            return back()
                ->with('error', 'The User Role does not exist!');
        }

        $validator = Validator::make($request->all() , [
            'role_name' => ['nullable', 'string', 'min:6'],
            'role_desc' => ['nullable', 'string', 'min:6'],
            'role_active' => ['required', 'boolean'],
            'permission_map' => ['nullable', 'array'],
            'permission_map.*.active' => ['boolean'],
            'permission_map.*.permitted' => ['boolean'],
        ], [
            'role_name.string' => 'The Role Name should be a string value.',
            'role_name.min' => 'The Role Name should be minimum :min characters.',
            'role_desc.string' => 'The Role Description should be a string value.',
            'role_desc.min' => 'The Role Description should be minimum :min characters.',
            'role_active.required' => 'The Role Active status should be provided.',
            'role_active.boolean' => 'The Role Active status should be boolean ("1" or "0") value.'
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('role_name', 'role_desc', 'role_active'));
        }

        $postData = $validator->validated();
        $roleName = $postData['role_name'];
        $roleDesc = $postData['role_desc'];
        $roleActive = $postData['role_active'];
        $permissionMapData = (
            array_key_exists('permission_map', $postData)
            && !is_null($postData['permission_map'])
            && is_array($postData['permission_map'])
            && (count($postData['permission_map']) > 0)
        ) ? $postData['permission_map'] : [];

        if ($givenUserRole->isAdmin() && (($roleActive == 0) || ($roleActive === false))) {
            return back()
                ->with('error', "The User Role 'Administrator' cannot be set as 'Inactive'!");
        }

        $cleanRoleName = ($roleName) ? $roleName : ucwords(str_replace('_', ' ', $givenUserRole->code));

        try {

            $givenUserRole->display_name = $cleanRoleName;
            $givenUserRole->description = $roleDesc;
            $givenUserRole->is_active = $roleActive;
            $givenUserRole->save();

            UserRoleMap::where('role_id', $givenUserRole->id)
                ->update(['is_active' => $roleActive]);

            foreach ($permissionMapData as $postPermissionKey => $postPermissionMap) {
                if (!is_null($postPermissionKey) && is_numeric($postPermissionKey) && ((int)$postPermissionKey > 0)) {
                    $givenUserPermission = Permission::find($postPermissionKey);
                    $possibleStatusValues = [0, 1];
                    if($givenUserPermission) {
                        if (
                            array_key_exists('permitted', $postPermissionMap)
                            && array_key_exists('active', $postPermissionMap)
                            && in_array((int) trim($postPermissionMap['permitted']), $possibleStatusValues)
                            && in_array((int) trim($postPermissionMap['active']), $possibleStatusValues)
                        ) {
                            if (!$givenUserRole->isAdmin() || !$givenUserPermission->isDefaultPermission()) {
                                $newPermissionMap = PermissionMap::updateOrCreate([
                                    'role_id' => $givenUserRole->id,
                                    'permission_id' => $givenUserPermission->id
                                ], [
                                    'permitted' => (((int) $postPermissionMap['permitted'] == 1) ? 1 : 0),
                                    'is_active' => (((int) $postPermissionMap['active'] === 1) ? 1 : 0)
                                ]);
                            }
                        }
                    }
                }
            }

            return redirect()->route('roles.index')->with('success', 'The User Role is updated successfully!');

        } catch(Exception $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput($request->only('role_name', 'role_desc', 'role_active'));
        }

    }

    /**
     * Remove the specified resource from storage.
     * @param int $roleId
     * @return Renderable
     */
    public function destroy($roleId)
    {

        if (is_null($roleId) || !is_numeric($roleId) || ((int)$roleId <= 0)) {
            return back()
                ->with('error', 'The User Role Id input is invalid!');
        }

        $targetRoleObj = UserRole::find($roleId);
        if(!$targetRoleObj) {
            return back()
                ->with('error', 'The User Role does not exist!');
        }

        if ($targetRoleObj->isAdmin()) {
            return back()
                ->with('error', "The User Role 'Administrator' cannot be deleted!");
        }

        try {

            UserRole::destroy($roleId);
            return redirect()->route('roles.index')->with('success', 'The User Role is deleted successfully!');

        } catch(Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }

    }

    public function pickersList() {

        $userRoleObj = new UserRole();
        $pickers = $userRoleObj->allPickers();

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Pickers';

        $serviceHelper = new UserRoleServiceHelper();

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getPickersAllowedStatuses();
        $deliveryTimeSlots = $serviceHelper->getPickerDeliveryTimeSlots();

        return view('userrole::pickers.list', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'todayDate',
            'availableApiChannels',
            'availableStatuses',
            'deliveryTimeSlots',
            'serviceHelper',
            'pickers'
        ));

    }

    public function pickersReportFilter(Request $request) {

        $serviceHelper = new UserRoleServiceHelper();

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

        $picker = (
            $request->has('picker_filter')
            && (trim($request->input('picker_filter')) != '')
        ) ? trim($request->input('picker_filter')) : '';

        $startDate = (
            $request->has('delivery_date_start_filter')
            && (trim($request->input('delivery_date_start_filter')) != '')
        ) ? trim($request->input('delivery_date_start_filter')) : date('Y-m-d');

        $endDate = (
            $request->has('delivery_date_end_filter')
            && (trim($request->input('delivery_date_end_filter')) != '')
        ) ? trim($request->input('delivery_date_end_filter')) : date('Y-m-d');

        $filteredOrderStats = $serviceHelper->getPickerOrderStats($region, $apiChannel, $picker, $startDate, $endDate);

        $filteredOrderData = [];
        $totalRec = 0;
        $collectRecStart = $dtStart;
        $collectRecEnd = $collectRecStart + $dtPageLength;
        $currentRec = -1;
        foreach ($filteredOrderStats as $record) {
            $totalRec++;
            $currentRec++;
            if (($currentRec < $collectRecStart) || ($currentRec >= $collectRecEnd)) {
                continue;
            }
            $filteredOrderData[] = [
                'pickerId' => $record['pickerId'],
                'picker' => $record['picker'],
                'active' => $record['active'],
                'date' => date('d-m-Y', strtotime($record['date'])),
                'assignedOrders' => $record['assignedOrders'],
                'pickedOrders' => $record['pickedOrders'],
                'holdedOrders' => $record['holdedOrders']
            ];
        }

        $returnData = [
            'draw' => $dtDraw,
            'recordsTotal' => $totalRec,
            'recordsFiltered' => $totalRec,
            'data' => $filteredOrderData
        ];

        return response()->json($returnData, 200);

    }

    public function pickerView($pickerId) {

        if (is_null($pickerId) || !is_numeric($pickerId) || ((int)$pickerId <= 0)) {
            return back()
                ->with('error', 'The Picker Id input is invalid!');
        }

        $pickerObject = User::find($pickerId);
        if (!$pickerObject) {
            return back()
                ->with('error', 'The Picker does not exist!');
        }

        if (is_null($pickerObject->mappedRole) || (count($pickerObject->mappedRole) == 0)) {
            return back()
                ->with('error', 'The given User is not a Picker!');
        }

        $mappedRole = $pickerObject->mappedRole[0];
        if (!$mappedRole->isPicker()) {
            return back()
                ->with('error', 'The given User is not a Picker!');
        }

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Picker: ' . $pickerObject->name;
        $givenUserData = $pickerObject;
        $serviceHelper = new UserRoleServiceHelper();
        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $allAvailableStatuses = $serviceHelper->getAvailableStatuses();
        $availableStatuses = $serviceHelper->getAvailableStatuses();

        $currentRole = null;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $currentRole = $sessionUser['roleCode'];
        }

        return view('userrole::pickers.view', compact(
            'pageTitle',
            'pageSubTitle',
            'givenUserData',
            'serviceHelper',
            'emirates',
            'availableApiChannels',
            'allAvailableStatuses',
            'availableStatuses',
            'currentRole'
        ));

    }

    public function driversList() {

        $userRoleObj = new UserRole();
        $drivers = $userRoleObj->allDrivers();

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Drivers';

        $serviceHelper = new UserRoleServiceHelper();

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();

        return view('userrole::drivers.list', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'todayDate',
            'availableApiChannels',
            'deliveryTimeSlots',
            'serviceHelper',
            'drivers'
        ));

    }

    public function driversReportFilter(Request $request) {

        set_time_limit(600);

        $serviceHelper = new UserRoleServiceHelper();

        $availableActions = ['datatable', 'excel_sheet'];
        $methodAction = (
            $request->has('filter_action')
            && (trim($request->input('filter_action')) != '')
            && in_array(trim($request->input('filter_action')), $availableActions)
        ) ? trim($request->input('filter_action')) : 'datatable';

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

        $driver = (
            $request->has('driver_filter')
            && (trim($request->input('driver_filter')) != '')
        ) ? trim($request->input('driver_filter')) : '';

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

        if ($methodAction == 'datatable') {

            $filteredOrderStats = $serviceHelper->getDriverOrderStats($region, $apiChannel, $driver, $startDate, $endDate, $deliverySlot);

            $filteredOrderData = [];
            $totalRec = 0;
            $collectRecStart = $dtStart;
            $collectRecEnd = $collectRecStart + $dtPageLength;
            $currentRec = -1;
            foreach ($filteredOrderStats as $record) {
                $totalRec++;
                $currentRec++;
                if (($currentRec < $collectRecStart) || ($currentRec >= $collectRecEnd)) {
                    continue;
                }
                $filteredOrderData[] = [
                    'driverId' => $record['driverId'],
                    'driver' => $record['driver'],
                    'active' => $record['active'],
                    'date' => date('d-m-Y', strtotime($record['date'])),
                    'assignedOrders' => $record['assignedOrders'],
                    'deliveryOrders' => $record['deliveryOrders'],
                    'deliveredOrders' => $record['deliveredOrders'],
                    'canceledOrders' => $record['canceledOrders']
                ];
            }

            $returnData = [
                'draw' => $dtDraw,
                'recordsTotal' => $totalRec,
                'recordsFiltered' => $totalRec,
                'data' => $filteredOrderData
            ];

            return response()->json($returnData, 200);

        }  elseif ($methodAction == 'excel_sheet') {

            $filteredOrderStats = $serviceHelper->getDriverOrderStatsExcel($region, $apiChannel, $driver, $startDate, $endDate, $deliverySlot);
            if (count($filteredOrderStats) <= 0) {
                return back()
                    ->with('error', "There is no record to export the CSV file.");
            }

            $fileName =  "drivers_delivery_report_" . date('d-m-Y', strtotime($startDate)) . "_" . date('d-m-Y', strtotime($endDate)) . ".csv";
            $headers = array(
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$fileName",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            );

            $headingColumns = ["Driver Id", "Driver Name", "Order Delivery Date", "Driver Delivery Date", "Order Number", "Emirates", "Address", "Order Status", "Payment Method", "Initial Pay"];
            $collectionMethods = SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS;
            foreach ($collectionMethods as $methodEl) {
                $headingColumns[] = ucwords($methodEl) . " Collected";
            }
            $headingColumns[] = "Amount Collected";
            $headingColumns[] = "Total Paid";
            $headingColumns[] = "Order Total";
            $headingColumns[] = "Payment Status";

            $callback = function() use($filteredOrderStats, $headingColumns, $collectionMethods) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_values($headingColumns));
                if(!empty($filteredOrderStats)) {
                    foreach($filteredOrderStats as $row) {
                        $rowDataArray = [
                            $row['driverId'],
                            $row['driver'],
                            date('d-m-Y', strtotime($row['orderDeliveryDate'])),
                            date('d-m-Y', strtotime($row['driverDeliveryDate'])),
                            $row['orderId'],
                            $row['emirates'],
                            $row['shippingAddress'],
                            $row['orderStatus'],
                            $row['paymentMethod'],
                            $row['initialPay'],
                        ];
                        foreach ($collectionMethods as $methodEl) {
                            $rowDataArray[] = $row[$methodEl];
                        }
                        $rowDataArray[] = $row['collectedAmount'];
                        $rowDataArray[] = $row['totalPaid'];
                        $rowDataArray[] = $row['orderTotal'];
                        $rowDataArray[] = $row['paymentStatus'];
                        fputcsv($file, $rowDataArray);
                    }
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        }

    }

    public function driverView($driverId) {

        if (is_null($driverId) || !is_numeric($driverId) || ((int)$driverId <= 0)) {
            return back()
                ->with('error', 'The Driver Id input is invalid!');
        }

        $driverObject = User::find($driverId);
        if (!$driverObject) {
            return back()
                ->with('error', 'The Driver does not exist!');
        }

        if (is_null($driverObject->mappedRole) || (count($driverObject->mappedRole) == 0)) {
            return back()
                ->with('error', 'The given User is not a Driver!');
        }

        $mappedRole = $driverObject->mappedRole[0];
        if (!$mappedRole->isDriver()) {
            return back()
                ->with('error', 'The given User is not a Driver!');
        }

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Driver: ' . $driverObject->name;
        $givenUserData = $driverObject;
        $serviceHelper = new UserRoleServiceHelper();
        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $allAvailableStatuses = $serviceHelper->getAvailableStatuses();
        $availableStatuses = $serviceHelper->getAvailableStatuses();

        $currentRole = null;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $currentRole = $sessionUser['roleCode'];
        }

        return view('userrole::drivers.view', compact(
            'pageTitle',
            'pageSubTitle',
            'givenUserData',
            'serviceHelper',
            'emirates',
            'availableApiChannels',
            'allAvailableStatuses',
            'availableStatuses',
            'currentRole'
        ));

    }

}
