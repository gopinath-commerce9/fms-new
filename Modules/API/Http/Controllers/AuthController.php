<?php

namespace Modules\API\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
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
            $errMessage = implode(" | ", $validator->errors()->all());
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
            $errMessage = implode(" | ", $validator->errors()->all());
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
            $errMessage = implode(" | ", $validator->errors()->all());
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

        $serviceHelper = new ApiServiceHelper();

        $user = auth()->user();
        $userId = $user->id;
        $givenUserData = User::find($userId);
        if(!$givenUserData) {
            $errMessage = 'The User does not exist!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $profilePicUrl = '';
        if (!is_null($givenUserData->profile_picture) && ($givenUserData->profile_picture != '')) {
            $dpData = json_decode($givenUserData->profile_picture, true);
            $profilePicUrlPath = $dpData['path'];
            $profilePicUrl = $serviceHelper->getUserImageUrl($profilePicUrlPath);
        }

        $returnData = [
            'userId' => $givenUserData->id,
            'userName' => $givenUserData->name,
            'userEmail' => $givenUserData->email,
            'userContact' => $givenUserData->contact_number,
            'userPicture' => $profilePicUrl,
        ];

        return $this->sendResponse($returnData, 'The User Details fetched successfully');

    }

    public function profileUpdate(Request $request)
    {

        $user = auth()->user();
        $userId = $user->id;
        $givenUserData = User::find($userId);
        if(!$givenUserData) {
            $errMessage = 'The User does not exist!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $validator = Validator::make($request->all() , [
            'userName' => ['required', 'string', 'min:3', 'max:255'],
            'userContact' => ['required', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10', 'max:20'],
            'profileAvatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:200'],
            'profileAvatarRemove' => ['nullable', 'boolean']
        ], [
            'userName.required' => 'The User Name should be provided.',
            'userName.string' => 'The User Name should be a string value.',
            'userName.min' => 'The User Name should be minimum :min characters.',
            'userName.max' => 'The User Name should not exceed :max characters.',
            'userContact.required' => 'The User Contact Number should be provided.',
            'userContact.min' => 'The User Contact should be minimum :min characters.',
            'userContact.max' => 'The User Contact should not exceed :max characters.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors()->all());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }
        $postData = $validator->validated();

        $serviceHelper = new ApiServiceHelper();

        try {

            $givenUserData->name = trim($postData['userName']);
            $givenUserData->contact_number = trim($postData['userContact']);

            $profileData = null;
            if (!is_null($givenUserData->profile_picture) && ($givenUserData->profile_picture != '')) {
                $profileData = json_decode($givenUserData->profile_picture, true);
            }

            if (!is_null($postData['profileAvatarRemove']) && ($postData['profileAvatarRemove'] == '1')) {
                $profilePicUrl = (!is_null($profileData)) ? $profileData['path'] : '';
                $serviceHelper->deleteUserImage($profilePicUrl);
                $givenUserData->profile_picture = null;
            }

            if($request->hasFile('profileAvatar')){

                $profilePicUrl = (!is_null($profileData)) ? $profileData['path'] : '';
                $serviceHelper->deleteUserImage($profilePicUrl);

                $uploadFileObj = $request->file('profileAvatar');
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

            $returnData = [];
            return $this->sendResponse($returnData, 'The User Profile is updated successfully!');

        } catch(Exception $e) {
            $errMessage = $e->getMessage();
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_INTERNAL_SERVER_ERROR);
        }

    }

    public function changePassword(Request $request) {

        $user = auth()->user();
        $userId = $user->id;
        $givenUserData = User::find($userId);
        if(!$givenUserData) {
            $errMessage = 'The User does not exist!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all() , [
            'userPassword' => ['required'],
            'newPassword' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'userPassword.required' => 'The Current Password should be provided.',
            'newPassword.required' => 'The New Password should be provided.',
        ]);

        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors()->all());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $postData = $validator->validated();

        if (!Hash::check($postData['userPassword'], $givenUserData->password)) {
            $errMessage = 'The current Password is not valid!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $givenUserData->password = Hash::make($postData['newPassword']);
        $givenUserData->saveQuietly();

        $returnData = [];
        return $this->sendResponse($returnData, 'The User Password is updated successfully!');

    }

    public function setOneSignalPlayerId(Request $request) {

        $validator = Validator::make($request->all() , [
            'playerId'   => ['required', 'string'],
        ], [
            'playerId.required' => 'OneSignal Player Id should be provided.',
            'playerId.string' => 'OneSignal Player Id should be a string.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors()->all());
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
            $errMessage = implode(" | ", $validator->errors()->all());
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
            $errMessage = implode(" | ", $validator->errors()->all());
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

        $user = auth()->user();
        $userId = $user->id;
        $givenUserData = User::find($userId);
        if(!$givenUserData) {
            $errMessage = 'The User does not exist!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $validator = Validator::make($request->all() , [
            'deviceName' => ['required'],
        ], [
            'deviceName.required' => 'Device Name should not be empty.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors()->all());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        /*$user->tokens()->delete();*/
        /*$user->tokens()->where('name', $request->deviceName)->delete();*/
        $user->currentAccessToken()->delete();

        $mobileAppUser = MobileAppUser::updateOrCreate([
            'user_id' => $givenUserData->id
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
