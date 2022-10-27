<?php

namespace Modules\UserRole\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Input;
use Modules\API\Entities\ApiServiceHelper;
use Modules\Sales\Entities\SaleOrder;
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

    public function pickersReportList() {

        $userRoleObj = new UserRole();
        $pickers = $userRoleObj->allPickers();

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Pickers Report';

        $serviceHelper = new UserRoleServiceHelper();

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getPickersAllowedStatuses();
        $deliveryTimeSlots = $serviceHelper->getPickerDeliveryTimeSlots();

        return view('userrole::pickers.report', compact(
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

        $pickers = (
            $request->has('picker_values')
            && (trim($request->input('picker_values')) != '')
        ) ? explode(',', trim($request->input('picker_values'))) : [];

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

        $filteredOrderStats = $serviceHelper->getPickerOrderStats($region, $apiChannel, $pickers, $startDate, $endDate, $deliverySlot);

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
            $actionLinkUrl = route('roles.pickersReportViewMore', [
                'region' => $region,
                'channel' => $apiChannel,
                'picker' => $record['pickerId'],
                'delivery_date' => date('Y-m-d', strtotime($record['date'])),
                'delivery_slot' => $deliverySlot
            ]);
            $filteredOrderData[] = [
                'pickerId' => $record['pickerId'],
                'picker' => $record['picker'],
                'active' => $record['active'],
                'date' => date('d-m-Y', strtotime($record['date'])),
                'totalOrders' => $record['totalOrders'],
                'pending' => $record['assignedOrders'],
                'holded' => $record['holdedOrders'],
                'completed' => $record['pickedOrders'],
                'actions' => $actionLinkUrl
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

    public function pickersReportViewMore(Request $request) {

        $serviceHelper = new UserRoleServiceHelper();

        $emirates = $serviceHelper->getAvailableRegionsList();
        $region = (
            $request->has('region')
            && (trim($request->input('region')) != '')
            && array_key_exists(trim($request->input('region')), $emirates)
        ) ? trim($request->input('region')) : '';

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $apiChannel = (
            $request->has('channel')
            && (trim($request->input('channel')) != '')
            && array_key_exists(trim($request->input('channel')), $availableApiChannels)
        ) ? trim($request->input('channel')) : '';

        $picker = (
            $request->has('picker')
            && (trim($request->input('picker')) != '')
        ) ? explode(',', trim($request->input('picker'))) : [];

        $startDate = (
            $request->has('delivery_date')
            && (trim($request->input('delivery_date')) != '')
        ) ? trim($request->input('delivery_date')) : date('Y-m-d');

        $endDate = (
            $request->has('delivery_date')
            && (trim($request->input('delivery_date')) != '')
        ) ? trim($request->input('delivery_date')) : date('Y-m-d');

        $deliverySlot = (
            $request->has('delivery_slot')
            && (trim($request->input('delivery_slot')) != '')
        ) ? trim($request->input('delivery_slot')) : '';

        if (is_null($picker) || !is_array($picker) || (count($picker) == 0)) {
            return back()
                ->with('error', 'The Picker Id input is invalid!');
        }

        $pickerObject = User::select('*')->whereIn('id', $picker)->get();
        if (!$pickerObject || (count($pickerObject) == 0)) {
            return back()
                ->with('error', 'The Picker does not exist!');
        }

        $pickerFlag = true;
        $pickerNames = [];
        foreach ($pickerObject as $pickerObj) {
            if (is_null($pickerObj->mappedRole) || (count($pickerObj->mappedRole) == 0)) {
                $pickerFlag = false;
            } elseif (!$pickerObj->mappedRole[0]->isPicker()) {
                $pickerFlag = false;
            } else {
                $pickerNames[] =  $pickerObj->name;
            }
        }

        if ($pickerFlag === false) {
            return back()
                ->with('error', 'The given User is not a Picker!');
        }

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Pickers (' . implode(', ', $pickerNames) . ') ' . 'Activities on ' . date('d-m-Y', strtotime($startDate)) . '';

        $filteredOrderStats = $serviceHelper->getPickerOrderStatsExcel($region, $apiChannel, $picker, $startDate, $endDate, $deliverySlot);
        if (count($filteredOrderStats) <= 0) {
            return back()
                ->with('error', "There is no record of Activities of the Picker.");
        }

        $currentRole = null;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $currentRole = $sessionUser['roleCode'];
        }

        $givenUserData = $pickerObject;

        return view('userrole::pickers.report-view', compact(
            'pageTitle',
            'pageSubTitle',
            'givenUserData',
            'serviceHelper',
            'emirates',
            'availableApiChannels',
            'filteredOrderStats',
            'currentRole'
        ));

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

    public function driversReportList() {

        $userRoleObj = new UserRole();
        $drivers = $userRoleObj->allDrivers();

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Drivers Detailed Report';

        $serviceHelper = new UserRoleServiceHelper();

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();

        return view('userrole::drivers.report', compact(
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

        $drivers = (
            $request->has('driver_values')
            && (trim($request->input('driver_values')) != '')
        ) ? explode(',', trim($request->input('driver_values'))) : [];

        $feederFlag = (
            $request->has('feeder_driver_filter')
            && (trim($request->input('feeder_driver_filter')) != '')
        ) ? trim($request->input('feeder_driver_filter')) : '';

        $startDate = (
            $request->has('delivery_date_start_filter')
            && (trim($request->input('delivery_date_start_filter')) != '')
        ) ? trim($request->input('delivery_date_start_filter')) : date('Y-m-d');

        $endDate = (
            $request->has('delivery_date_end_filter')
            && (trim($request->input('delivery_date_end_filter')) != '')
        ) ? trim($request->input('delivery_date_end_filter')) : date('Y-m-d');

        $datePurpose = (
            $request->has('date_purpose_filter')
            && (trim($request->input('date_purpose_filter')) != '')
            && (
                ((int)trim($request->input('date_purpose_filter')) === 1)
                || ((int)trim($request->input('date_purpose_filter')) === 2)
            )
        ) ? (int)trim($request->input('date_purpose_filter')) : 1;

        $deliverySlot = (
            $request->has('delivery_slot_filter')
            && (trim($request->input('delivery_slot_filter')) != '')
        ) ? trim($request->input('delivery_slot_filter')) : '';

        $collVerifyFlag = (
            $request->has('collection_verify_filter')
            && (trim($request->input('collection_verify_filter')) != '')
        ) ? trim($request->input('collection_verify_filter')) : '';

        if ($methodAction == 'datatable') {

            $filteredOrderStats = $serviceHelper->getDriverOrderStats($region, $apiChannel, $drivers, $feederFlag, $collVerifyFlag, $startDate, $endDate, $deliverySlot, $datePurpose);

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
                $actionLinkUrl = route('roles.driversReportViewMore', [
                    'region' => $region,
                    'channel' => $apiChannel,
                    'driver' => $record['driverId'],
                    'filter_type' => $datePurpose,
                    'delivery_date' => date('Y-m-d', strtotime($record['date'])),
                    'delivery_slot' => $deliverySlot,
                    'collection_verify' => $collVerifyFlag,
                ]);
                $filteredOrderData[] = [
                    'driverId' => $record['driverId'],
                    'driver' => $record['driver'],
                    'active' => $record['active'],
                    'feeder' => $record['feeder'],
                    'date' => date('d-m-Y', strtotime($record['date'])),
                    'assignedOrders' => $record['assignedOrders'],
                    'deliveryOrders' => $record['deliveryOrders'],
                    'deliveredOrders' => $record['deliveredOrders'],
                    'canceledOrders' => $record['canceledOrders'],
                    'actions' => $actionLinkUrl
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

            $filteredOrderStats = $serviceHelper->getDriverOrderStatsExcel($region, $apiChannel, $drivers, $feederFlag, $collVerifyFlag, $startDate, $endDate, $deliverySlot, $datePurpose);
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

            $headingColumns = [
                "Driver Id",
                "Driver Name",
                "Order Delivery Date",
                "Driver Assigned Date",
                "Driver Delivery Date",
                "Order Number",
                "Emirates",
                "Name",
                "Address",
                "Phone",
                "Order Status",
                "Payment Method",
                "Initial Pay"
            ];
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
                            date('d-m-Y', strtotime($row['driverAssignedDate'])),
                            date('d-m-Y', strtotime($row['driverDeliveryDate'])),
                            $row['orderNumber'],
                            $row['emirates'],
                            $row['customerName'],
                            $row['shippingAddress'],
                            $row['customerContact'],
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

    public function driversReportViewMore(Request $request) {

        $serviceHelper = new UserRoleServiceHelper();

        $emirates = $serviceHelper->getAvailableRegionsList();
        $region = (
            $request->has('region')
            && (trim($request->input('region')) != '')
            && array_key_exists(trim($request->input('region')), $emirates)
        ) ? trim($request->input('region')) : '';

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $apiChannel = (
            $request->has('channel')
            && (trim($request->input('channel')) != '')
            && array_key_exists(trim($request->input('channel')), $availableApiChannels)
        ) ? trim($request->input('channel')) : '';

        $driver = (
            $request->has('driver')
            && (trim($request->input('driver')) != '')
        ) ? explode(',', trim($request->input('driver'))) : [];

        $feederFlag = (
            $request->has('feeder_driver_filter')
            && (trim($request->input('feeder_driver_filter')) != '')
        ) ? trim($request->input('feeder_driver_filter')) : '';

        $startDate = (
            $request->has('delivery_date')
            && (trim($request->input('delivery_date')) != '')
        ) ? trim($request->input('delivery_date')) : date('Y-m-d');

        $endDate = (
            $request->has('delivery_date')
            && (trim($request->input('delivery_date')) != '')
        ) ? trim($request->input('delivery_date')) : date('Y-m-d');

        $datePurpose = (
            $request->has('filter_type')
            && (trim($request->input('filter_type')) != '')
            && (
                ((int)trim($request->input('filter_type')) === 1)
                || ((int)trim($request->input('filter_type')) === 2)
            )
        ) ? (int)trim($request->input('filter_type')) : 1;

        $deliverySlot = (
            $request->has('delivery_slot')
            && (trim($request->input('delivery_slot')) != '')
        ) ? trim($request->input('delivery_slot')) : '';

        $collVerifyFlag = (
            $request->has('collection_verify')
            && (trim($request->input('collection_verify')) != '')
        ) ? trim($request->input('collection_verify')) : '';

        if (is_null($driver) || !is_array($driver) || (count($driver) == 0)) {
            return back()
                ->with('error', 'The Driver Id input is invalid!');
        }

        $driverObject = User::select('*')->whereIn('id', $driver)->get();
        if (!$driverObject || (count($driverObject) == 0)) {
            return back()
                ->with('error', 'The Driver does not exist!');
        }

        $driverFlag = true;
        $driverNames = [];
        foreach ($driverObject as $driverObj) {
            if (is_null($driverObj->mappedRole) || (count($driverObj->mappedRole) == 0)) {
                $driverFlag = false;
            } elseif (!$driverObj->mappedRole[0]->isDriver()) {
                $driverFlag = false;
            } else {
                $driverNames[] =  $driverObj->name;
            }
        }

        if ($driverFlag === false) {
            return back()
                ->with('error', 'The given User is not a Driver!');
        }

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Drivers (' . implode(', ', $driverNames) . ') ' . (($datePurpose == 2) ? 'Assignments' : 'Activities') . ' on ' . date('d-m-Y', strtotime($startDate)) . '';

        $filteredOrderStats = $serviceHelper->getDriverOrderStatsExcel($region, $apiChannel, $driver, $feederFlag, $collVerifyFlag, $startDate, $endDate, $deliverySlot, $datePurpose);
        if (count($filteredOrderStats) <= 0) {
            return back()
                ->with('error', "There is no record of Activities of the Driver.");
        }

        $collectionMethods = SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS;

        $currentRole = null;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $currentRole = $sessionUser['roleCode'];
        }

        $givenUserData = $driverObject;

        return view('userrole::drivers.report-view', compact(
            'pageTitle',
            'pageSubTitle',
            'givenUserData',
            'serviceHelper',
            'emirates',
            'availableApiChannels',
            'collectionMethods',
            'filteredOrderStats',
            'currentRole'
        ));

    }

    public function driverCollectionEditView($orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        $filterStatus = [
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY,
            SaleOrder::SALE_ORDER_STATUS_DELIVERED,
        ];
        if (!in_array($saleOrderObj->order_status, $filterStatus)) {
            return back()
                ->with('error', 'The Sale Order cannot be edited for Driver Amount Collection!');
        }

        $saleOrderObj->paymentData;
        $saleOrderObj->paidAmountCollections;
        $saleOrderObj->statusHistory;
        $saleOrderData = $saleOrderObj->toArray();

        $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
        if (!in_array($saleOrderData['payment_data'][0]['method'], $fixTotalDueArray)) {
            return back()
                ->with('error', 'The Sale Order cannot be edited for Driver Amount Collection!');
        }

        if ((int)$saleOrderData['is_amount_verified'] === 1) {
            return back()
                ->with('error', 'The Sale Order cannot be edited for Driver Amount Collection!');
        }

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Driver Amount Collection Order #' . $saleOrderObj->increment_id;

        $serviceHelper = new UserRoleServiceHelper();
        $emirates = $serviceHelper->getAvailableRegionsList();
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $allAvailableStatuses = $serviceHelper->getAvailableStatuses();
        $availableStatuses = $serviceHelper->getAvailableStatuses();
        $collectionMethodList = SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS;

        $totalOrderValueOrig = (float)$saleOrderData['order_total'];
        $totalCanceledValue = (!is_null($saleOrderData['canceled_total'])) ? (float)$saleOrderData['canceled_total'] : 0;
        $totalOrderValue = $totalOrderValueOrig - $totalCanceledValue;
        $totalDueValue = $saleOrderData['order_due'];
        $initialPaidValue = (float)$saleOrderData['order_total'] - (float)$saleOrderData['order_due'];
        if (in_array($saleOrderData['payment_data'][0]['method'], $fixTotalDueArray)) {
            $totalDueValue = $totalOrderValue;
            $initialPaidValue = 0;
        }
        $amountCollectionData = [];
        $totalCollectedAmount = 0;
        foreach($collectionMethodList as $cMethod) {
            $amountCollectionData[$cMethod] = 0;
        }

        if (
            isset($saleOrderData['paid_amount_collections'])
            && is_array($saleOrderData['paid_amount_collections'])
            && (count($saleOrderData['paid_amount_collections']) > 0)
        ) {
            foreach ($saleOrderData['paid_amount_collections'] as $paidCollEl) {
                $amountCollectionData[$paidCollEl['method']] += (float) $paidCollEl['amount'];
                $totalCollectedAmount += (float) $paidCollEl['amount'];
                $totalDueValue -= (float) $paidCollEl['amount'];
            }
        }

        $paymentStatus = '';
        $epsilon = 0.00001;
        if (!(abs($totalOrderValue - 0) < $epsilon)) {
            if (abs($totalDueValue - 0) < $epsilon) {
                $paymentStatus = 'paid';
            } else {
                if ($totalDueValue < 0) {
                    $paymentStatus = 'overpaid';
                } else {
                    $paymentStatus = 'due';
                }
            }
        }

        $paymentMethodTitle = '';
        $payInfoLoopTargetLabel = 'method_title';
        if (isset($saleOrderData['payment_data'][0]['extra_info'])) {
            $paymentAddInfo = json5_decode($saleOrderData['payment_data'][0]['extra_info'], true);
            if (is_array($paymentAddInfo) && (count($paymentAddInfo) > 0)) {
                foreach ($paymentAddInfo as $paymentInfoEl) {
                    if ($paymentInfoEl['key'] == $payInfoLoopTargetLabel) {
                        $paymentMethodTitle = $paymentInfoEl['value'];
                    }
                }
            }
        }

        return view('userrole::drivers.report-edit-view', compact(
            'pageTitle',
            'pageSubTitle',
            'saleOrderObj',
            'saleOrderData',
            'serviceHelper',
            'emirates',
            'availableApiChannels',
            'allAvailableStatuses',
            'availableStatuses',
            'totalOrderValue',
            'totalDueValue',
            'paymentMethodTitle',
            'paymentStatus',
            'initialPaidValue',
            'totalCollectedAmount',
            'collectionMethodList',
            'amountCollectionData',
        ));

    }

    public function driverCollectionEditSave(Request $request, $orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        $filterStatus = [
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY,
            SaleOrder::SALE_ORDER_STATUS_DELIVERED,
        ];
        if (!in_array($saleOrderObj->order_status, $filterStatus)) {
            return back()
                ->with('error', 'The Sale Order cannot be edited for Driver Amount Collection!');
        }

        $saleOrderObj->paymentData;
        $saleOrderObj->paidAmountCollections;
        $saleOrderObj->statusHistory;
        $saleOrderData = $saleOrderObj->toArray();

        $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
        if (!in_array($saleOrderData['payment_data'][0]['method'], $fixTotalDueArray)) {
            return back()
                ->with('error', 'The Sale Order cannot be edited for Driver Amount Collection!');
        }

        if ((int)$saleOrderData['is_amount_verified'] === 1) {
            return back()
                ->with('error', 'The Sale Order cannot be edited for Driver Amount Collection!');
        }

        $givenOrderCollections = (
            $request->has('collections')
            && (!is_null($request->input('collections')))
            && is_array($request->input('collections'))
            && (count($request->input('collections')) > 0)
        ) ? $request->input('collections') : [];

        if (count($givenOrderCollections) == 0) {
            return back()
                ->with('error', 'Amount Collection details not found!');
        }

        $amountCollectionMethods = SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS;
        $methodsIncluded = false;
        $currentAmountCollected = 0;
        foreach ($givenOrderCollections as $givenMethod => $givenAmount) {
            if (in_array($givenMethod, $amountCollectionMethods) && is_numeric(trim($givenAmount)) && ((float)trim($givenAmount) >= 0)) {
                $methodsIncluded = true;
                $currentAmountCollected += (float)trim($givenAmount);
            }
        }
        if ($methodsIncluded === false) {
            return back()
                ->with('error', 'Amount Collection details not found!');
        }

        foreach ($givenOrderCollections as $givenMethod => $givenAmount) {
            if (in_array($givenMethod, $amountCollectionMethods) && is_numeric(trim($givenAmount)) && ((float)trim($givenAmount) >= 0)) {
                $amountCollectionDeleteExec = SaleOrderAmountCollection::where('order_id', $saleOrderData['id'])
                    ->where('method', $givenMethod)
                    ->delete();
                if ((float)trim($givenAmount) > 0) {
                    $newAmountCollectionObj = SaleOrderAmountCollection::updateOrCreate([
                        'order_id' => $saleOrderData['id'],
                        'method' => $givenMethod,
                    ], [
                        'amount' => (float)trim($givenAmount),
                        'status' => SaleOrderAmountCollection::PAYMENT_COLLECTION_STATUS_PAID,
                    ]);
                }
            }
        }

        return back()
            ->with('success', 'The Driver Amount Collection of Sale Order #' . $saleOrderData['increment_id'] . ' is updated successfully!');

    }

    public function driverCollectionVerification(Request $request, ?int $orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order Id input is invalid!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order does not exist!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $filterStatus = [
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY,
            SaleOrder::SALE_ORDER_STATUS_DELIVERED,
        ];
        if (!in_array($saleOrderObj->order_status, $filterStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order cannot be verified for Driver Amount Collection!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $saleOrderObj->paymentData;
        $saleOrderObj->paidAmountCollections;
        $saleOrderObj->statusHistory;
        $saleOrderData = $saleOrderObj->toArray();

        /*$fixTotalDueArray = ['cashondelivery', 'banktransfer'];
        if (!in_array($saleOrderData['payment_data'][0]['method'], $fixTotalDueArray)) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order cannot be verified for Driver Amount Collection!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }*/

        if ((int)$saleOrderData['is_amount_verified'] === SaleOrder::COLLECTION_VERIFIED_YES) {
            return response()->json([
                'success' => false,
                'message' => 'The Sale Order is already verified for Driver Amount Collection!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $processUserId = 0;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $processUserId = $sessionUser['id'];
        }

        $affectedRows = SaleOrder::where("id", $saleOrderData['id'])->update([
            "is_amount_verified" => SaleOrder::COLLECTION_VERIFIED_YES,
            "amount_verified_at" => date('Y-m-d H:i:s'),
            "amount_verified_by" => $processUserId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The Sale Order is set as verified for Driver Amount Collection!',
        ], ApiServiceHelper::HTTP_STATUS_CODE_OK);

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

    public function feedersReportList() {

        $userRoleObj = new UserRole();
        $drivers = $userRoleObj->allDrivers();

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Feeder Drivers Detailed Report';

        $serviceHelper = new UserRoleServiceHelper();

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();

        $collectionMethods = SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS;

        $currentRole = null;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $currentRole = $sessionUser['roleCode'];
        }

        return view('userrole::drivers.feeder-report', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'todayDate',
            'availableApiChannels',
            'deliveryTimeSlots',
            'serviceHelper',
            'drivers',
            'collectionMethods',
            'currentRole',
        ));

    }

    public function feedersReportFilter(Request $request) {

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

        $drivers = (
            $request->has('driver_values')
            && (trim($request->input('driver_values')) != '')
        ) ? explode(',', trim($request->input('driver_values'))) : [];

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

            $filteredOrderStats = $serviceHelper->getFeederOrderStats($region, $apiChannel, $drivers, $startDate, $endDate, $deliverySlot);

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

                $currentRole = null;
                if (session()->has('authUserData')) {
                    $sessionUser = session('authUserData');
                    $currentRole = $sessionUser['roleCode'];
                }

                if (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_ADMIN)) {
                    $roleUrlFragment = 'admin';
                } elseif (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_SUPERVISOR)) {
                    $roleUrlFragment = 'supervisor';
                } elseif (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_PICKER)) {
                    $roleUrlFragment = 'picker';
                } elseif (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_DRIVER)) {
                    $roleUrlFragment = 'driver';
                }

                $amountCollectable = false;
                $amountCollectionEditable = false;
                $amountCollectionVerified = false;
                $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
                if (in_array($record['paymentMethodCode'], $fixTotalDueArray)) {
                    $amountCollectable = true;
                    if ($record['collectionVerified'] == '0') {
                        $amountCollectionEditable = true;
                    } elseif ($record['collectionVerified'] == '1') {
                        $amountCollectionVerified = true;
                    }
                }
                if ($amountCollectable === false) {
                    if ($record['collectionVerified'] == SaleOrder::COLLECTION_VERIFIED_YES) {
                        $amountCollectionVerified = true;
                    }
                }

                $feedersString = '';
                if (is_array($record['feedersInvolved']) && (count($record['feedersInvolved']) > 0)) {
                    foreach ($record['feedersInvolved'] as $feederEl) {
                        $feedersString .= '<a href="' . url('/userrole/drivers/view/' . $feederEl['id']) . '" class="btn btn-primary btn-clean mr-2" title="View Picker">';
                        $feedersString .= '<span>' . $feederEl['name'] . '</span>';
                        $feedersString .= '</a>';
                    }
                }
                $collectionVerifiedString = ($amountCollectionVerified) ? '<i class="flaticon2-check-mark text-success"></i>' : '-';
                $collectionVerifiedAtString = (!is_null($record['collectionVerifiedAt'])) ? $serviceHelper->getFormattedTime($record['collectionVerifiedAt'], 'F d, Y, h:i:s A') : '-';
                $amountCollectedString = '';
                foreach(SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS as $cMethod) {
                    if (array_key_exists($cMethod, $record)) {
                        $amountCollectedString .=  ((trim($amountCollectedString) != '') ? ' + ' : '') . $record[$cMethod] . ' (' . ucwords($cMethod) . ')';
                    }
                }
                $driverString = '';
                $driverString .= '<a href="' . url('/userrole/drivers/view/' . $record['driverId']) . '" class="btn btn-primary btn-clean mr-2" title="View Picker">';
                $driverString .= '<span>' . $record['driver'] . '</span>';
                $driverString .= '</a>';
                $actionLinkUrl = '';
                $actionLinkUrl .= '<a href="' . url('/'. $roleUrlFragment . '/order-view/' . $record['orderRecordId']) . '" target="_blank" class="btn btn-sm btn-primary mr-2 feeder-report-single-order-view-btn"';
                $actionLinkUrl .= ' data-order-id="' . $record['orderRecordId'] . '" data-order-number="' . $record['orderNumber'] . '" title="View Order">View</a>';
                if($amountCollectionEditable === true) {
                    $actionLinkUrl .= '<a href="' . url('/userrole/driver-collection-edit/' . $record['orderRecordId']) . '" target="_blank" class="btn btn-sm btn-primary mr-2 feeder-report-single-order-edit-btn"';
                    $actionLinkUrl .= ' data-order-id="' . $record['orderRecordId'] . '" data-order-number="' . $record['orderNumber'] . '" title="Edit Order Amount Collection">Edit</a>';
                }
                if($amountCollectionVerified === false) {
                    $actionLinkUrl .= '<a href="' . url('/userrole/driver-collection-verify/' . $record['orderRecordId']) . '" class="btn btn-sm btn-primary mr-2 feeder-report-single-order-verify-btn"';
                    $actionLinkUrl .= ' data-order-id="' . $record['orderRecordId'] . '" data-order-number="' . $record['orderNumber'] . '" title="Verify Order Amount Collection">Verify</a>';
                }

                $filteredOrderData[] = [
                    'orderRecordId' => $record['orderRecordId'],
                    'orderId' => $record['orderId'],
                    'orderNumber' => $record['orderNumber'],
                    'feeders' => $feedersString,
                    'channel' => $record['channel'],
                    'region' => $record['emirates'],
                    'customerName' => $record['customerName'],
                    'customerPhone' => $record['customerContact'],
                    'orderDeliveryDate' => date('d-m-Y', strtotime($record['orderDeliveryDate'])),
                    'driverDeliveryDate' => date('d-m-Y', strtotime($record['driverDeliveryDate'])),
                    'paymentMethod' => $record['paymentMethod'],
                    'collectionVerified' => $collectionVerifiedString,
                    'initialPay' => $record['initialPay'],
                    'amountCollected' => $amountCollectedString,
                    'totalCollected' => $record['collectedAmount'],
                    'totalPaid' => $record['totalPaid'],
                    'orderTotal' => $record['orderTotal'],
                    'paymentStatus' => $record['paymentStatus'],
                    'collectionVerifiedAt' => $collectionVerifiedAtString,
                    'driver' => $driverString,
                    'deliveredAt' => $serviceHelper->getFormattedTime($record['driverDeliveryAt'], 'F d, Y, h:i:s A'),
                    'orderStatus' => $record['orderStatus'],
                    'customerAddress' => $record['shippingAddress'],
                    'actions' => $actionLinkUrl
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

            $filteredOrderStats = $serviceHelper->getFeederOrderStats($region, $apiChannel, $drivers, $startDate, $endDate, $deliverySlot);
            if (count($filteredOrderStats) <= 0) {
                return back()
                    ->with('error', "There is no record to export the CSV file.");
            }

            $fileName =  "feeders_delivery_report_" . date('d-m-Y', strtotime($startDate)) . "_" . date('d-m-Y', strtotime($endDate)) . ".csv";
            $headers = array(
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$fileName",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            );

            $headingColumns = ["Order Number", "Feeder(s)", "Channel", "Emirates", "Customer Name", "Customer Address", "Customer Phone", "Order Delivery Date", "Driver Delivery Date", "Order Status", "Payment Method", "Initial Pay"];
            $collectionMethods = SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS;
            foreach ($collectionMethods as $methodEl) {
                $headingColumns[] = ucwords($methodEl) . " Collected";
            }
            $headingColumns[] = "Amount Collected";
            $headingColumns[] = "Total Paid";
            $headingColumns[] = "Order Total";
            $headingColumns[] = "Payment Status";
            $headingColumns[] = "Payment Verified";
            $headingColumns[] = "Payment Verified At";

            $callback = function() use($filteredOrderStats, $headingColumns, $collectionMethods, $serviceHelper) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_values($headingColumns));
                if(!empty($filteredOrderStats)) {
                    foreach($filteredOrderStats as $row) {
                        $feedersString = '';
                        if (is_array($row['feedersInvolved']) && (count($row['feedersInvolved']) > 0)) {
                            foreach ($row['feedersInvolved'] as $feederEl) {
                                $feedersString .= ((trim($feedersString) != '') ? ', ' : '') . $feederEl['name'] . ' (#' . $feederEl['id'] . ')';
                            }
                        }
                        $rowDataArray = [
                            $row['orderNumber'],
                            $feedersString,
                            $row['channel'],
                            $row['emirates'],
                            $row['customerName'],
                            $row['shippingAddress'],
                            $row['customerContact'],
                            date('d-m-Y', strtotime($row['orderDeliveryDate'])),
                            date('d-m-Y', strtotime($row['driverDeliveryDate'])),
                            $row['orderStatus'],
                            $row['paymentMethod'],
                            $row['initialPay'],
                        ];
                        foreach ($collectionMethods as $methodEl) {
                            $rowDataArray[] = (array_key_exists($methodEl, $row)) ? $row[$methodEl] : '0';
                        }
                        $rowDataArray[] = $row['collectedAmount'];
                        $rowDataArray[] = $row['totalPaid'];
                        $rowDataArray[] = $row['orderTotal'];
                        $rowDataArray[] = $row['paymentStatus'];
                        $rowDataArray[] = ($row['collectionVerified'] == '1') ? 'Yes' : 'No';
                        $rowDataArray[] = (!is_null($row['collectionVerifiedAt'])) ? $serviceHelper->getFormattedTime($row['collectionVerifiedAt'], 'F d, Y, h:i:s A') : '-';
                        fputcsv($file, $rowDataArray);
                    }
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        }

    }

    public function yangoLogisticsReportList() {

        $userRoleObj = new UserRole();
        $drivers = $userRoleObj->allDrivers();

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Drivers Report - Yango Logistics';

        $serviceHelper = new UserRoleServiceHelper();

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();

        $collectionMethods = SaleOrderAmountCollection::PAYMENT_COLLECTION_METHODS;

        $currentRole = null;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $currentRole = $sessionUser['roleCode'];
        }

        return view('userrole::drivers.yango-report', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'todayDate',
            'availableApiChannels',
            'deliveryTimeSlots',
            'serviceHelper',
            'drivers',
            'collectionMethods',
            'currentRole',
        ));

    }

    public function yangoLogisticsReportFilter(Request $request) {

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

        $drivers = (
            $request->has('driver_values')
            && (trim($request->input('driver_values')) != '')
        ) ? explode(',', trim($request->input('driver_values'))) : [];

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

        $datePurpose = (
            $request->has('date_purpose_filter')
            && (trim($request->input('date_purpose_filter')) != '')
            && (
                ((int)trim($request->input('date_purpose_filter')) === 1)
                || ((int)trim($request->input('date_purpose_filter')) === 2)
            )
        ) ? (int)trim($request->input('date_purpose_filter')) : 1;

        if ($methodAction == 'datatable') {

            $filteredOrderStats = $serviceHelper->getYangoDriverOrderStats($region, $apiChannel, $drivers, $startDate, $endDate, $deliverySlot, $datePurpose);

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

                $currentRole = null;
                if (session()->has('authUserData')) {
                    $sessionUser = session('authUserData');
                    $currentRole = $sessionUser['roleCode'];
                }

                $roleUrlFragment = '';
                if (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_ADMIN)) {
                    $roleUrlFragment = 'admin';
                } elseif (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_SUPERVISOR)) {
                    $roleUrlFragment = 'supervisor';
                } elseif (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_PICKER)) {
                    $roleUrlFragment = 'picker';
                } elseif (!is_null($currentRole) && ($currentRole === UserRole::USER_ROLE_DRIVER)) {
                    $roleUrlFragment = 'driver';
                }

                $actionLinkUrl = (trim($roleUrlFragment) != '') ?  url('/' . trim($roleUrlFragment) . '/order-view/' . $record['orderRecordId']) : 'javascript:void(0)';

                $filteredOrderData[] = [
                    'recordId' => $record['orderRecordId'],
                    'orderNumber' => $record['orderNumber'],
                    'channel' => $record['channel'],
                    'region' => $record['emirates'],
                    'latitude' => $record['shippingLatitude'],
                    'longitude' => $record['shippingLongitude'],
                    'orderAssignmentDate' => $record['driverDeliveryDate'],
                    'orderDeliveryDate' => $record['orderDeliveryDate'],
                    'orderDeliverySlot' => $record['orderDeliverySlot'],
                    'customerName' => $record['customerName'],
                    'customerAddress' => $record['shippingAddress'],
                    'customerPhone' => $record['customerContact'],
                    'paymentMethod' => $record['paymentMethod'],
                    'orderTotal' => number_format($record['orderTotal'], 2)  . " " . $record['orderCurrency'],
                    'paymentStatus' => ucwords($record['paymentStatus']),
                    'codAmount' => number_format($record['totalDue'], 2) . " " . $record['orderCurrency'],
                    'deliveryNote' => $record['orderDeliveryNote'],
                    'orderStatus' => ucwords($record['orderStatus']),
                    'actions' => $actionLinkUrl
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

            $filteredOrderStats = $serviceHelper->getYangoDriverOrderStats($region, $apiChannel, $drivers, $startDate, $endDate, $deliverySlot, $datePurpose);
            if (count($filteredOrderStats) <= 0) {
                return back()
                    ->with('error', "There is no record to export the CSV file.");
            }

            $fileName =  "yango_delivery_report_" . date('d-m-Y', strtotime($startDate)) . "_" . date('d-m-Y', strtotime($endDate)) . ".csv";
            $headers = array(
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$fileName",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            );

            $headingColumns = [
                "Date",
                "Order Id",
                "Customer Name",
                "Emirate",
                "Latitude",
                "Longitude",
                "Phone Number",
                "Time Slot",
                "Customer Address",
                "Payment Method",
                "COD Amount",
                "Delivery Note"
            ];

            $callback = function() use($filteredOrderStats, $headingColumns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_values($headingColumns));
                if(!empty($filteredOrderStats)) {
                    foreach($filteredOrderStats as $row) {
                        $rowDataArray = [
                            date('d-m-Y', strtotime($row['orderDeliveryDate'])),
                            $row['orderNumber'],
                            $row['customerName'],
                            $row['emirates'],
                            $row['shippingLatitude'],
                            $row['shippingLongitude'],
                            $row['customerContact'],
                            $row['orderDeliverySlot24'],
                            $row['shippingAddress'],
                            $row['paymentMethod'],
                            number_format($row['totalDue'], 2) . " " . $row['orderCurrency'],
                            $row['orderDeliveryNote'],
                        ];
                        fputcsv($file, $rowDataArray);
                    }
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        }

    }

}
