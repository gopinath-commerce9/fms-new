<?php

namespace Modules\API\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;
use Validator;
use Hash;
use App\Models\User;
use Modules\API\Entities\MobileAppUser;
use Modules\API\Entities\ApiServiceHelper;

class AuthController extends BaseController
{

    public function generateToken(Request $request) {

        $validator = Validator::make($request->all() , [
            'username'   => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'deviceName' => ['required'],
        ], [
            'username.required' => 'EMail Address should be provided.',
            'username.email' => 'EMail Address should be valid.',
            'password.required' => 'Password should be provided.',
            'password.min' => 'Password should be minimum :min characters.',
            'deviceName.required' => 'Device Name should be minimum :min characters.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $user = User::where('email', $request->username)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            $errMessage = 'User Authentication failed!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $roleMapData = UserRoleMap::firstWhere('user_id', $user->id);
        if (!$roleMapData) {
            $errMessage = 'The User not assigned to any role!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $mappedRoleId = $roleMapData->role_id;
        $roleData = UserRole::find($mappedRoleId);
        if (!$roleData) {
            $errMessage = 'The User not assigned to any role!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $token = $user->createToken($request->deviceName)->plainTextToken;

        $mobileAppUser = MobileAppUser::updateOrCreate([
            'user_id' => $user->id
        ], [
            'role_id' => $roleData->id,
            'access_token' => $token,
            'device_id' => $request->deviceName,
            'logged_in' => 1,
        ]);

        $returnData = [
            'token' => $token,
            'token_type' => 'Bearer',
            'name' => $user->name,
        ];
        return $this->sendResponse($returnData, 'Hi '.$user->name.', welcome to home');

    }

    public function generateSupervisorToken(Request $request) {

        $validator = Validator::make($request->all() , [
            'username'   => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'deviceName' => ['required'],
        ], [
            'username.required' => 'EMail Address should be provided.',
            'username.email' => 'EMail Address should be valid.',
            'password.required' => 'Password should be provided.',
            'password.min' => 'Password should be minimum :min characters.',
            'deviceName.required' => 'Device Name should be minimum :min characters.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $user = User::where('email', $request->username)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            $errMessage = 'User Authentication failed!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $roleMapData = UserRoleMap::firstWhere('user_id', $user->id);
        if (!$roleMapData) {
            $errMessage = 'The User not assigned to any role!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $mappedRoleId = $roleMapData->role_id;
        $roleData = UserRole::find($mappedRoleId);
        if (!$roleData) {
            $errMessage = 'The User not assigned to any role!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        if (!$roleData->isSupervisor()) {
            $errMessage = 'The User is not a Supervisor!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $token = $user->createToken($request->deviceName)->plainTextToken;

        $mobileAppUser = MobileAppUser::updateOrCreate([
            'user_id' => $user->id
        ], [
            'role_id' => $roleData->id,
            'access_token' => $token,
            'device_id' => $request->deviceName,
            'logged_in' => 1,
        ]);

        $returnData = [
            'token' => $token,
            'token_type' => 'Bearer',
            'name' => $user->name,
        ];
        return $this->sendResponse($returnData, 'Hi '.$user->name.', welcome to home');

    }

    public function generatePickerToken(Request $request) {

        $validator = Validator::make($request->all() , [
            'username'   => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'deviceName' => ['required'],
        ], [
            'username.required' => 'EMail Address should be provided.',
            'username.email' => 'EMail Address should be valid.',
            'password.required' => 'Password should be provided.',
            'password.min' => 'Password should be minimum :min characters.',
            'deviceName.required' => 'Device Name should be minimum :min characters.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $user = User::where('email', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            $errMessage = 'User Authentication failed!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $roleMapData = UserRoleMap::firstWhere('user_id', $user->id);
        if (!$roleMapData) {
            $errMessage = 'The User not assigned to any role!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $mappedRoleId = $roleMapData->role_id;
        $roleData = UserRole::find($mappedRoleId);
        if (!$roleData) {
            $errMessage = 'The User not assigned to any role!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        if (!$roleData->isPicker()) {
            $errMessage = 'The User is not a Picker!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $token = $user->createToken($request->deviceName)->plainTextToken;

        $mobileAppUser = MobileAppUser::updateOrCreate([
            'user_id' => $user->id
        ], [
            'role_id' => $roleData->id,
            'access_token' => $token,
            'device_id' => $request->deviceName,
            'logged_in' => 1,
        ]);

        $returnData = [
            'token' => $token,
            'token_type' => 'Bearer',
            'name' => $user->name,
        ];
        return $this->sendResponse($returnData, 'Hi '.$user->name.', welcome to home');

    }

    public function userDetails() {

        $user = auth()->user();
        $userId = $user->id;

        $returnData = [
            'user' => $user->toArray(),
        ];

        return $this->sendResponse($returnData, 'The User Details fetched successfully');

    }

    public function setOneSignalPlayerId(Request $request) {

        $validator = Validator::make($request->all() , [
            'playerId'   => ['required', 'string'],
        ], [
            'playerId.required' => 'OneSignal Player Id should be provided.',
            'playerId.string' => 'OneSignal Player Id should be a string.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $postData = $validator->validated();

        $user = auth()->user();
        $mobileAppUser = MobileAppUser::firstWhere('user_id', $user->id);
        if ($mobileAppUser) {
            $mobileAppUser->update([
                'onesignal_player_id' => $postData['playerId'],
            ]);
        }

        $returnData = [];
        return $this->sendResponse($returnData, 'OneSignal Player Id successfully saved for the user.');

    }

    public function setFirebaseTokenId(Request $request) {

        $validator = Validator::make($request->all() , [
            'tokenId'   => ['required', 'string'],
        ], [
            'tokenId.required' => 'Firebase Token Id should be provided.',
            'tokenId.string' => 'Firebase Token Id should be a string.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $postData = $validator->validated();

        $user = auth()->user();
        $mobileAppUser = MobileAppUser::firstWhere('user_id', $user->id);
        if ($mobileAppUser) {
            $mobileAppUser->update([
                'firebase_token_id' => $postData['tokenId'],
            ]);
        }

        $returnData = [];
        return $this->sendResponse($returnData, 'Firebase Token Id successfully saved for the user.');

    }

    public function setUserLocationCoordinates(Request $request) {

        $validator = Validator::make($request->all() , [
            'latitude'   => ['required', 'numeric'],
            'longitude'   => ['required', 'numeric'],
        ], [
            'latitude.required' => 'Latitude should be provided.',
            'latitude.numeric' => 'Latitude should be a numeric value.',
            'longitude.required' => 'Longitude should be provided.',
            'longitude.numeric' => 'Longitude should be a numeric value.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $postData = $validator->validated();

        $user = auth()->user();
        $mobileAppUser = MobileAppUser::firstWhere('user_id', $user->id);
        if ($mobileAppUser) {
            $mobileAppUser->update([
                'last_seen_lat' => $postData['latitude'],
                'last_seen_lng' => $postData['longitude'],
                'last_seen_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $returnData = [];
        return $this->sendResponse($returnData, 'The User Location is successfully saved.');

    }

    public function logout(Request $request) {

        $validator = Validator::make($request->all() , [
            'deviceName' => ['required'],
        ], [
            'deviceName.required' => 'Device Name should be minimum :min characters.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $user = auth()->user();

        auth()->user()->tokens()->where('name', $request->deviceName)->delete();

        $mobileAppUser = MobileAppUser::updateOrCreate([
            'user_id' => $user->id
        ], [
            'access_token' => null,
            'device_id' => null,
            'onesignal_player_id' => null,
            'firebase_token_id' => null,
            'logged_in' => 0,
        ]);

        return $this->sendResponse([], 'You have successfully logged out and the token was successfully deleted');

    }

}
