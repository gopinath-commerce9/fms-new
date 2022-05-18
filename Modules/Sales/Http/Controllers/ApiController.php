<?php

namespace Modules\Sales\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\API\Entities\MobileAppUser;
use Modules\API\Http\Controllers\BaseController;
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

}
