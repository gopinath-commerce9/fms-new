<?php

namespace Modules\UserRole\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Input;
use Modules\UserRole\Entities\Permission;
use Modules\UserRole\Entities\PermissionMap;
use Modules\UserRole\Entities\UserRoleMap;
use Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use Modules\UserRole\Entities\UserRole;

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
}
