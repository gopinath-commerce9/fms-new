<?php

namespace Modules\Cashier\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\API\Entities\ApiServiceHelper;
use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SaleOrderAddress;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Entities\SaleOrderItem;
use Modules\Sales\Entities\SalesRegion;
use Modules\Cashier\Entities\CashierServiceHelper;
use Modules\Sales\Entities\SaleOrder;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\UserRole\Entities\UserRole;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;

class CashierController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return redirect()->route('cashier.dashboard');
    }

    public function dashboard(Request $request)
    {
        /*return redirect()->route('sales.pos');*/

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Dashboard';

        $serviceHelper = new CashierServiceHelper();

        $todayDate = date('Y-m-d');

        return view('cashier::index', compact(
            'pageTitle',
            'pageSubTitle'
        ));
    }

    public function searchOrderByIncrementId(Request $request) {

        $incrementId = (
            $request->has('order_number')
            && (trim($request->input('order_number')) != '')
        ) ? trim($request->input('order_number')) : '';

        if ($incrementId == '') {
            return response()->json([
                'success' => false,
                'message' => 'Requested Order Number value is invalid!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $serviceHelper = new CashierServiceHelper();
        $availableStatuses = $serviceHelper->getCashiersAllowedStatuses();
        $currentChannel = $serviceHelper->getApiChannel();
        $currentEnv = $serviceHelper->getApiEnvironment();

        $targetOrderQ = SaleOrder::select('*');
        $targetOrderQ->where('increment_id', $incrementId);
        if (!is_null($currentEnv)) {
            $targetOrderQ->where('env', $currentEnv);
        }
        if (!is_null($currentChannel)) {
            $targetOrderQ->where('channel', $currentChannel);
        }
        if (count(array_keys($availableStatuses)) > 0) {
            $targetOrderQ->whereIn('order_status', array_keys($availableStatuses));
        }

        $targetOrder = $targetOrderQ->get();
        if ($targetOrder) {

            $saleOrderObj = ($targetOrder instanceof SaleOrder) ? $targetOrder : $targetOrder->first();
            if (is_null($saleOrderObj)) {
                return response()->json([
                    'success' => false,
                    'message' => "Sale Order #" . $incrementId . " not found!",
                ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
            }

            $processUserId = 0;
            if (session()->has('authUserData')) {
                $sessionUser = session('authUserData');
                $processUserId = $sessionUser['id'];
            }

            $orderStatuses = config('fms.order_statuses');

            $customerGroups = [];
            $customerGroupData = $serviceHelper->getCustomerGroups();
            if (is_array($customerGroupData) && (count($customerGroupData) > 0) && array_key_exists('items', $customerGroupData)) {
                foreach($customerGroupData['items'] as $group) {
                    $customerGroups[$group['id']] = $group['code'];
                }
            }

            $saleOrderObj->saleCustomer;
            $saleOrderObj->orderItems;
            $saleOrderObj->billingAddress;
            $saleOrderObj->shippingAddress;
            $saleOrderObj->paymentData;
            $saleOrderObj->statusHistory;
            $saleOrderObj->processHistory;
            $saleOrderData = $saleOrderObj->toArray();

            $storeCredits = 0;
            $storeCreditCheckArray = [
                [
                    'amstorecredit_invoiced_amount',
                    'amstorecredit_amount',
                ]
            ];
            $fetchedOrderResult = $serviceHelper->getServerOrderDetails($saleOrderObj);
            if ($fetchedOrderResult['status'] === true) {
                $fetchedOrderData = $fetchedOrderResult['orderData'];
                $orderExtData = array_key_exists('extension_attributes', $fetchedOrderData) ? $fetchedOrderData['extension_attributes'] : [];
                foreach ($storeCreditCheckArray as $mainLoopKey => $mainLoopEl) {
                    $tempStoreCredit = null;
                    foreach ($mainLoopEl as $secondaryLoopKey => $secondaryLoopEl) {
                        if (is_null($tempStoreCredit) && array_key_exists($secondaryLoopEl, $orderExtData) && ((float)trim($orderExtData[$secondaryLoopEl]) > 0)) {
                            $tempStoreCredit = (float)trim($orderExtData[$secondaryLoopEl]);
                        }
                    }
                    if (!is_null($tempStoreCredit)) {
                        $storeCredits += $tempStoreCredit;
                    }
                }
            }
            $saleOrderData['storeCredits'] = $storeCredits;

            $saleRegionDetails = SalesRegion::firstWhere('region_id', $saleOrderData['region_id']);

            $orderDetailsHtml = view('cashier::order-details-view', compact(
                'saleOrderData',
                'saleOrderObj',
                'serviceHelper',
                'customerGroups',
                'orderStatuses',
                'saleRegionDetails',
            ))->render();

            $orderItemsHtml = view('cashier::order-items-view', compact(
                'saleOrderData',
                'saleOrderObj',
                'serviceHelper',
                'customerGroups',
                'orderStatuses',
                'saleRegionDetails',
            ))->render();

            return response()->json([
                'success' => true,
                'data'    => [
                    'recordId' => $saleOrderData['id'],
                    'orderId' => $saleOrderData['order_id'],
                    'orderNumber' => $saleOrderData['increment_id'],
                    'orderDetailsHtml' => $orderDetailsHtml,
                    'orderItemsHtml' => $orderItemsHtml,
                ],
                'message' => 'The Sale Order fetched successfully!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);

        } else {
            return response()->json([
                'success' => false,
                'message' => "Sale Order #" . $incrementId . " not found!",
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

    }

    public function searchOrderItemByBarcode(Request $request) {

        $orderId = (
            $request->has('item_order_id')
            && (trim($request->input('item_order_id')) != '')
        ) ? trim($request->input('item_order_id')) : '';

        $orderItemBarcode = (
            $request->has('order_item_barcode')
            && (trim($request->input('order_item_barcode')) != '')
        ) ? trim($request->input('order_item_barcode')) : '';

        $orderItemBarcodeRescan = (
            $request->has('order_item_rescan')
            && (trim($request->input('order_item_rescan')) != '')
            && (
                ((int)trim($request->input('order_item_rescan')) === 0)
                || ((int)trim($request->input('order_item_rescan')) === 1)
            )
        ) ? (int)trim($request->input('order_item_rescan')) : 0;

        if ($orderId == '') {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 0
                ],
                'message' => 'Requested Order Number value is invalid!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        if ($orderItemBarcode == '') {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 0
                ],
                'message' => 'Requested Order Item Barcode value is invalid!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 0
                ],
                'message' => 'The Sale Order does not exist!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $serviceHelper = new CashierServiceHelper();

        $saleOrderObj->orderItems;
        $saleOrderDataTemp = $saleOrderObj->toArray();

        $orderItemSku = "";
        $orderItemId = "";
        $orderItemBarcodeList = [];
        $orderItemBarcodeScanCount = 0;
        $orderItemQty = 0;
        $orderItemAmount = 0;
        $orderItemQtyScanned = 0;
        $orderItemAmountScanned = 0;
        $orderItemQtyScannedEarlier = 0;
        $qtyToleranceThreshold = 0;
        $orderItemAmountScannedEarlier = 0;
        $freshItem = false;
        $productExists = false;
        $productInOrder = false;
        $productScannedEarlier = false;

        $barcodeExploder = $serviceHelper->explodeSaleOrderItemBarcode($orderItemBarcode);
        if (!is_null($barcodeExploder)) {
            $freshItem = true;
            $orderItemSku = $barcodeExploder['itemSku'];
            $orderItemQtyScanned = (float) $barcodeExploder['itemQty'];
            $orderItemAmountScanned = (float) $barcodeExploder['itemPrice'];
            $skuSearcher = $serviceHelper->fetchProductDetailsBySku($orderItemSku, $saleOrderDataTemp['env'], $saleOrderDataTemp['channel']);
            if (is_array($skuSearcher) && (count($skuSearcher) > 0)) {
                $productExists = true;
                $productCustAttrBase = (array_key_exists('custom_attributes', $skuSearcher) && (count($skuSearcher['custom_attributes']) > 0)) ? $skuSearcher['custom_attributes'] : [];
                $productCustAttr = [];
                foreach ($productCustAttrBase as $attrObj) {
                    $productCustAttr[$attrObj['attribute_code']] = $attrObj['value'];
                }
                if (array_key_exists('weight_tolerance', $productCustAttr)) {
                    $qtyToleranceThreshold = (float)$productCustAttr['weight_tolerance'];
                }
            }
        }

        if (trim($orderItemSku) == "") {
            $barcodeSearcher = $serviceHelper->fetchProductDetailsByBarcode($orderItemBarcode, $saleOrderDataTemp['env'], $saleOrderDataTemp['channel']);
            if (is_array($barcodeSearcher) && (count($barcodeSearcher) > 0)) {
                $productExists = true;
                $orderItemSku = $barcodeSearcher['sku'];
                $orderItemQtyScanned = (float) $barcodeSearcher['extension_attributes']['min_qty'];
                $orderItemAmountScanned = (float) $barcodeSearcher['price'];
            }
        }

        if ($productExists === false) {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 0
                ],
                'message' => 'Could not fetch the data for Product!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $orderItems = $saleOrderDataTemp['order_items'];
        if (isset($orderItems) && is_array($orderItems) && (count($orderItems) > 0)) {
            foreach ($orderItems as $item) {
                if ($item['item_sku'] == $orderItemSku) {
                    $productInOrder = true;
                    $orderItemId = $item['id'];
                    $orderItemQty = (float)$item['qty_ordered'];
                    $orderItemAmount = (float)$item['row_grand_total'];
                    $orderItemQtyScannedEarlier = (!is_null($item['qty_delivered'])) ? (float)$item['qty_delivered'] : 0;
                    $orderItemAmountScannedEarlier = (!is_null($item['row_total_delivered'])) ? (float)$item['row_total_delivered'] : 0;
                    if (!is_null($item['scan_barcode'])) {
                        $productScannedEarlier = true;
                        $orderItemBarcodeList = explode(',', trim($item['scan_barcode']));
                        $orderItemBarcodeScanCount = (!is_null($item['scan_count'])) ? (int)$item['scan_count'] : 0;
                    }
                }
            }
        }

        if ($productInOrder === false) {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 0
                ],
                'message' => 'The Scanned Product "' . $orderItemBarcode . '" is not from the Sale Order "#' . $saleOrderDataTemp['increment_id'] . '" !',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        if (($orderItemBarcodeRescan == 0) && ($productScannedEarlier === true)) {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 1
                ],
                'message' => 'The Scanned Product "' . $orderItemBarcode . '" is already scanned ' . $orderItemBarcodeScanCount . ' time(s) for the Sale Order "#' . $saleOrderDataTemp['increment_id'] . '" !',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $orderItemBarcodeList[] = trim($orderItemBarcode);
        $barcodeList = implode(',', $orderItemBarcodeList);
        $orderItemBarcodeScanCount++;
        $orderItemQtyScannedEarlier += $orderItemQtyScanned;
        $orderItemAmountScannedEarlier += $orderItemAmountScanned;

        $maxQtyAllowed = $orderItemQty + $qtyToleranceThreshold;
        $itemQtyDifference = $orderItemQtyScannedEarlier - $maxQtyAllowed;
        if ($itemQtyDifference > 0) {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 0
                ],
                'message' => 'The Scanned Product "' . $orderItemBarcode . '" exceeded the maximum Quantity level of the item ordered in the Sale Order "#' . $saleOrderDataTemp['increment_id'] . '" !',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        /*$finalScannedQty = (($orderItemQtyScannedEarlier - $orderItemQty) > 0) ? $orderItemQty : $orderItemQtyScannedEarlier;
        $finalScannedAmount = (($orderItemQtyScannedEarlier - $orderItemQty) > 0) ? $orderItemAmount : $orderItemAmountScannedEarlier;*/

        $finalScannedQty = $orderItemQtyScannedEarlier;
        $finalScannedAmount = $orderItemAmountScannedEarlier;

        $saleOrderUpdatedItem = SaleOrderItem::find($orderItemId);
        if (!is_null($saleOrderUpdatedItem)) {
            $saleOrderUpdatedItem->scan_barcode = $barcodeList;
            $saleOrderUpdatedItem->scan_count = $orderItemBarcodeScanCount;
            $saleOrderUpdatedItem->qty_delivered = $finalScannedQty;
            $saleOrderUpdatedItem->row_total_delivered = $finalScannedAmount;
            $saleOrderUpdatedItem->store_availability = 1;
            $saleOrderUpdatedItem->availability_checked_at = date('Y-m-d H:i:s');
            $saleOrderUpdatedItem->save();
        }

        $saleOrderObj->refresh();

        $saleOrderObj->saleCustomer;
        $saleOrderObj->orderItems;
        $saleOrderObj->billingAddress;
        $saleOrderObj->shippingAddress;
        $saleOrderObj->paymentData;
        $saleOrderObj->statusHistory;
        $saleOrderObj->processHistory;
        $saleOrderData = $saleOrderObj->toArray();

        $storeCredits = 0;
        $storeCreditCheckArray = [
            [
                'amstorecredit_invoiced_amount',
                'amstorecredit_amount',
            ]
        ];
        $fetchedOrderResult = $serviceHelper->getServerOrderDetails($saleOrderObj);
        if ($fetchedOrderResult['status'] === true) {
            $fetchedOrderData = $fetchedOrderResult['orderData'];
            $orderExtData = array_key_exists('extension_attributes', $fetchedOrderData) ? $fetchedOrderData['extension_attributes'] : [];
            foreach ($storeCreditCheckArray as $mainLoopKey => $mainLoopEl) {
                $tempStoreCredit = null;
                foreach ($mainLoopEl as $secondaryLoopKey => $secondaryLoopEl) {
                    if (is_null($tempStoreCredit) && array_key_exists($secondaryLoopEl, $orderExtData) && ((float)trim($orderExtData[$secondaryLoopEl]) > 0)) {
                        $tempStoreCredit = (float)trim($orderExtData[$secondaryLoopEl]);
                    }
                }
                if (!is_null($tempStoreCredit)) {
                    $storeCredits += $tempStoreCredit;
                }
            }
        }
        $saleOrderData['storeCredits'] = $storeCredits;

        $orderStatuses = config('fms.order_statuses');

        $customerGroups = [];
        $customerGroupData = $serviceHelper->getCustomerGroups();
        if (is_array($customerGroupData) && (count($customerGroupData) > 0) && array_key_exists('items', $customerGroupData)) {
            foreach($customerGroupData['items'] as $group) {
                $customerGroups[$group['id']] = $group['code'];
            }
        }
        $saleRegionDetails = SalesRegion::firstWhere('region_id', $saleOrderData['region_id']);

        $orderItemsHtml = view('cashier::order-items-view', compact(
            'saleOrderData',
            'saleOrderObj',
            'serviceHelper',
            'customerGroups',
            'orderStatuses',
            'saleRegionDetails',
        ))->render();

        return response()->json([
            'success' => true,
            'data' => [
                'recordId' => $saleOrderData['id'],
                'orderId' => $saleOrderData['order_id'],
                'orderNumber' => $saleOrderData['increment_id'],
                'rescanBarcode' => 0,
                'orderItemsHtml' => $orderItemsHtml,
            ],
            'message' => 'The Product scanned successfully!',
        ], ApiServiceHelper::HTTP_STATUS_CODE_OK);

    }

    public function clearSaleItemBarcodes(Request $request) {

        $orderId = (
            $request->has('orderId')
            && (trim($request->input('orderId')) != '')
        ) ? trim($request->input('orderId')) : '';

        $orderItemId = (
            $request->has('itemId')
            && (trim($request->input('itemId')) != '')
        ) ? trim($request->input('itemId')) : '';

        $orderItemSku = (
            $request->has('itemSku')
            && (trim($request->input('itemSku')) != '')
        ) ? trim($request->input('itemSku')) : '';

        if ($orderId == '') {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 0
                ],
                'message' => 'Requested Order Number value is invalid!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        if ($orderItemId == '') {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 0
                ],
                'message' => 'Requested Order Item value is invalid!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        if ($orderItemSku == '') {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 0
                ],
                'message' => 'Requested Order Item SKU value is invalid!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $serviceHelper = new CashierServiceHelper();
        $availableStatuses = $serviceHelper->getCashiersAllowedStatuses();
        $currentChannel = $serviceHelper->getApiChannel();
        $currentEnv = $serviceHelper->getApiEnvironment();

        $targetOrderItemObj = SaleOrderItem::find($orderItemId);
        if(!$targetOrderItemObj || ($targetOrderItemObj->item_sku != $orderItemSku) || ($targetOrderItemObj->order_id != $orderId)) {
            return response()->json([
                'success' => false,
                'data' => [
                    'rescanBarcode' => 0
                ],
                'message' => 'The Sale Order Item does not exist!',
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $targetOrderQ = SaleOrder::select('*');
        $targetOrderQ->where('id', $orderId);
        if (!is_null($currentEnv)) {
            $targetOrderQ->where('env', $currentEnv);
        }
        if (!is_null($currentChannel)) {
            $targetOrderQ->where('channel', $currentChannel);
        }
        if (count(array_keys($availableStatuses)) > 0) {
            $targetOrderQ->whereIn('order_status', array_keys($availableStatuses));
        }

        $targetOrder = $targetOrderQ->get();
        if (!$targetOrder) {
            return response()->json([
                'success' => false,
                'message' => "Sale Order not found!",
            ], ApiServiceHelper::HTTP_STATUS_CODE_BAD_REQUEST);
        }

        $saleOrderObj = ($targetOrder instanceof SaleOrder) ? $targetOrder : $targetOrder->first();
        if (is_null($saleOrderObj)) {
            return response()->json([
                'success' => false,
                'message' => "Sale Order not found!",
            ], ApiServiceHelper::HTTP_STATUS_CODE_OK);
        }

        $targetOrderItemObj->scan_barcode = null;
        $targetOrderItemObj->scan_count = null;
        $targetOrderItemObj->qty_delivered = null;
        $targetOrderItemObj->row_total_delivered = null;
        $targetOrderItemObj->store_availability = null;
        $targetOrderItemObj->availability_checked_at = null;
        $targetOrderItemObj->save();

        $targetOrderItemObj->refresh();

        $saleOrderObj->saleCustomer;
        $saleOrderObj->orderItems;
        $saleOrderObj->billingAddress;
        $saleOrderObj->shippingAddress;
        $saleOrderObj->paymentData;
        $saleOrderObj->statusHistory;
        $saleOrderObj->processHistory;
        $saleOrderData = $saleOrderObj->toArray();

        $storeCredits = 0;
        $storeCreditCheckArray = [
            [
                'amstorecredit_invoiced_amount',
                'amstorecredit_amount',
            ]
        ];
        $fetchedOrderResult = $serviceHelper->getServerOrderDetails($saleOrderObj);
        if ($fetchedOrderResult['status'] === true) {
            $fetchedOrderData = $fetchedOrderResult['orderData'];
            $orderExtData = array_key_exists('extension_attributes', $fetchedOrderData) ? $fetchedOrderData['extension_attributes'] : [];
            foreach ($storeCreditCheckArray as $mainLoopKey => $mainLoopEl) {
                $tempStoreCredit = null;
                foreach ($mainLoopEl as $secondaryLoopKey => $secondaryLoopEl) {
                    if (is_null($tempStoreCredit) && array_key_exists($secondaryLoopEl, $orderExtData) && ((float)trim($orderExtData[$secondaryLoopEl]) > 0)) {
                        $tempStoreCredit = (float)trim($orderExtData[$secondaryLoopEl]);
                    }
                }
                if (!is_null($tempStoreCredit)) {
                    $storeCredits += $tempStoreCredit;
                }
            }
        }
        $saleOrderData['storeCredits'] = $storeCredits;

        $orderStatuses = config('fms.order_statuses');

        $customerGroups = [];
        $customerGroupData = $serviceHelper->getCustomerGroups();
        if (is_array($customerGroupData) && (count($customerGroupData) > 0) && array_key_exists('items', $customerGroupData)) {
            foreach($customerGroupData['items'] as $group) {
                $customerGroups[$group['id']] = $group['code'];
            }
        }
        $saleRegionDetails = SalesRegion::firstWhere('region_id', $saleOrderData['region_id']);

        $orderItemsHtml = view('cashier::order-items-view', compact(
            'saleOrderData',
            'saleOrderObj',
            'serviceHelper',
            'customerGroups',
            'orderStatuses',
            'saleRegionDetails',
        ))->render();

        return response()->json([
            'success' => true,
            'data' => [
                'recordId' => $saleOrderData['id'],
                'orderId' => $saleOrderData['order_id'],
                'orderNumber' => $saleOrderData['increment_id'],
                'rescanBarcode' => 0,
                'orderItemsHtml' => $orderItemsHtml,
            ],
            'message' => 'The Product Barcode cleared successfully!',
        ], ApiServiceHelper::HTTP_STATUS_CODE_OK);

    }

    public function prepareOrderStatusChange(Request $request, $orderId) {

        if (is_null($orderId) || !is_numeric($orderId) || ((int)$orderId <= 0)) {
            return back()
                ->with('error', 'The Sale Order Id input is invalid!');
        }

        $saleOrderObj = SaleOrder::find($orderId);
        if(!$saleOrderObj) {
            return back()
                ->with('error', 'The Sale Order does not exist!');
        }

        $allowedStatuses = [
            SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED
        ];
        if (!in_array($saleOrderObj->order_status, $allowedStatuses)) {
            return back()
                ->with('error', 'The Sale Order Status cannot be changed!');
        }

        $processUserId = 0;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $processUserId = $sessionUser['id'];
        }

        $orderItemCount = count($saleOrderObj->orderItems);
        $allowedAvailabilityValues = [
            SaleOrderItem::STORE_AVAILABLE_YES,
            SaleOrderItem::STORE_AVAILABLE_NO
        ];

        $validator = Validator::make($request->all() , [
            'box_qty' => ['required', 'numeric', 'integer', 'min:1'],
            'store_availability' => ['required', 'array', 'max:' . $orderItemCount],
            'store_availability.*' => [Rule::in($allowedAvailabilityValues)],
        ], [
            'box_qty.required' => 'The Box Count should not be empty.',
            'box_qty.min' => 'The Box Count should be atleast :min.',
            'store_availability.required' => 'The Order Items are not checked for Store Availability.',
            'store_availability.*.in' => 'Some of the Order Items are not checked for Store Availability.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('box_qty'));
        }

        $postData = $validator->validated();
        $boxCount = $postData['box_qty'];
        $storeAvailabilityArray = $postData['store_availability'];

        $serviceHelper = new CashierServiceHelper();

        $returnResult = $serviceHelper->setOrderAsDispatchReady($saleOrderObj, $boxCount, $storeAvailabilityArray, $processUserId);
        if ($returnResult['status']) {
            return back()->with('success', 'The Sale Order status is updated successfully!');
        } else {
            return back()->with('error', $returnResult['message']);
        }

    }

}
