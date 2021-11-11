<?php

namespace Modules\UserAuth\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Input;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;
use Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Modules\UserAuth\Entities\UserServiceHelper;

class UserCrudController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Users';

        $userList = User::all();
        $usersTotal = $userList->count();

        $serviceHelper = new UserServiceHelper();

        return view('userauth::users.list', compact(
            'pageTitle',
            'pageSubTitle',
            'userList',
            'usersTotal',
            'serviceHelper'
        ));

    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'New User';

        $userRoles = UserRole::all();
        $serviceHelper = new UserServiceHelper();

        return view('userauth::users.new', compact(
            'pageTitle',
            'pageSubTitle',
            'userRoles',
            'serviceHelper'
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
            'user_name' => ['required', 'string', 'min:3', 'max:255'],
            'user_email' => [
                'required',
                'string',
                'regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
                'max:255',
                'unique:users,email'
            ],
            'user_contact' => ['nullable', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'],
            'profile_avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:200'],
            'profile_avatar_remove' => ['nullable', 'boolean'],
            'user_role' => ['nullable', 'numeric', 'integer', 'exists:user_roles,id'],
            'user_password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'user_name.required' => 'The User Name should be provided.',
            'user_name.string' => 'The User Name should be a string value.',
            'user_name.min' => 'The User Name should be minimum :min characters.',
            'user_name.max' => 'The User Name should not exceed :max characters.',
            'user_email.required' => 'The User E-Mail should be provided.',
            'user_email.string' => 'The User E-Mail should be a string value.',
            'user_email.regex' => 'The User E-Mail should be valid.',
            'user_email.max' => 'The User E-Mail should not exceed :max characters.',
            'user_email.unique' => 'The User E-Mail is already taken.',
            'user_password.required' => 'The Password should be provided.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('user_name', 'user_email', 'user_contact', 'user_password', 'user_password_confirmation'));
        }

        $postData = $validator->validated();

        $givenUserRole = null;
        $roleAssiged = false;
        if (array_key_exists('user_role', $postData)) {
            $roleAssiged = true;
            if (!is_null($postData['user_role'])) {
                $givenUserRole = UserRole::find($postData['user_role']);
                if(!$givenUserRole) {
                    return back()
                        ->with('error', 'The User Role does not exist!')
                        ->withInput($request->only('user_name'));
                }
            }
        }

        try {

            $newUser = new User();
            $newUser->name = trim($postData['user_name']);
            $newUser->email = trim($postData['user_email']);
            $newUser->contact_number = trim($postData['user_contact']);
            $newUser->password = Hash::make($postData['user_password']);
            $newUser->saveQuietly();

            if($request->hasFile('profile_avatar')){

                $uploadFileObj = $request->file('profile_avatar');
                $givenFileName = $uploadFileObj->getClientOriginalName();
                $givenFileNameExt = $uploadFileObj->extension();
                $proposedFileName = 'userAvatar_' . $newUser->id. '_' . date('YndHis') . '.' . $givenFileNameExt;
                $uploadPath = $uploadFileObj->storeAs('media/images/users', $proposedFileName, 'public');
                if ($uploadPath) {
                    $newUser->profile_picture = json_encode([
                        'name' => $givenFileName,
                        'ext' => $givenFileNameExt,
                        'path' => $proposedFileName
                    ]);
                    $newUser->saveQuietly();
                }

            }

            if ($roleAssiged) {
                if (is_null($givenUserRole)) {
                    UserRoleMap::where('user_id', $newUser->id)
                        ->delete();
                } else {
                    $newRoleMap = UserRoleMap::updateOrCreate(
                        ['user_id' => $newUser->id],
                        ['role_id' => $givenUserRole->id, 'is_active' => 1]
                    );
                }
            }

            return redirect()->route('users.index')->with('success', 'The User is added successfully!');

        } catch(Exception $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput($request->only('user_name', 'user_email', 'user_password', 'user_password_confirmation'));
        }

    }

    /**
     * Show the specified resource.
     * @param int $userId
     * @return Renderable
     */
    public function show($userId)
    {

        if (is_null($userId) || !is_numeric($userId) || ((int)$userId <= 0)) {
            return back()
                ->with('error', 'The User Id input is invalid!');
        }

        $givenUserData = User::find($userId);
        if(!$givenUserData) {
            return back()
                ->with('error', 'The User does not exist!');
        }

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'User #' . $givenUserData->id;

        return view('userauth::users.view', compact(
            'pageTitle',
            'pageSubTitle',
            'givenUserData'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $userId
     * @return Renderable
     */
    public function edit($userId)
    {

        if (is_null($userId) || !is_numeric($userId) || ((int)$userId <= 0)) {
            return back()
                ->with('error', 'The User Id input is invalid!');
        }

        $givenUserData = User::find($userId);
        if(!$givenUserData) {
            return back()
                ->with('error', 'The User does not exist!');
        }

        $userRoles = UserRole::all();
        $serviceHelper = new UserServiceHelper();

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Edit User #' . $givenUserData->email;

        return view('userauth::users.edit', compact(
            'pageTitle',
            'pageSubTitle',
            'givenUserData',
            'userRoles',
            'serviceHelper'
        ));

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $userId
     * @return Renderable
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $userId)
    {

        if (is_null($userId) || !is_numeric($userId) || ((int)$userId <= 0)) {
            return back()
                ->with('error', 'The User Id input is invalid!');
        }

        $givenUserData = User::find($userId);
        if(!$givenUserData) {
            return back()
                ->with('error', 'The User does not exist!');
        }

        $validator = Validator::make($request->all() , [
            'user_name' => ['required', 'string', 'min:3', 'max:255'],
            'user_contact' => ['nullable', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'],
            'profile_avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:200'],
            'profile_avatar_remove' => ['nullable', 'boolean'],
            'user_role' => ['nullable', 'numeric', 'integer', 'exists:user_roles,id'],
        ], [
            'user_name.required' => 'The User Name should be provided.',
            'user_name.string' => 'The User Name should be a string value.',
            'user_name.min' => 'The User Name should be minimum :min characters.',
            'user_name.max' => 'The User Name should not exceed :max characters.',
        ]);


        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('user_name'));
        }

        $postData = $validator->validated();

        $givenUserRole = null;
        $roleAssiged = false;
        if (array_key_exists('user_role', $postData)) {
            $roleAssiged = true;
            if (!is_null($postData['user_role'])) {
                $givenUserRole = UserRole::find($postData['user_role']);
                if(!$givenUserRole) {
                    return back()
                        ->with('error', 'The User Role does not exist!')
                        ->withInput($request->only('user_name'));
                }
            }
        }

        if ($givenUserData->isDefaultUser() && $roleAssiged && (is_null($givenUserRole) || ($givenUserRole->code != UserRole::USER_ROLE_ADMIN))) {
            return back()
                ->with('error', "The Role of the default User '". $givenUserData->email . " 'cannot be changed!")
                ->withInput($request->only('user_name'));
        }

        try {

            $serviceHelper = new UserServiceHelper();

            $givenUserData->name = trim($postData['user_name']);
            $givenUserData->contact_number = trim($postData['user_contact']);

            $profileData = null;
            if (!is_null($givenUserData->profile_picture) && ($givenUserData->profile_picture != '')) {
                $profileData = json_decode($givenUserData->profile_picture, true);
            }

            if (!is_null($postData['profile_avatar_remove']) && ($postData['profile_avatar_remove'] == '1')) {
                $profilePicUrl = (!is_null($profileData)) ? $profileData['path'] : '';
                $serviceHelper->deleteUserImage($profilePicUrl);
                $givenUserData->profile_picture = null;
            }
            if($request->hasFile('profile_avatar')){

                $profilePicUrl = (!is_null($profileData)) ? $profileData['path'] : '';
                $serviceHelper->deleteUserImage($profilePicUrl);

                $uploadFileObj = $request->file('profile_avatar');
                $givenFileName = $uploadFileObj->getClientOriginalName();
                $givenFileNameExt = $uploadFileObj->extension();
                $proposedFileName = 'userAvatar_' . $givenUserData->id. '_' . date('YndHis') . '.' . $givenFileNameExt;
                $uploadPath = $uploadFileObj->storeAs('media/images/users', $proposedFileName, 'public');
                if ($uploadPath) {
                    $givenUserData->profile_picture = json_encode([
                        'name' => $givenFileName,
                        'ext' => $givenFileNameExt,
                        'path' => $proposedFileName
                    ]);
                }

            }

            $givenUserData->saveQuietly();

            if ($roleAssiged) {
                if (is_null($givenUserRole)) {
                    UserRoleMap::where('user_id', $givenUserData->id)
                        ->delete();
                } else {
                    $newRoleMap = UserRoleMap::updateOrCreate(
                        ['user_id' => $givenUserData->id],
                        ['role_id' => $givenUserRole->id, 'is_active' => 1]
                    );
                }
            }

            return redirect()->route('users.index')->with('success', 'The User is updated successfully!');

        } catch(Exception $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput($request->only('user_name'));
        }

    }

    /**
     * Remove the specified resource from storage.
     * @param int $userId
     * @return Renderable
     */
    public function destroy($userId)
    {

        if (is_null($userId) || !is_numeric($userId) || ((int)$userId <= 0)) {
            return back()
                ->with('error', 'The User Id input is invalid!');
        }

        $givenUserData = User::find($userId);
        if(!$givenUserData) {
            return back()
                ->with('error', 'The User does not exist!');
        }

        if ($givenUserData->isDefaultUser()) {
            return back()
                ->with('error', "The Default User '" . $givenUserData->email . "' cannot be deleted!");
        }

        try {

            User::destroy($userId);
            return redirect()->route('users.index')->with('success', 'The User is deleted successfully!');

        } catch(Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }

    }
}
