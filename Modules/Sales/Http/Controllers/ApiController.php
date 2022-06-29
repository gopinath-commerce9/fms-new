<?php

namespace Modules\Sales\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\API\Entities\MobileAppUser;
use Modules\API\Http\Controllers\BaseController;
use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrderAmountCollection;
use Validator;
use Hash;
use Modules\Sales\Entities\SalesApiServiceHelper;
use Modules\Sales\Entities\SaleOrder;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;
use Modules\API\Entities\ApiServiceHelper;

class ApiController extends BaseController
{

    public function getCollectionVerifiedOrders(Request $request) {

        $validator = Validator::make($request->all() , [
            'lastVerifiedDate'   => ['required', 'date'],
            'limit' => ['nullable', 'numeric'],
        ], [
            'lastVerifiedDate.required' => 'The Last Verified Date should be provided.',
            'lastVerifiedDate.date' => 'The Last Verified Date should be a Date Value.',
            'limit.numeric' => 'Limit should be numeric value.',
        ]);
        if ($validator->fails()) {
            $errMessage = implode(" | ", $validator->errors()->all());
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $postData = $validator->validated();
        $lastVerifiedDate = date('Y-m-d H:i:s', strtotime($postData['lastVerifiedDate']));
        $limitClean = (array_key_exists('limit', $postData) && !is_null($postData['limit'])) ? (int)$postData['limit'] : 100;

        $serviceHelper = new SalesApiServiceHelper();

        $filteredOrderData = $serviceHelper->getCollectionVerifiedSaleOrders($lastVerifiedDate, $limitClean);
        if (count($filteredOrderData) == 0) {
            $errMessage = 'Sale Order details not found!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        return $this->sendResponse($filteredOrderData, 'The Sale Order details listed successfully!');

    }

    public function generateAdminToken(Request $request) {

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

        if (!$roleData->isAdmin()) {
            $errMessage = 'The User is not an Admin!';
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

    public function createOrder(Request $request) {

        $serviceHelper = new SalesApiServiceHelper();

        $requestHostName = $request->getSchemeAndHttpHost();

        $userId = 0;
        /*$user = auth()->user();
        $userId = $user->id;
        $validStatus = $serviceHelper->isValidApiUser($userId);
        if ($validStatus['success'] === false) {
            return $this->sendError($validStatus['message'], ['error' => $validStatus['message']], $validStatus['httpStatus']);
        }*/

        $givenOrderId = (
            $request->has('orderId')
            && (trim($request->input('orderId')) != '')
            && is_numeric($request->input('orderId'))
            && ((int)trim($request->input('orderId')) > 0)
        ) ? (int)trim($request->input('orderId')) : null;

        $givenOrderNumber = (
            $request->has('orderNumber')
            && (trim($request->input('orderNumber')) != '')
        ) ? trim($request->input('orderNumber')) : null;

        if (is_null($givenOrderId)) {
            $errMessage = 'Sale Order Not found!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $restApiService = new RestApiService();
        $envList = $restApiService->getAllAvailableApiEnvironments();
        $givenEnv = $envList[array_key_first($envList)];
        $givenChannel = '';
        foreach ($envList as $envEl) {
            $restApiService->setApiEnvironment($envEl);
            $channels = $restApiService->getAllAvailableApiChannels();
            foreach ($channels as $channelId => $channelEl) {
                $restApiService->setApiChannel($channelEl);
                $currentBaseUrl = $restApiService->getBaseUrl();
                $requestHostNameClean = $requestHostName . ((substr($requestHostName, - 1) != '/') ? '/' : '');
                if (strcasecmp($currentBaseUrl, $requestHostNameClean) == 0) {
                    $givenEnv = $envEl;
                    $givenChannel = $channelId;
                }
            }
        }

        $returnResult = $serviceHelper->saleOrderSync($givenOrderId, $givenEnv, $givenChannel, $userId);
        if (!$returnResult['status']) {
            return $this->sendError($returnResult['message'], ['error' => $returnResult['message']], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $returnResponse = [];
        if (array_key_exists('errors', $returnResult) && is_array($returnResult['errors']) && (count($returnResult['errors']) > 0)) {
            $returnResponse['errors'] = $returnResult['errors'];
        }
        return $this->sendResponse($returnResponse, 'The Sale Order is synced to the OMS successfully!');

    }

}
