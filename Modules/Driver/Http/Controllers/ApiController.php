<?php

namespace Modules\Driver\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\API\Entities\MobileAppUser;
use Modules\API\Http\Controllers\BaseController;
use Validator;
use Hash;
use Modules\Driver\Entities\DriverApiServiceHelper;
use Modules\Sales\Entities\SaleOrder;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;
use Modules\API\Entities\ApiServiceHelper;

class ApiController extends BaseController
{

    public function generateDriverToken(Request $request) {

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

        if (!$roleData->isDriver()) {
            $errMessage = 'The User is not a Driver!';
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

    public function getRecentAssignedOrders(Request $request) {

        $serviceHelper = new DriverApiServiceHelper();

        $user = auth()->user();
        $userId = $user->id;
        $validStatus = $serviceHelper->isValidApiUser($userId);
        if ($validStatus['success'] === false) {
            return $this->sendError($validStatus['message'], ['error' => $validStatus['message']], $validStatus['httpStatus']);
        }

        $pageStart = (
            $request->has('page')
            && (trim($request->input('page')) != '')
        ) ? (int)trim($request->input('page')) : 0;

        $pageLength = (
            $request->has('limit')
            && (trim($request->input('limit')) != '')
        ) ? (int)trim($request->input('limit')) : 10;

        $filterStatus = [
            SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH
        ];
        $filteredOrders = $serviceHelper->getDriverOrders('', '', $filterStatus, '', '');
        if (!$filteredOrders) {
            return $this->sendResponse([], 'No Orders Found!');
        }

        $emirates = config('fms.emirates');
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getDriversAllowedStatuses();
        $filteredOrderData = [];
        $totalRec = 0;
        $collectRecStart = $pageStart;
        $collectRecEnd = $collectRecStart + $pageLength;
        $currentRec = -1;
        foreach ($filteredOrders as $record) {
            $deliveryDriverData = $record->currentDriver;
            $canProceed = false;
            $driverDetail = null;
            if ($deliveryDriverData && (count($deliveryDriverData) > 0)) {
                foreach ($deliveryDriverData as $dDeliver) {
                    if (($userId > 0) && !is_null($dDeliver->done_by) && ((int)$dDeliver->done_by == $userId)) {
                        $canProceed = true;
                        $driverDetail = $dDeliver;
                    }
                }
            }
            if ($canProceed) {
                $totalRec++;
                $currentRec++;
                if (($currentRec < $collectRecStart) || ($currentRec >= $collectRecEnd)) {
                    continue;
                }
                $tempRecord = [];
                $tempRecord['recordId'] = $record->id;
                $tempRecord['orderId'] = $record->order_id;
                $tempRecord['incrementId'] = $record->increment_id;
                $apiChannelId = $record->channel;
                $tempRecord['channel'] = $availableApiChannels[$apiChannelId]['name'];
                $emirateId = $record->region_code;
                $tempRecord['region'] = $emirates[$emirateId];
                $tempRecord['city'] = $record->city;
                $tempRecord['zoneId'] = $record->zone_id;
                $shipAddress = $record->shippingAddress;
                $tempRecord['customerName'] = $shipAddress->first_name . ' ' . $shipAddress->last_name;
                $tempRecord['deliveryDate'] = $record->delivery_date;
                $tempRecord['deliveryTimeSlot'] = $record->delivery_time_slot;
                $tempRecord['deliveryPickerTime'] = '';
                $tempRecord['deliveryDriverTime'] = '';
                $orderStatusId = $record->order_status;
                $tempRecord['orderStatus'] = $availableStatuses[$orderStatusId];
                $deliveryPickerData = $record->pickedData;
                if ($deliveryPickerData) {
                    $tempRecord['deliveryPickerTime'] = $deliveryPickerData->done_at;
                }
                if (!is_null($driverDetail)) {
                    $tempRecord['deliveryDriverTime'] = $driverDetail->done_at;
                }
                $filteredOrderData[] = $tempRecord;
            }
        }

        return $this->sendResponse($filteredOrderData, count($filteredOrderData) . ' Order(s) Found!');

    }

    public function getDeliveryOrders(Request $request) {

        $serviceHelper = new DriverApiServiceHelper();

        $user = auth()->user();
        $userId = $user->id;
        $validStatus = $serviceHelper->isValidApiUser($userId);
        if ($validStatus['success'] === false) {
            return $this->sendError($validStatus['message'], ['error' => $validStatus['message']], $validStatus['httpStatus']);
        }

        $pageStart = (
            $request->has('page')
            && (trim($request->input('page')) != '')
        ) ? (int)trim($request->input('page')) : 0;

        $pageLength = (
            $request->has('limit')
            && (trim($request->input('limit')) != '')
        ) ? (int)trim($request->input('limit')) : 10;

        $filterStatus = [
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY
        ];
        $filteredOrders = $serviceHelper->getDriverOrders('', '', $filterStatus, '', '');
        if (!$filteredOrders) {
            return $this->sendResponse([], 'No Orders Found!');
        }

        $emirates = config('fms.emirates');
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getDriversAllowedStatuses();
        $filteredOrderData = [];
        $totalRec = 0;
        $collectRecStart = $pageStart;
        $collectRecEnd = $collectRecStart + $pageLength;
        $currentRec = -1;
        foreach ($filteredOrders as $record) {
            $deliveryDriverData = $record->currentDriver;
            $canProceed = false;
            $driverDetail = null;
            if ($deliveryDriverData && (count($deliveryDriverData) > 0)) {
                foreach ($deliveryDriverData as $dDeliver) {
                    if (($userId > 0) && !is_null($dDeliver->done_by) && ((int)$dDeliver->done_by == $userId)) {
                        $canProceed = true;
                        $driverDetail = $dDeliver;
                    }
                }
            }
            if ($canProceed) {
                $totalRec++;
                $currentRec++;
                if (($currentRec < $collectRecStart) || ($currentRec >= $collectRecEnd)) {
                    continue;
                }
                $tempRecord = [];
                $tempRecord['recordId'] = $record->id;
                $tempRecord['orderId'] = $record->order_id;
                $tempRecord['incrementId'] = $record->increment_id;
                $apiChannelId = $record->channel;
                $tempRecord['channel'] = $availableApiChannels[$apiChannelId]['name'];
                $emirateId = $record->region_code;
                $tempRecord['region'] = $emirates[$emirateId];
                $tempRecord['city'] = $record->city;
                $tempRecord['zoneId'] = $record->zone_id;
                $shipAddress = $record->shippingAddress;
                $tempRecord['customerName'] = $shipAddress->first_name . ' ' . $shipAddress->last_name;
                $tempRecord['deliveryDate'] = $record->delivery_date;
                $tempRecord['deliveryTimeSlot'] = $record->delivery_time_slot;
                $tempRecord['deliveryPickerTime'] = '';
                $tempRecord['deliveryDriverTime'] = '';
                $orderStatusId = $record->order_status;
                $tempRecord['orderStatus'] = $availableStatuses[$orderStatusId];
                $deliveryPickerData = $record->pickedData;
                if ($deliveryPickerData) {
                    $tempRecord['deliveryPickerTime'] = $deliveryPickerData->done_at;
                }
                if (!is_null($driverDetail)) {
                    $tempRecord['deliveryDriverTime'] = $driverDetail->done_at;
                }
                $filteredOrderData[] = $tempRecord;
            }
        }

        return $this->sendResponse($filteredOrderData, count($filteredOrderData) . ' Order(s) Found!');

    }

    public function getDeliveredOrders(Request $request) {

        $serviceHelper = new DriverApiServiceHelper();

        $user = auth()->user();
        $userId = $user->id;
        $validStatus = $serviceHelper->isValidApiUser($userId);
        if ($validStatus['success'] === false) {
            return $this->sendError($validStatus['message'], ['error' => $validStatus['message']], $validStatus['httpStatus']);
        }

        $pageStart = (
            $request->has('page')
            && (trim($request->input('page')) != '')
        ) ? (int)trim($request->input('page')) : 0;

        $pageLength = (
            $request->has('limit')
            && (trim($request->input('limit')) != '')
        ) ? (int)trim($request->input('limit')) : 10;

        $filterStatus = [
            SaleOrder::SALE_ORDER_STATUS_DELIVERED,
            SaleOrder::SALE_ORDER_STATUS_CANCELED,
        ];
        $filteredOrders = $serviceHelper->getDriverOrders('', '', $filterStatus, '', '');
        if (!$filteredOrders) {
            return $this->sendResponse([], 'No Orders Found!');
        }

        $emirates = config('fms.emirates');
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getDriversAllowedStatuses();
        $statusList = config('fms.order_statuses');
        $filteredOrderData = [];
        $totalRec = 0;
        $collectRecStart = $pageStart;
        $collectRecEnd = $collectRecStart + $pageLength;
        $currentRec = -1;
        foreach ($filteredOrders as $record) {
            $deliveryDriverData = null;
            if ($record->order_status == SaleOrder::SALE_ORDER_STATUS_DELIVERED) {
                $deliveryDriverData = $record->deliveredData;
            } elseif ($record->order_status == SaleOrder::SALE_ORDER_STATUS_CANCELED) {
                $deliveryDriverData = $record->canceledData;
            }
            $canProceed = false;
            $driverDetail = null;
            if ($deliveryDriverData) {
                if (($userId > 0) && !is_null($deliveryDriverData->done_by) && ((int)$deliveryDriverData->done_by == $userId)) {
                    $canProceed = true;
                    $driverDetail = $deliveryDriverData;
                }
            }
            if ($canProceed) {
                $totalRec++;
                $currentRec++;
                if (($currentRec < $collectRecStart) || ($currentRec >= $collectRecEnd)) {
                    continue;
                }
                $tempRecord = [];
                $tempRecord['recordId'] = $record->id;
                $tempRecord['orderId'] = $record->order_id;
                $tempRecord['incrementId'] = $record->increment_id;
                $apiChannelId = $record->channel;
                $tempRecord['channel'] = $availableApiChannels[$apiChannelId]['name'];
                $emirateId = $record->region_code;
                $tempRecord['region'] = $emirates[$emirateId];
                $tempRecord['city'] = $record->city;
                $tempRecord['zoneId'] = $record->zone_id;
                $shipAddress = $record->shippingAddress;
                $tempRecord['customerName'] = $shipAddress->first_name . ' ' . $shipAddress->last_name;
                $tempRecord['deliveryDate'] = $record->delivery_date;
                $tempRecord['deliveryTimeSlot'] = $record->delivery_time_slot;
                $tempRecord['deliveryPickerTime'] = '';
                $tempRecord['deliveryDriverTime'] = '';
                $orderStatusId = $record->order_status;
                $tempRecord['orderStatus'] = $statusList[$orderStatusId];
                $deliveryPickerData = $record->pickedData;
                if ($deliveryPickerData) {
                    $tempRecord['deliveryPickerTime'] = $deliveryPickerData->done_at;
                }
                if (!is_null($driverDetail)) {
                    $tempRecord['deliveryDriverTime'] = $driverDetail->done_at;
                }
                $filteredOrderData[] = $tempRecord;
            }
        }

        return $this->sendResponse($filteredOrderData, count($filteredOrderData) . ' Order(s) Found!');

    }

    public  function getOrderDetails(Request $request) {

        $serviceHelper = new DriverApiServiceHelper();

        $user = auth()->user();
        $userId = $user->id;
        $validStatus = $serviceHelper->isValidApiUser($userId);
        if ($validStatus['success'] === false) {
            return $this->sendError($validStatus['message'], ['error' => $validStatus['message']], $validStatus['httpStatus']);
        }

        $givenOrderId = (
            $request->has('orderId')
            && (trim($request->input('orderId')) != '')
            && is_numeric($request->input('orderId'))
            && ((int)trim($request->input('orderId')) > 0)
        ) ? (int)trim($request->input('orderId')) : null;
        if (is_null($givenOrderId)) {
            $errMessage = 'Sale Order Not found!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $allowedReqStatuses = [
            'pickup' => SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH,
            'delivery' => SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY,
            'delivered' => SaleOrder::SALE_ORDER_STATUS_DELIVERED,
            'canceled' => SaleOrder::SALE_ORDER_STATUS_CANCELED,
        ];
        $givenAction = (
            $request->has('orderState')
            && (trim($request->input('orderState')) != '')
        ) ? trim($request->input('orderState')) : null;
        if (!is_null($givenAction) && !in_array($givenAction, array_keys($allowedReqStatuses))) {
            $errMessage = 'Sale Order not accessible!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $saleOrderObj = SaleOrder::find($givenOrderId);
        if (!$saleOrderObj) {
            $errMessage = 'Sale Order Not found!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        if (
            (
                is_null($givenAction)
                && !in_array($saleOrderObj->order_status, array_values($allowedReqStatuses))
            )
            || (
                !is_null($givenAction)
                && ($saleOrderObj->order_status !== $allowedReqStatuses[$givenAction])
            )
        ) {
            $errMessage = 'The Sale Order not accessible!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $canProceed = false;
        $driverDetail = null;
        if (
            ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH)
            || ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY)
        ) {
            $deliveryDriverData = $saleOrderObj->currentDriver;
            if ($deliveryDriverData && (count($deliveryDriverData) > 0)) {
                foreach ($deliveryDriverData as $dDeliver) {
                    if (($userId > 0) && !is_null($dDeliver->done_by) && ((int)$dDeliver->done_by == $userId)) {
                        $canProceed = true;
                        $driverDetail = $dDeliver;
                    }
                }
            }
            if (!$canProceed) {
                $errMessage = 'The Sale Order is not assigned to the user!';
                return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
            }
        } elseif ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_DELIVERED) {
            $deliveryDriverData = $saleOrderObj->deliveredData;
            if ($deliveryDriverData) {
                if (($userId > 0) && !is_null($deliveryDriverData->done_by) && ((int)$deliveryDriverData->done_by == $userId)) {
                    $canProceed = true;
                    $driverDetail = $deliveryDriverData;
                }
            }
            if (!$canProceed) {
                $errMessage = 'The Sale Order is not delivered by the user!';
                return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
            }
        } elseif ($saleOrderObj->order_status === SaleOrder::SALE_ORDER_STATUS_CANCELED) {
            $deliveryDriverData = $saleOrderObj->canceledData;
            if ($deliveryDriverData) {
                if (($userId > 0) && !is_null($deliveryDriverData->done_by) && ((int)$deliveryDriverData->done_by == $userId)) {
                    $canProceed = true;
                    $driverDetail = $deliveryDriverData;
                }
            }
            if (!$canProceed) {
                $errMessage = 'The Sale Order is not canceled by the user!';
                return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
            }
        }

        $saleOrderObj->saleCustomer;
        $saleOrderObj->orderItems;
        $saleOrderObj->billingAddress;
        $saleOrderObj->shippingAddress;
        $saleOrderObj->paymentData;
        $saleOrderObj->statusHistory;
        $saleOrderData = $saleOrderObj->toArray();

        $saleOrderData['deliveryPickerTime'] = '';
        $saleOrderData['deliveryDriverTime'] = '';
        $deliveryPickerData = $saleOrderObj->pickedData;
        if ($deliveryPickerData) {
            $saleOrderData['deliveryPickerTime'] = $deliveryPickerData->done_at;
        }
        if (!is_null($driverDetail)) {
            $saleOrderData['deliveryDriverTime'] = $driverDetail->done_at;
        }

        return $this->sendResponse($saleOrderData, 'The Sale Order fetched successfully!');

    }

    public function setOrderForDelivery(Request $request) {

        $serviceHelper = new DriverApiServiceHelper();

        $user = auth()->user();
        $userId = $user->id;
        $validStatus = $serviceHelper->isValidApiUser($userId);
        if ($validStatus['success'] === false) {
            return $this->sendError($validStatus['message'], ['error' => $validStatus['message']], $validStatus['httpStatus']);
        }

        $givenOrderId = (
            $request->has('orderId')
            && (trim($request->input('orderId')) != '')
            && is_numeric($request->input('orderId'))
            && ((int)trim($request->input('orderId')) > 0)
        ) ? (int)trim($request->input('orderId')) : null;

        if (is_null($givenOrderId)) {
            $errMessage = 'Sale Order Not found!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $saleOrderObj = SaleOrder::find($givenOrderId);
        if (!$saleOrderObj) {
            $errMessage = 'Sale Order Not found!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $allowedStatuses = [
            SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH
        ];
        if (!in_array($saleOrderObj->order_status, $allowedStatuses)) {
            $errMessage = 'The Sale Order Status cannot be changed!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $canProceed = false;
        if ($saleOrderObj->currentDriver && (count($saleOrderObj->currentDriver) > 0)) {
            $currentHistory = $saleOrderObj->currentDriver[0];
            if ($currentHistory->done_by === $userId) {
                $canProceed = true;
            }
        }
        if (!$canProceed) {
            $errMessage = 'The Sale Order is not assigned to the user!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $newStatus = SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY;
        $returnResult = $serviceHelper->changeSaleOrderStatus($saleOrderObj, $newStatus, $userId);
        if (!$returnResult['status']) {
            return $this->sendError($returnResult['message'], ['error' => $returnResult['message']], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        return $this->sendResponse([], 'The Sale Order is now ready for delivery!');

    }

    public function setOrderAsDelivered(Request $request) {

        $serviceHelper = new DriverApiServiceHelper();

        $user = auth()->user();
        $userId = $user->id;
        $validStatus = $serviceHelper->isValidApiUser($userId);
        if ($validStatus['success'] === false) {
            return $this->sendError($validStatus['message'], ['error' => $validStatus['message']], $validStatus['httpStatus']);
        }

        $givenOrderId = (
            $request->has('orderId')
            && (trim($request->input('orderId')) != '')
            && is_numeric($request->input('orderId'))
            && ((int)trim($request->input('orderId')) > 0)
        ) ? (int)trim($request->input('orderId')) : null;

        if (is_null($givenOrderId)) {
            $errMessage = 'Sale Order Not found!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $saleOrderObj = SaleOrder::find($givenOrderId);
        if (!$saleOrderObj) {
            $errMessage = 'Sale Order Not found!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $allowedStatuses = [
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY
        ];
        if (!in_array($saleOrderObj->order_status, $allowedStatuses)) {
            $errMessage = 'The Sale Order Status cannot be changed!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $canProceed = false;
        if ($saleOrderObj->currentDriver && (count($saleOrderObj->currentDriver) > 0)) {
            $currentHistory = $saleOrderObj->currentDriver[0];
            if ($currentHistory->done_by === $userId) {
                $canProceed = true;
            }
        }
        if (!$canProceed) {
            $errMessage = 'The Sale Order is not assigned to the user!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $newStatus = SaleOrder::SALE_ORDER_STATUS_DELIVERED;
        $returnResult = $serviceHelper->changeSaleOrderStatus($saleOrderObj, $newStatus, $userId);
        if (!$returnResult['status']) {
            return $this->sendError($returnResult['message'], ['error' => $returnResult['message']], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        return $this->sendResponse([], 'The Sale Order is delivered successfully!');

    }

    public function setOrderAsCanceled(Request $request) {

        $serviceHelper = new DriverApiServiceHelper();

        $user = auth()->user();
        $userId = $user->id;
        $validStatus = $serviceHelper->isValidApiUser($userId);
        if ($validStatus['success'] === false) {
            return $this->sendError($validStatus['message'], ['error' => $validStatus['message']], $validStatus['httpStatus']);
        }

        $givenOrderId = (
            $request->has('orderId')
            && (trim($request->input('orderId')) != '')
            && is_numeric($request->input('orderId'))
            && ((int)trim($request->input('orderId')) > 0)
        ) ? (int)trim($request->input('orderId')) : null;

        if (is_null($givenOrderId)) {
            $errMessage = 'Sale Order Not found!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $saleOrderObj = SaleOrder::find($givenOrderId);
        if (!$saleOrderObj) {
            $errMessage = 'Sale Order Not found!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $allowedStatuses = [
            SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY
        ];
        if (!in_array($saleOrderObj->order_status, $allowedStatuses)) {
            $errMessage = 'The Sale Order Status cannot be changed!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        $canProceed = false;
        if ($saleOrderObj->currentDriver && (count($saleOrderObj->currentDriver) > 0)) {
            $currentHistory = $saleOrderObj->currentDriver[0];
            if ($currentHistory->done_by === $userId) {
                $canProceed = true;
            }
        }
        if (!$canProceed) {
            $errMessage = 'The Sale Order is not assigned to the user!';
            return $this->sendError($errMessage, ['error' => $errMessage], ApiServiceHelper::HTTP_STATUS_CODE_UNAUTHORIZED);
        }

        $newStatus = SaleOrder::SALE_ORDER_STATUS_CANCELED;
        $returnResult = $serviceHelper->changeSaleOrderStatus($saleOrderObj, $newStatus, $userId);
        if (!$returnResult['status']) {
            return $this->sendError($returnResult['message'], ['error' => $returnResult['message']], ApiServiceHelper::HTTP_STATUS_CODE_NOT_FOUND);
        }

        return $this->sendResponse([], 'The Sale Order is canceled successfully!');

    }

}
