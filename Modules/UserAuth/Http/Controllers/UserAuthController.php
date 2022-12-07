<?php

namespace Modules\UserAuth\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Redirect;
use Auth;
use Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;

class UserAuthController extends Controller
{

    use AuthenticatesUsers;

    /**
     * Where to redirect admins after login.
     *
     * @var string
     */
    protected $redirectRoute = 'dashboard.index';


    /**
     * UserAuthController constructor.
     */
    public function __construct()
    {

    }

    /**
     * Show the Login Page for the System.
     * @return Renderable
     */
    public function index()
    {
        return redirect()->route('userauth.login');
    }

    /**
     * Show the Login Page for the System.
     * @return Renderable
     */
    public function login()
    {
        $pageTitle = 'User Login';
        return view('userauth::login-form', compact('pageTitle'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(Request $request) {

        $validator = Validator::make($request->all() , [
            'username'   => 'required|email',
            'password' => 'required|min:6'
        ], [
            'username.required' => 'EMail Address should be provided.',
            'username.email' => 'EMail Address should be valid.',
            'password.required' => 'Password should be provided.',
            'password.min' => 'Password should be minimum :min characters.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('username'));
        }

        if (Auth::guard('auth-user')->attempt([
            'email' => $request->username,
            'password' => $request->password
        ], false)) {

            $authUserData = Auth::guard('auth-user')->user()->toArray();
            $profileData = null;
            if (!is_null($authUserData['profile_picture']) && ($authUserData['profile_picture'] != '')) {
                $profileData = json_decode($authUserData['profile_picture'], true);
            }
            $userDetails = [
                'id' => $authUserData['id'],
                'name' => $authUserData['name'],
                'email' => $authUserData['email'],
                'roleId' => null,
                'roleCode' => null,
                'roleName' => null,
                'userImage' => (!is_null($profileData)) ? $profileData['path'] : ''
            ];
            $roleMapData = UserRoleMap::firstWhere('user_id', $authUserData['id']);
            if ($roleMapData) {

                if ($roleMapData->is_active === UserRole::ROLE_USER_ACTIVE_NO) {
                    return back()
                        ->with('error', 'The user is in-active!')
                        ->withInput($request->only('email', 'remember'));
                }

                $mappedRoleId = $roleMapData->role_id;
                $roleData = UserRole::find($mappedRoleId);
                if ($roleData) {
                    $userDetails['roleId'] = $roleData->id;
                    $userDetails['roleCode'] = $roleData->code;
                    $userDetails['roleName'] = $roleData->display_name;
                }
            }
            $request->session()->put('authUserData', $userDetails);

            if (!is_null($userDetails['roleCode']) &&  Route::has($userDetails['roleCode'] . '.index')) {
                return redirect()->intended(route($userDetails['roleCode'] . '.index'));
            } else {
                return redirect()->intended(route($this->redirectRoute));
            }

        }

        return back()
            ->with('error', 'The user sign-in failed!')
            ->withInput($request->only('email', 'remember'));

    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function logout(Request $request)
    {
        Auth::guard('auth-user')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('userauth.login');
    }

}
