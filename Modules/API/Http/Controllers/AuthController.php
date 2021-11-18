<?php


namespace Modules\API\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;
use Validator;
use Hash;
use App\Models\User;

class AuthController extends BaseController
{

    public function generateToken(Request $request) {

        $validator = Validator::make($request->all() , [
            'username'   => 'required|email',
            'password' => 'required|min:6',
            'deviceName' => 'required',
        ], [
            'username.required' => 'EMail Address should be provided.',
            'username.email' => 'EMail Address should be valid.',
            'password.required' => 'Password should be provided.',
            'password.min' => 'Password should be minimum :min characters.',
            'deviceName.required' => 'Device Name should be minimum :min characters.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Bad Request.', ['error'=> implode(" | ", $validator->errors())], 400);
        }

        $user = User::where('email', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised'], 401);
        }

        $token = $user->createToken($request->deviceName)->plainTextToken;
        $returnData = [
            'token' => $token,
            'token_type' => 'Bearer',
            'name' => $user->name,
        ];
        return $this->sendResponse($returnData, 'Hi '.$user->name.', welcome to home');

    }

    public function generateSupervisorToken(Request $request) {

        $validator = Validator::make($request->all() , [
            'username'   => 'required|email',
            'password' => 'required|min:6',
            'deviceName' => 'required',
        ], [
            'username.required' => 'EMail Address should be provided.',
            'username.email' => 'EMail Address should be valid.',
            'password.required' => 'Password should be provided.',
            'password.min' => 'Password should be minimum :min characters.',
            'deviceName.required' => 'Device Name should be minimum :min characters.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Bad Request.', ['error'=> implode(" | ", $validator->errors())], 400);
        }

        $user = User::where('email', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised'], 401);
        }

        $roleMapData = UserRoleMap::firstWhere('user_id', $user->id);
        if (!$roleMapData) {
            return $this->sendError('Unauthorised.', ['error'=>'The User does not have a role!'], 401);
        }

        $mappedRoleId = $roleMapData->role_id;
        $roleData = UserRole::find($mappedRoleId);
        if (!$roleData) {
            return $this->sendError('Unauthorised.', ['error'=>'The User does not have a role!'], 401);
        }

        if (!$roleData->isSupervisor()) {
            return $this->sendError('Unauthorised.', ['error'=>'The User is not a Supervisor!'], 401);
        }

        $token = $user->createToken($request->deviceName)->plainTextToken;
        $returnData = [
            'token' => $token,
            'token_type' => 'Bearer',
            'name' => $user->name,
        ];
        return $this->sendResponse($returnData, 'Hi '.$user->name.', welcome to home');

    }

    public function generatePickerToken(Request $request) {

        $validator = Validator::make($request->all() , [
            'username'   => 'required|email',
            'password' => 'required|min:6',
            'deviceName' => 'required',
        ], [
            'username.required' => 'EMail Address should be provided.',
            'username.email' => 'EMail Address should be valid.',
            'password.required' => 'Password should be provided.',
            'password.min' => 'Password should be minimum :min characters.',
            'deviceName.required' => 'Device Name should be minimum :min characters.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Bad Request.', ['error'=> implode(" | ", $validator->errors())], 400);
        }

        $user = User::where('email', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised'], 401);
        }

        $roleMapData = UserRoleMap::firstWhere('user_id', $user->id);
        if (!$roleMapData) {
            return $this->sendError('Unauthorised.', ['error'=>'The User does not have a role!'], 401);
        }

        $mappedRoleId = $roleMapData->role_id;
        $roleData = UserRole::find($mappedRoleId);
        if (!$roleData) {
            return $this->sendError('Unauthorised.', ['error'=>'The User does not have a role!'], 401);
        }

        if (!$roleData->isPicker()) {
            return $this->sendError('Unauthorised.', ['error'=>'The User is not a Picker!'], 401);
        }

        $token = $user->createToken($request->deviceName)->plainTextToken;
        $returnData = [
            'token' => $token,
            'token_type' => 'Bearer',
            'name' => $user->name,
        ];
        return $this->sendResponse($returnData, 'Hi '.$user->name.', welcome to home');

    }

    public function generateDriverToken(Request $request) {

        $validator = Validator::make($request->all() , [
            'username'   => 'required|email',
            'password' => 'required|min:6',
            'deviceName' => 'required',
        ], [
            'username.required' => 'EMail Address should be provided.',
            'username.email' => 'EMail Address should be valid.',
            'password.required' => 'Password should be provided.',
            'password.min' => 'Password should be minimum :min characters.',
            'deviceName.required' => 'Device Name should be minimum :min characters.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Bad Request.', ['error'=> implode(" | ", $validator->errors())], 400);
        }

        $user = User::where('email', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised'], 401);
        }

        $roleMapData = UserRoleMap::firstWhere('user_id', $user->id);
        if (!$roleMapData) {
            return $this->sendError('Unauthorised.', ['error'=>'The User does not have a role!'], 401);
        }

        $mappedRoleId = $roleMapData->role_id;
        $roleData = UserRole::find($mappedRoleId);
        if (!$roleData) {
            return $this->sendError('Unauthorised.', ['error'=>'The User does not have a role!'], 401);
        }

        if (!$roleData->isDriver()) {
            return $this->sendError('Unauthorised.', ['error'=>'The User is not a Driver!'], 401);
        }

        $token = $user->createToken($request->deviceName)->plainTextToken;
        $returnData = [
            'token' => $token,
            'token_type' => 'Bearer',
            'name' => $user->name,
        ];
        return $this->sendResponse($returnData, 'Hi '.$user->name.', welcome to home');

    }

    public function userDetails() {

        $returnData = [
            'user' => auth()->user()->toArray(),
        ];

        return $this->sendResponse($returnData, 'The User Details fetched successfully');

    }

    public function logout(Request $request) {

        $validator = Validator::make($request->all() , [
            'deviceName' => 'required',
        ], [
            'deviceName.required' => 'Device Name should be minimum :min characters.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Logout Failed.', ['error'=> implode(" | ", $validator->errors())], 400);
        }

        auth()->user()->tokens()->where('name', $request->deviceName)->delete();

        return $this->sendResponse([], 'You have successfully logged out and the token was successfully deleted');

    }

}
