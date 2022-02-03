<?php

namespace Modules\UserRole\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Input;
use Modules\UserRole\Entities\UserRole;
use Redirect;
use Illuminate\Support\Facades\Validator;
use Modules\UserRole\Entities\PermissionMap;
use Modules\UserRole\Entities\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'User Role Permissions';

        $userPermissionList = Permission::all();

        $userPermissionsTotal = $userPermissionList->count();

        return view('userrole::permissions.list', compact(
            'pageTitle',
            'pageSubTitle',
            'userPermissionList',
            'userPermissionsTotal'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'New User Role Permission';

        return view('userrole::permissions.new', compact(
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
            'permission_code'   => ['required', 'regex:/^[a-zA-Z0-9\-\_\.]+$/i'],
            'permission_name' => ['nullable', 'string', 'min:6'],
            'permission_desc' => ['nullable', 'string', 'min:6'],
            'permission_active' => ['required', 'boolean'],
        ], [
            'permission_code.required' => 'The Role Code should be provided.',
            'permission_code.regex' => 'The Role Code should contain only alphabets, numbers, dashes(-), dots(.) or underscores(_).',
            'permission_name.string' => 'The Role Name should be a string value.',
            'permission_name.min' => 'The Role Name should be minimum :min characters.',
            'permission_desc.string' => 'The Role Description should be a string value.',
            'permission_desc.min' => 'The Role Description should be minimum :min characters.',
            'permission_active.required' => 'The Role Active status should be provided.',
            'permission_active.boolean' => 'The Role Active status should be boolean ("1" or "0") value.'
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('permission_code', 'permission_name', 'permission_desc', 'permission_active'));
        }

        $postData = $validator->validated();
        $permissionCode = $postData['permission_code'];
        $permissionName = $postData['permission_name'];
        $permissionDesc = $postData['permission_desc'];
        $permissionActive = $postData['permission_active'];

        $cleanPermissionCode = strtolower(str_replace(' ', '.', trim($permissionCode)));

        if (Permission::firstWhere('code', $cleanPermissionCode)) {
            return back()
                ->with('error', 'The User Permission Code is already used!')
                ->withInput($request->only('permission_code', 'permission_name', 'permission_desc', 'permission_active'));
        }

        $cleanPermissionName = ($permissionName) ? $permissionName : ucwords(str_replace('.', ' ', $cleanPermissionCode));

        try {

            $permissionObj = new Permission();
            $permissionObj->code = $cleanPermissionCode;
            $permissionObj->display_name = $cleanPermissionName;
            $permissionObj->description = $permissionDesc;
            $permissionObj->is_active = $permissionActive;
            $permissionObj->save();

            return redirect()->route('permissions.index')->with('success', 'The User Permission is added successfully!');

        } catch(Exception $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput($request->only('permission_code', 'permission_name', 'permission_desc', 'permission_active'));
        }

    }

    /**
     * Show the specified resource.
     * @param int $pId
     * @return Renderable
     */
    public function show($pId)
    {

        if (is_null($pId) || !is_numeric($pId) || ((int)$pId <= 0)) {
            return back()
                ->with('error', 'The User Permission Id input is invalid!');
        }

        $givenUserPermission = Permission::find($pId);
        if(!$givenUserPermission) {
            return back()
                ->with('error', 'The User Permission does not exist!');
        }

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'User Permission #' . $givenUserPermission->code;

        return view('userrole::permissions.view', compact(
            'pageTitle',
            'pageSubTitle',
            'givenUserPermission'
        ));

    }

    /**
     * Show the form for editing the specified resource.
     * @param int $pId
     * @return Renderable
     */
    public function edit($pId)
    {

        if (is_null($pId) || !is_numeric($pId) || ((int)$pId <= 0)) {
            return back()
                ->with('error', 'The User Permission Id input is invalid!');
        }

        $givenUserPermission = Permission::find($pId);
        if(!$givenUserPermission) {
            return back()
                ->with('error', 'The User Permission does not exist!');
        }

        $givenRoleList = UserRole::all();

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Edit User Permission #' . $givenUserPermission->code;

        return view('userrole::permissions.edit', compact(
            'pageTitle',
            'pageSubTitle',
            'givenUserPermission',
            'givenRoleList'
        ));

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $pId
     * @return Renderable
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $pId)
    {

        if (is_null($pId) || !is_numeric($pId) || ((int)$pId <= 0)) {
            return back()
                ->with('error', 'The User Permission Id input is invalid!');
        }

        $targetPermissionObj = Permission::find($pId);
        if(!$targetPermissionObj) {
            return back()
                ->with('error', 'The User Permission does not exist!');
        }

        $validator = Validator::make($request->all() , [
            'permission_name' => ['nullable', 'string', 'min:6'],
            'permission_desc' => ['nullable', 'string', 'min:6'],
            'permission_active' => ['required', 'boolean'],
            'permission_map' => ['nullable', 'array'],
            'permission_map.*.active' => ['boolean'],
            'permission_map.*.permitted' => ['boolean'],
        ], [
            'permission_name.string' => 'The Role Name should be a string value.',
            'permission_name.min' => 'The Role Name should be minimum :min characters.',
            'permission_desc.string' => 'The Role Description should be a string value.',
            'permission_desc.min' => 'The Role Description should be minimum :min characters.',
            'permission_active.required' => 'The Role Active status should be provided.',
            'permission_active.boolean' => 'The Role Active status should be boolean ("1" or "0") value.'
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('permission_name', 'permission_desc', 'permission_active'));
        }

        $postData = $validator->validated();
        $permissionName = $postData['permission_name'];
        $permissionDesc = $postData['permission_desc'];
        $permissionActive = $postData['permission_active'];
        $permissionMapData = (
            array_key_exists('permission_map', $postData)
            && !is_null($postData['permission_map'])
            && is_array($postData['permission_map'])
            && (count($postData['permission_map']) > 0)
        ) ? $postData['permission_map'] : [];

        if ($targetPermissionObj->isDefaultPermission() && (($permissionActive == 0) || ($permissionActive === false))) {
            return back()
                ->with('error', "The default User Permission cannot be set as 'Inactive'!");
        }

        $cleanPermissionName = ($permissionName) ? $permissionName : ucwords(str_replace('.', ' ', $targetPermissionObj->code));

        try {

            $targetPermissionObj->display_name = $cleanPermissionName;
            $targetPermissionObj->description = $permissionDesc;
            $targetPermissionObj->is_active = $permissionActive;
            $targetPermissionObj->save();

            foreach ($permissionMapData as $postRoleKey => $postRoleMap) {
                if (!is_null($postRoleKey) && is_numeric($postRoleKey) && ((int)$postRoleKey > 0)) {
                    $givenUserRole = UserRole::find($postRoleKey);
                    $possibleStatusValues = [0, 1];
                    if($givenUserRole) {
                        if (
                            array_key_exists('permitted', $postRoleMap)
                            && array_key_exists('active', $postRoleMap)
                            && in_array((int) trim($postRoleMap['permitted']), $possibleStatusValues)
                            && in_array((int) trim($postRoleMap['active']), $possibleStatusValues)
                        ) {
                            if (!$givenUserRole->isAdmin() || !$targetPermissionObj->isDefaultPermission()) {
                                $newPermissionMap = PermissionMap::updateOrCreate([
                                    'role_id' => $givenUserRole->id,
                                    'permission_id' => $targetPermissionObj->id
                                ], [
                                    'permitted' => (((int) $postRoleMap['permitted'] == 1) ? 1 : 0),
                                    'is_active' => (((int) $postRoleMap['active'] === 1) ? 1 : 0)
                                ]);
                            }
                        }
                    }
                }
            }

            return redirect()->route('permissions.index')->with('success', 'The User Permission is updated successfully!');

        } catch(Exception $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput($request->only('permission_name', 'permission_desc', 'permission_active'));
        }

    }

    /**
     * Remove the specified resource from storage.
     * @param int $pId
     * @return Renderable
     */
    public function destroy($pId)
    {

        if (is_null($pId) || !is_numeric($pId) || ((int)$pId <= 0)) {
            return back()
                ->with('error', 'The User Permission Id input is invalid!');
        }

        $targetPermissionObj = Permission::find($pId);
        if(!$targetPermissionObj) {
            return back()
                ->with('error', 'The User Permission does not exist!');
        }

        if ($targetPermissionObj->isDefaultPermission()) {
            return back()
                ->with('error', "The default User Permission cannot be deleted!");
        }

        try {

            Permission::destroy($pId);
            return redirect()->route('permissions.index')->with('success', 'The User Permission is deleted successfully!');

        } catch(Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }

    }
}
