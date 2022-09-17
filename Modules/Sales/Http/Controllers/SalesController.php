<?php

namespace Modules\Sales\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Entities\ProductCategory;
use Modules\Sales\Entities\SalesRegion;
use Modules\Sales\Entities\SalesServiceHelper;
use Modules\Sales\Entities\SaleOrder;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\UserRole\Entities\UserRole;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;

class SalesController extends Controller
{

    public function index()
    {
        return redirect()->route('sales.ordersList');
    }

    public function ordersList(Request $request) {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Dashboard';

        $serviceHelper = new SalesServiceHelper();
        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();

        $selectedEmirate = (
            $request->has('emirate')
            && (trim($request->input('emirate')) != '')
            && array_key_exists(trim($request->input('emirate')), $emirates)
        ) ? trim($request->input('emirate')) : 'DXB';

        $todayDate = date('Y-m-d');

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getAvailableStatuses();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();

        return view('sales::list', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'selectedEmirate',
            'todayDate',
            'availableApiChannels',
            'availableStatuses',
            'deliveryTimeSlots',
            'serviceHelper'
        ));

    }

    public function searchOrderByIncrementId(Request $request) {

        $incrementId = (
            $request->has('order_number')
            && (trim($request->input('order_number')) != '')
        ) ? trim($request->input('order_number')) : '';

        if ($incrementId == '') {
            return back()
                ->with('error', "Requested Order Number value is invalid!");
        }

        $serviceHelper = new SalesServiceHelper();
        $availableStatuses = $serviceHelper->getAvailableStatuses();
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
            $saleOrder = ($targetOrder instanceof SaleOrder) ? $targetOrder : $targetOrder->first();
            if (is_null($saleOrder)) {
                return back()
                    ->with('error', "Sale Order #" . $incrementId . " not found!");
            }
            $currentRole = null;
            if (session()->has('authUserData')) {
                $sessionUser = session('authUserData');
                $currentRole = $sessionUser['roleCode'];
            }
            if (!is_null($currentRole)) {
                return redirect('/' . $currentRole . '/order-view/' . $saleOrder->id);
            } else {
                return back()
                    ->with('error', "Sale Order #" . $incrementId . " not found!");
            }
        } else {
            return back()
                ->with('error', "Sale Order #" . $incrementId . " not found!");
        }

    }

    public function searchOrderByFilters(Request $request) {

        $serviceHelper = new SalesServiceHelper();

        $dtDraw = (
            $request->has('draw')
            && (trim($request->input('draw')) != '')
        ) ? (int)trim($request->input('draw')) : 1;

        $dtStart = (
            $request->has('start')
            && (trim($request->input('start')) != '')
        ) ? (int)trim($request->input('start')) : 0;

        $dtPageLength = (
            $request->has('length')
            && (trim($request->input('length')) != '')
        ) ? (int)trim($request->input('length')) : 10;

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();
        $region = (
            $request->has('emirates_region')
            && (trim($request->input('emirates_region')) != '')
            && array_key_exists(trim($request->input('emirates_region')), $emirates)
        ) ? trim($request->input('emirates_region')) : '';

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $apiChannel = (
            $request->has('channel_filter')
            && (trim($request->input('channel_filter')) != '')
            && array_key_exists(trim($request->input('channel_filter')), $availableApiChannels)
        ) ? trim($request->input('channel_filter')) : '';

        $availableStatuses = $serviceHelper->getAvailableStatuses();
        $orderStatus = (
            $request->has('order_status_filter')
            && (trim($request->input('order_status_filter')) != '')
            && array_key_exists(trim($request->input('order_status_filter')), $availableStatuses)
        ) ? trim($request->input('order_status_filter')) : '';

        $deliveryDate = (
            $request->has('delivery_date_filter')
            && (trim($request->input('delivery_date_filter')) != '')
        ) ? trim($request->input('delivery_date_filter')) : '';

        $deliverySlot = (
            $request->has('delivery_slot_filter')
            && (trim($request->input('delivery_slot_filter')) != '')
        ) ? trim($request->input('delivery_slot_filter')) : '';

        $filteredOrders = $serviceHelper->getSaleOrders($region, $apiChannel, $orderStatus, $deliveryDate, $deliverySlot);
        if (!$filteredOrders) {
            return response()->json([], 200);
        }

        $filteredOrderData = [];
        $totalRec = 0;
        $collectRecStart = $dtStart;
        $collectRecEnd = $collectRecStart + $dtPageLength;
        $currentRec = -1;
        $currentRole = null;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $currentRole = $sessionUser['roleCode'];
        }
        foreach ($filteredOrders as $record) {
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
            $emirateId = $record->region_id;
            $tempRecord['region'] = $emirates[$emirateId];
            $shipAddress = $record->shippingAddress;
            $shipAddressString = '';
            $shipAddressString .= (isset($shipAddress->company)) ? $shipAddress->company . ' ' : '';
            $shipAddressString .= (isset($shipAddress->address_1)) ? $shipAddress->address_1 : '';
            $shipAddressString .= (isset($shipAddress->address_2)) ? ', ' . $shipAddress->address_2 : '';
            $shipAddressString .= (isset($shipAddress->address_3)) ? ', ' . $shipAddress->address_3 : '';
            $shipAddressString .= (isset($shipAddress->city)) ? ', ' . $shipAddress->city : '';
            $shipAddressString .= (isset($shipAddress->region)) ? ', ' . $shipAddress->region : '';
            $shipAddressString .= (isset($shipAddress->post_code)) ? ', ' . $shipAddress->post_code : '';
            $tempRecord['customerName'] = $shipAddress->first_name . ' ' . $shipAddress->last_name;
            $tempRecord['customerAddress'] = $shipAddressString;
            $tempRecord['deliveryDate'] = (!is_null($record->delivery_date)) ? date('d-m-Y', strtotime($record->delivery_date)) : '';
            $tempRecord['deliveryTimeSlot'] = $record->delivery_time_slot;
            $tempRecord['deliveryPicker'] = '';
            $tempRecord['deliveryPickerTime'] = '';
            $tempRecord['deliveryDriver'] = '';
            $tempRecord['deliveryDriverTime'] = '';
            $orderStatusId = $record->order_status;
            $tempRecord['orderStatus'] = $availableStatuses[$orderStatusId];
            $deliveryPickerData = $record->currentPicker;
            $deliveryDriverData = $record->currentDriver;
            $tempRecord['actions'] = (!is_null($currentRole)) ? url('/' . $currentRole . '/order-view/' . $record->id) : 'javascript:void(0);';
            if ($deliveryPickerData && (count($deliveryPickerData) > 0)) {
                $pickerDetail = $deliveryPickerData[0];
                $tempRecord['deliveryPickerTime'] = $serviceHelper->getFormattedTime($pickerDetail->done_at, 'F d, Y, h:i:s A');
                if ($pickerDetail->actionDoer) {
                    $tempRecord['deliveryPicker'] = $pickerDetail->actionDoer->name;
                }
            }
            if ($deliveryDriverData && (count($deliveryDriverData) > 0)) {
                $driverDetail = $deliveryDriverData[0];
                $tempRecord['deliveryDriverTime'] = $serviceHelper->getFormattedTime($driverDetail->done_at, 'F d, Y, h:i:s A');
                if ($driverDetail->actionDoer) {
                    $tempRecord['deliveryDriver'] = $driverDetail->actionDoer->name;
                }
            }
            $filteredOrderData[] = $tempRecord;
        }

        $returnData = [
            'draw' => $dtDraw,
            'recordsTotal' => $totalRec,
            'recordsFiltered' => $totalRec,
            'data' => $filteredOrderData
        ];

        return response()->json($returnData, 200);

    }

    public function posView(Request $request) {

        $serviceHelper = new SalesServiceHelper();
        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();
        $deliveryTimeSlots = config('fms.delivery_time_slots');
        $orderSources = $serviceHelper->getAvailablePosOrderSources();
        $paymentMethods = $serviceHelper->getAvailablePosPaymentMethods();
        $selectedEmirate = (
            $request->has('emirate')
            && (trim($request->input('emirate')) != '')
            && array_key_exists(trim($request->input('emirate')), $emirates)
        ) ? trim($request->input('emirate')) : 'DXB';

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'POS System';

        $todayDate = date('Y-m-d');
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $regionsList = $serviceHelper->getAvailableRegionsList();
        $areaListRaw = $serviceHelper->getAvailableCityList();
        $areaList = [];
        foreach ($areaListRaw as $area) {
            $areaList[$area['region_id']] = $area['available_areas'];
        }

        return view('sales::pos', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'selectedEmirate',
            'todayDate',
            'availableApiChannels',
            'serviceHelper',
            'orderSources',
            'regionsList',
            'areaList',
            'deliveryTimeSlots',
            'paymentMethods'
        ));

    }

    public function posAddCart(Request $request) {

        $serviceHelper = new SalesServiceHelper();
        $returnCartHtml = '';

        $cartAction = (
            $request->has('action')
            && (trim($request->input('action')) != '')
        ) ? trim($request->input('action')) : null;

        $targetProductId = (
            $request->has('id')
            && (trim($request->input('id')) != '')
        ) ? trim($request->input('id')) : null;

        $targetProductRow = (
            $request->has('row')
            && (trim($request->input('row')) != '')
        ) ? trim($request->input('row')) : null;

        $targetProductItem = (
            $request->has('item')
            && (trim($request->input('item')) != '')
        ) ? trim($request->input('item')) : null;

        $targetProductBarcode = (
            $request->has('barcode')
            && (trim($request->input('barcode')) != '')
        ) ? trim($request->input('barcode')) : null;

        $targetProductType = (
            $request->has('product_type')
            && (trim($request->input('product_type')) != '')
        ) ? trim($request->input('product_type')) : null;

        if (!is_null($cartAction) && (trim($cartAction) == 'clearcart')) {
            session()->forget('qty_array');
            session()->forget('cart');
            session()->forget('actual_qty');
        }

        if (!is_null($cartAction) && (trim($cartAction) == 'add') && !is_null($targetProductId) && !is_null($targetProductRow)) {
            $productData = $serviceHelper->getProductData($targetProductId);
            if (is_array($productData) && (count($productData) > 0) && array_key_exists('items', $productData)) {
                $productItems = $productData['items'];
                if (is_array($productItems) && (count($productItems) > 0)) {
                    $sessionQtyArray = (session()->has('qty_array')) ? session()->get('qty_array') : [];
                    $incrementBy = (isset($productItems[0]['extension_attributes']['minimum_cart_qty']))
                        ? (int)$productItems[0]['extension_attributes']['minimum_cart_qty'] : 1;
                    $oldQuantity = (session()->has('qty_array') && array_key_exists($targetProductRow, session()->get('qty_array')))
                        ? (int)session()->get('qty_array')[$targetProductRow] : 0;
                    $sessionQtyArray[$targetProductRow] = $oldQuantity + $incrementBy;
                    session()->put('qty_array', $sessionQtyArray);
                }
            }
        }

        if (!is_null($cartAction) && (trim($cartAction) == 'remove') && !is_null($targetProductId) && !is_null($targetProductRow)) {
            $productData = $serviceHelper->getProductData($targetProductId);
            if (is_array($productData) && (count($productData) > 0) && array_key_exists('items', $productData)) {
                $productItems = $productData['items'];
                if (is_array($productItems) && (count($productItems) > 0)) {
                    $sessionQtyArray = (session()->has('qty_array')) ? session()->get('qty_array') : [];
                    $incrementBy = (isset($productItems[0]['extension_attributes']['minimum_cart_qty']))
                        ? (int)$productItems[0]['extension_attributes']['minimum_cart_qty'] : 1;
                    $oldQuantity = (session()->has('qty_array') && array_key_exists($targetProductRow, session()->get('qty_array')))
                        ? (int)session()->get('qty_array')[$targetProductRow] : 0;
                    $newQuantity = $oldQuantity - $incrementBy;
                    if ($newQuantity > 0) {
                        $sessionQtyArray[$targetProductRow] = $newQuantity;
                        session()->put('qty_array', $sessionQtyArray);
                    } else {
                        $sessionCart = (session()->has('cart')) ? session()->get('cart') : [];
                        $sessionActualQtyArray = (session()->has('actual_qty')) ? session()->get('actual_qty') : [];
                        unset($sessionCart[$targetProductRow]);
                        unset($sessionQtyArray[$targetProductRow]);
                        unset($sessionActualQtyArray[$targetProductRow]);
                        $sessionQtyArray = array_values($sessionQtyArray);
                        $sessionActualQtyArray = array_values($sessionActualQtyArray);
                        if (empty($sessionCart)) {
                            session()->forget('qty_array');
                            session()->forget('cart');
                            session()->forget('actual_qty');
                        } else {
                            session()->put('qty_array', $sessionQtyArray);
                            session()->put('cart', $sessionCart);
                            session()->put('actual_qty', $sessionActualQtyArray);
                        }
                    }
                }
            }
        }

        if (!is_null($cartAction) && (trim($cartAction) == 'removeitem') && !is_null($targetProductItem)) {
            $sessionCart = (session()->has('cart')) ? session()->get('cart') : [];
            $sessionQtyArray = (session()->has('qty_array')) ? session()->get('qty_array') : [];
            $sessionActualQtyArray = (session()->has('actual_qty')) ? session()->get('actual_qty') : [];
            $key = array_search($targetProductItem, $sessionCart);
            unset($sessionCart[$key]);
            unset($sessionQtyArray[$key]);
            unset($sessionActualQtyArray[$key]);
            $sessionQtyArray = array_values($sessionQtyArray);
            $sessionActualQtyArray = array_values($sessionActualQtyArray);
            if (empty($sessionCart)) {
                session()->forget('qty_array');
                session()->forget('cart');
                session()->forget('actual_qty');
            } else {
                session()->put('qty_array', $sessionQtyArray);
                session()->put('cart', $sessionCart);
                session()->put('actual_qty', $sessionActualQtyArray);
            }
        }

        $productBarcode = '';
        if (!is_null($targetProductBarcode) && !is_null($targetProductType)) {
            if($targetProductType == 'grocery'){
                $productBarcode = $targetProductBarcode;
            } if($targetProductType == 'fresh'){
                $productBarcode = str_pad(substr($targetProductBarcode, 0, 7),13,0);
            }
            $sessionCart = (session()->has('cart')) ? session()->get('cart') : [];
            $sessionQtyArray = (session()->has('qty_array')) ? session()->get('qty_array') : [];
            $sessionActualQtyArray = (session()->has('actual_qty')) ? session()->get('actual_qty') : [];
            if (!in_array($productBarcode, $sessionCart)) {
                $productData = $serviceHelper->getProductData($productBarcode);
                if (is_array($productData) && (count($productData) > 0) && array_key_exists('total_count', $productData)) {
                    array_push($sessionCart, $productBarcode);
                    $actualQty = (($targetProductType == 'fresh') && ($productData['items'][0]['extension_attributes']['selling_format'] == 'kg'))
                        ? (((int)substr(substr($targetProductBarcode,-6),1,-1)) / 1000)
                        : 1;
                    array_push($sessionActualQtyArray, $actualQty);
                } else {
                    $returnCartHtml .= '<div style="margin-bottom:20px;color:red;font-weight:bold;text-align:center">Product with barcode ' .
                        $productBarcode . ' Not Available</div>';
                }

            } else {
                foreach($sessionCart as $productIndex => $product){
                    if($productBarcode == $product) {
                        $oldQuantity = (array_key_exists($productIndex, $sessionQtyArray)) ? $sessionQtyArray[$productIndex] : 0;
                        $sessionQtyArray[$productIndex] = $oldQuantity + 1;
                    }
                }
            }

            session()->put('cart', $sessionCart);
            session()->put('qty_array', $sessionQtyArray);
            session()->put('actual_qty', $sessionActualQtyArray);
        }

        $cartTotal = 0;
        if (session()->has('cart') && !empty(session()->get('cart'))) {

            $sessionCart = (session()->has('cart')) ? session()->get('cart') : [];
            $sessionQtyArray = (session()->has('qty_array')) ? session()->get('qty_array') : [];
            $sessionActualQtyArray = (session()->has('actual_qty')) ? session()->get('actual_qty') : [];

            $index = 0;
            if(!isset($sessionQtyArray) || (count($sessionQtyArray) == 0)){
                $sessionQtyArray = array_fill(0, count($sessionCart), 1);
                session()->put('qty_array', $sessionQtyArray);
            }

            $returnCartHtml .='<table class="table table-bordered table-checkable cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price(AED)</th>
                        <th>Quantity</th>
                        <th class="text-right">Row Total(AED)</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($sessionCart as $productIndex => $product) {
                $productData = $serviceHelper->getProductData($product);
                if (is_array($productData) && (count($productData) > 0) && array_key_exists('items', $productData)) {
                    $productItems = $productData['items'];
                    if(empty($sessionQtyArray[$index])) {
                        $sessionQtyArray[$index] = 1;
                    }
                    if (is_array($productItems) && (count($productItems) > 0)) {
                        foreach ($productItems as $productItem) {

                            $saleFormat = $productItem['extension_attributes']['selling_format_label'];
                            $currentItemQty = $sessionQtyArray[$index];
                            if(
                                (fmod($sessionActualQtyArray[$index], $productItem['extension_attributes']['minimum_cart_qty']) != 0)
                                && !is_null($cartAction)
                                && (trim($cartAction) != 'add')
                                && (trim($cartAction) != 'remove')
                            ) {
                                $itemcount = floor($sessionActualQtyArray[$index] / $productItem['extension_attributes']['minimum_cart_qty']);
                                $currentItemQty = $productItem['extension_attributes']['minimum_cart_qty'] * $itemcount;
                                $sessionQtyArray[$index] = $currentItemQty;
                            }

                            $returnCartHtml .='<input type="hidden" name="qty[]" value="' . $currentItemQty . '">';
                            $returnCartHtml .='<input type="hidden" name="barcode[]" value="' . $product . '">';
                            $returnCartHtml .='<tr>';

                            $returnCartHtml .='<td>
                                <span class="font-weight-bold">' . $productItem['name'] . '</span><br/>
                                <strong>'.$productItem['sku'].'</strong>
                                <p>
                                    <small>
                                        <a href="#" class="text-danger item-remove-btn" data-product-id="' . $product . '"\>
                                            <i class="flaticon2-trash text-danger icon-nm"></i> Remove
                                        </a>
                                    </small>
                                </p>
                            </td>';

                            $specialPrice = false;
                            $itemPrice = $productItem['price'];
                            foreach($productItem['custom_attributes'] as $attribute){
                                if($attribute['attribute_code'] == 'special_price'){
                                    $specialPrice = true;
                                    $itemPrice = $attribute['value'];
                                }
                            }
                            $returnCartHtml .='<td>' . number_format($itemPrice,2).'<small>per '. $saleFormat .'</small></td>';

                            $returnCartHtml .='<td id="quan_td_'.$index.'">
                                <div class="input-group-sm input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <a href="#" class="product-remove-btn" data-product-id="' . $product . '" data-row-index="' . $index . '">-</a>
                                        </span>
                                    </div>
                                    <input type="text" value="' . $currentItemQty . ' ' . $saleFormat .'" class="form-control col-md-4" id="input-quantity-'.$productItem["id"].'">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <a href="#" class="product-add-btn" data-product-id="' . $product . '" data-row-index="' . $index . '">+</a>
                                        </span>
                                    </div>
                                </div>
                                <small>Actual Qty.: ' . $sessionActualQtyArray[$index] . ' ' . $productItem['extension_attributes']['selling_format_label'] . '</small>
                            </td>';
                            $returnCartHtml .='<td class="text-right">'.number_format($currentItemQty * $itemPrice, 2).'</td>';
                            $cartTotal += $currentItemQty * $itemPrice;
                            $returnCartHtml .='</tr>';

                        }
                    }
                }

                session()->put('qty_array', $sessionQtyArray);
                $index ++;

            }

            $returnCartHtml .='<input type="hidden" name="subtotal" id="subtotal" value="' . number_format($cartTotal,2) . '">';
            $returnCartHtml .='</tbody></table>';

        }

        $returnData = [
            'success' => true,
            'html' => $returnCartHtml
        ];
        return response()->json($returnData, 200);

    }

    public function posCreateOrder(Request $request) {

        $serviceHelper = new SalesServiceHelper();
        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();
        $deliveryTimeSlots = config('fms.delivery_time_slots');
        $orderSources = $serviceHelper->getAvailablePosOrderSources();
        $paymentMethods = $serviceHelper->getAvailablePosPaymentMethods();
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $regionsList = $serviceHelper->getAvailableRegionsList();
        $areaListRaw = $serviceHelper->getAvailableCityList();
        $areaList = [];
        foreach ($areaListRaw as $area) {
            $areaList[$area['region_id']] = $area['available_areas'];
        }

        $validator = Validator::make($request->all() , [
            'channel_id' => ['required', Rule::in(array_column($orderSources, 'code'))],
            'paymentMethod' => ['required', Rule::in(array_column($paymentMethods, 'method'))],
            'source_order_id' => ['nullable'],
            'region' => ['required', Rule::in(array_column($regionsList['available_regions'], 'id'))],
            'city' => ['required'],
            'street' => ['nullable'],
            'firstname' => ['nullable'],
            'lastname' => ['nullable'],
            'email' => ['nullable'],
            'telephone' => ['nullable'],
            'delivery_date' => ['required'],
            'delivery_time_slot' => ['required'],
            'discount' => ['nullable'],
            'number_of_box' => ['nullable'],
        ], [
            'channel_id.required' => 'The Channel Id should not be empty.',
            'channel_id.in' => 'The Channel Id given is invalid.',
            'paymentMethod.required' => 'The Payment Method should not be empty.',
            'paymentMethod.in' => 'The Payment Method given is invalid.',
            'region.required' => 'The Emirates should not be empty.',
            'region.in' => 'The Emirates given is invalid.',
            'city.required' => 'The Area should not be empty.',
            'delivery_date.required' => 'The Delivery Date should not be empty.',
            'delivery_time_slot.required' => 'The Delivery Time Slot should not be empty.',
        ]);

        if ($validator->fails()) {
            $returnData = [
                'success' => false,
                'message' => implode(' | ', $validator->errors()->all())
            ];
            return response()->json($returnData, 200);
        }

        $postData = $validator->validated();

        $checkArea = $areaList[$postData['region']];
        $areaCodes = array_column($checkArea, 'area_code');

        if (!in_array($postData['city'], $areaCodes)) {
            $returnData = [
                'success' => false,
                'message' => 'The Area given is invalid!'
            ];
            return response()->json($returnData, 200);
        }

        $sessionCart = (session()->has('cart')) ? session()->get('cart') : [];
        $sessionQtyArray = (session()->has('qty_array')) ? session()->get('qty_array') : [];
        $sessionActualQtyArray = (session()->has('actual_qty')) ? session()->get('actual_qty') : [];

        if ((count($sessionCart) == 0) || (count($sessionCart) != count($sessionQtyArray)) || (count($sessionCart) != count($sessionActualQtyArray))) {
            $returnData = [
                'success' => false,
                'message' => 'The cart is empty!'
            ];
            return response()->json($returnData, 200);
        }

        $orderItems = [];
        foreach ($sessionCart as $cartIndex => $cartProduct) {
            if($cartProduct != ''){
                $orderItems['items'][$cartIndex]['barcode'] = $cartProduct;
                $orderItems['items'][$cartIndex]['qty'] = $sessionQtyArray[$cartIndex];
                $orderItems['items'][$cartIndex]['actual_qty'] = $sessionActualQtyArray[$cartIndex];
            }
        }

        if (count($orderItems) == 0) {
            $returnData = [
                'success' => false,
                'message' => 'The cart is empty!'
            ];
            return response()->json($returnData, 200);
        }

        $orderRequestData = [];
        $orderRequestData['cartItem'] = $orderItems;
        $orderRequestData['paymentMethod']['method'] = $postData['paymentMethod'];
        $orderRequestData['sourceOrderId'] = (!is_null($postData['source_order_id'])) ? $postData['source_order_id'] : '';

        $orderRequestData['addressInformation']['shipping_address']['countryId'] = 'AE';
        $orderRequestData['addressInformation']['shipping_address']['region'] = $postData['region'];
        $orderRequestData['addressInformation']['shipping_address']['city'] = $postData['city'];
        $orderRequestData['addressInformation']['shipping_address']['street'][] = $postData['street'];
        $orderRequestData['addressInformation']['shipping_address']['firstname'] = $postData['firstname'];
        $orderRequestData['addressInformation']['shipping_address']['lastname'] = $postData['lastname'];
        $orderRequestData['addressInformation']['shipping_address']['email'] = $postData['email'];
        $orderRequestData['addressInformation']['shipping_address']['telephone'] = $postData['telephone'];

        $orderRequestData['deliveryInformation']['delivery_date'] = date('Y-m-d', strtotime($postData['delivery_date']));
        $orderRequestData['deliveryInformation']['delivery_time_slot'] = $postData['delivery_time_slot'];

        $orderRequestData['orderStatus'] = SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED;
        $orderRequestData['discount'] = (isset($postData['discount'])) ? $postData['discount'] : '0';
        $orderRequestData['number_of_boxes'] = (isset($postData['number_of_box'])) ? $postData['number_of_box'] : '1';

        $channelId = $postData['channel_id'];

        $processUserId = 0;
        $processUserRole = null;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $processUserId = $sessionUser['id'];
            $processUserRole = $sessionUser['roleCode'];
        }

        $orderPlaceResult = $serviceHelper->placePosOrder($orderRequestData, $channelId, $processUserId);
        if (!$orderPlaceResult['success']) {
            $returnData = [
                'success' => false,
                'message' => $orderPlaceResult['message']
            ];
            return response()->json($returnData, 200);
        }

        session()->forget('qty_array');
        session()->forget('cart');
        session()->forget('actual_qty');

        $orderResponse = $orderPlaceResult['response'];
        $newSaleOrderId = 0;
        $newOrderNumber = 0;
        $returnMessage = (array_key_exists('message', $orderResponse)) ? $orderResponse['message'] : '';
        if(!isset($orderResponse['order_id'])){
            $returnData = [
                'success' => true,
                'order_id' => $newSaleOrderId,
                'order_number' => $newOrderNumber,
                'html' => 'Order Placed successfully!',
                'message' => 'Order Placed successfully!',
            ];
            return response()->json($returnData, 200);
        }

        $newSaleOrderId = $orderResponse['order_id'];
        $newOrderNumber = $orderResponse['order_number'];
        $awbUrl = $orderResponse['awb_url'];

        $apiChannel = $serviceHelper->getApiChannel();
        $orderSaveResult = $serviceHelper->saveOrderToDatabase($apiChannel, $newSaleOrderId, $processUserId, 'pos');
        if (!$orderSaveResult['success']) {
            $returnData = [
                'success' => false,
                'message' => $orderSaveResult['message']
            ];
            return response()->json($returnData, 200);
        }

        $savedSaleOrder = $orderSaveResult['saleOrder'];
        $resultHtml = "Order <a target='_blank' href='";
        $resultHtml .= (!is_null($processUserRole)) ? url('/' . $processUserRole . '/order-view/' . $savedSaleOrder->id) : 'javascript:void(0);';
        $resultHtml .= "'>#" . $newOrderNumber . "</a> created successfully. ";
        $resultHtml .= "Please click on order link and wait for 10 sec to process the order.";
        $returnData = [
            'success' => true,
            'order_id' => $newSaleOrderId,
            'order_number' => $newOrderNumber,
            'awb_url' => $awbUrl,
            'errors' => $orderSaveResult['errors'],
            'html' => $resultHtml,
            'message' => $returnMessage
        ];
        return response()->json($returnData, 200);

    }

    public function stockUpdate(Request $request) {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Update Stock';

        $serviceHelper = new SalesServiceHelper();

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getAvailableStatuses();

        return view('sales::update-stock', compact(
            'pageTitle',
            'pageSubTitle',
            'availableApiChannels',
            'availableStatuses',
            'serviceHelper'
        ));

    }

    public function updateProductStockQty(Request $request) {

        $productSku = (
            $request->has('product_sku')
            && (trim($request->input('product_sku')) != '')
        ) ? trim($request->input('product_sku')) : '';

        if ($productSku == '') {
            return back()
                ->with('error', "The product SKU should not be empty!");
        }

        $serviceHelper = new SalesServiceHelper();

        $searchDetailResponse = $serviceHelper->getStockItemData($productSku);
        if (!$searchDetailResponse['status']) {
            return back()
                ->with('error', $searchDetailResponse['message']);
        }

        $searchDetails = $searchDetailResponse['response'];
        if ($searchDetails['message']) {
            return back()
                ->with('error', $searchDetails['message']);
        }

        if (!array_key_exists('item_id', $searchDetails)) {
            return back()
                ->with('error', 'Could not update the Product Stock for SKU "' . $productSku . '"');
        }

        $updateStockResponse = $serviceHelper->setProductOutOfStock($productSku, $searchDetails['item_id']);
        if (!$updateStockResponse['status']) {
            return back()
                ->with('error', $updateStockResponse['message']);
        }

        $updateStock = $updateStockResponse['response'];
        if ($updateStock['message']) {
            return back()
                ->with('error', $updateStock['message']);
        }

    }

    public function oosReport(Request $request) {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Out Of Stock Report';

        $serviceHelper = new SalesServiceHelper();

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getAvailableStatuses();

        $dayInterval = 3;
        $oosResult = $serviceHelper->getOutOfStockItems($dayInterval);
        $oosData = ($oosResult['status']) ? $oosResult['response'] : [];

        return view('sales::oos-report', compact(
            'pageTitle',
            'pageSubTitle',
            'availableApiChannels',
            'availableStatuses',
            'oosData',
            'serviceHelper'
        ));

    }

    public function orderItemsReport(Request $request) {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Order Items Sales Report';

        $serviceHelper = new SalesServiceHelper();

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getAvailableStatuses();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();

        return view('sales::order-items-report', compact(
            'pageTitle',
            'pageSubTitle',
            'availableApiChannels',
            'availableStatuses',
            'emirates',
            'deliveryTimeSlots',
            'serviceHelper'
        ));

    }

    public function filterOrderItemsReport(Request $request) {

        $serviceHelper = new SalesServiceHelper();

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getAvailableStatuses();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();

        $region = (
            $request->has('emirates_region')
            && (trim($request->input('emirates_region')) != '')
            && array_key_exists(trim($request->input('emirates_region')), $emirates)
        ) ? trim($request->input('emirates_region')) : '';

        $apiChannel = (
            $request->has('channel_filter')
            && (trim($request->input('channel_filter')) != '')
            && array_key_exists(trim($request->input('channel_filter')), $availableApiChannels)
        ) ? trim($request->input('channel_filter')) : '';

        $orderStatus = (
            $request->has('order_status_filter')
            && is_array($request->input('order_status_filter'))
            && (count($request->input('order_status_filter')) > 0)
        ) ? trim($request->input('order_status_filter')) : [];

        $startDate = (
            $request->has('delivery_date_start_filter')
            && (trim($request->input('delivery_date_start_filter')) != '')
        ) ? trim($request->input('delivery_date_start_filter')) : date('Y-m-d');

        $endDate = (
            $request->has('delivery_date_end_filter')
            && (trim($request->input('delivery_date_end_filter')) != '')
        ) ? trim($request->input('delivery_date_end_filter')) : date('Y-m-d');

        $deliverySlot = (
            $request->has('delivery_slot_filter')
            && (trim($request->input('delivery_slot_filter')) != '')
        ) ? trim($request->input('delivery_slot_filter')) : '';

        $orderStatusClean = [];
        foreach ($orderStatus as $statusEl) {
            if (array_key_exists($statusEl, $availableStatuses)) {
                $orderStatusClean[] = $statusEl;
            }
        }

        $fromDate = '';
        $toDate = '';
        if ($endDate > $startDate) {
            $fromDate = $startDate;
            $toDate = $endDate;
        } else {
            $fromDate = $endDate;
            $toDate = $startDate;
        }

        $filterResult = $serviceHelper->getSaleOrderItemsReport($region, $apiChannel, $orderStatusClean, $fromDate, $toDate, $deliverySlot);
        if (count($filterResult) == 0) {
            return back()
                ->with('error', "There is no record to export the CSV file.");
        }

        $fileName = "orders-items_" . date('Y-m-d', strtotime($fromDate)) . "_" . date('Y-m-d', strtotime($toDate)) . ".csv";
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $headingColumns = ["SKU", "Name", "Total Qty","Total Return Qty", "Supplier", "Item Type"];

        $callback = function() use($filterResult, $headingColumns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($headingColumns));
            if(!empty($filterResult)) {
                foreach($filterResult as $row) {
                    fputcsv($file, [
                        $row['item_sku'],
                        $row['item_name'],
                        $row['total_qty'],
                        $row['total_return_qty'],
                        $row['supplier_name'],
                        $row['item_type']
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);

    }

    public function picklist(Request $request) {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Sales Picklist';

        $serviceHelper = new SalesServiceHelper();
        $emirates = $serviceHelper->getAvailableRegionsList();
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $availableStatuses = $serviceHelper->getPicklistStatuses();
        $deliveryTimeSlots = $serviceHelper->getDeliveryTimeSlots();
        $productCategories = $serviceHelper->getProductCategories();

        $userRoleObj = new UserRole();
        $pickers = $userRoleObj->allPickers();

        $storeAvailability = [
            '1' => 'Not Checked',
            '2' => 'Available',
            '3' => 'Not Available',
        ];

        $todayDate = date('Y-m-d');

        return view('sales::picklist', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'todayDate',
            'availableApiChannels',
            'availableStatuses',
            'deliveryTimeSlots',
            'productCategories',
            'storeAvailability',
            'pickers',
            'serviceHelper'
        ));

    }

    public function filterPicklist(Request $request) {

        set_time_limit(600);

        $serviceHelper = new SalesServiceHelper();

        $availableActions = ['datatable', 'pdf_generator', 'csv_generator'];
        $methodAction = (
            $request->has('filter_action')
            && (trim($request->input('filter_action')) != '')
            && in_array(trim($request->input('filter_action')), $availableActions)
        ) ? trim($request->input('filter_action')) : 'datatable';

        $dtDraw = (
            $request->has('draw')
            && (trim($request->input('draw')) != '')
        ) ? (int)trim($request->input('draw')) : 1;

        $dtStart = (
            $request->has('start')
            && (trim($request->input('start')) != '')
        ) ? (int)trim($request->input('start')) : 0;

        $dtPageLength = (
            $request->has('length')
            && (trim($request->input('length')) != '')
        ) ? (int)trim($request->input('length')) : 10;

        /*$emirates = config('fms.emirates');*/
        $emirates = $serviceHelper->getAvailableRegionsList();
        $region = (
            $request->has('emirates_region')
            && (trim($request->input('emirates_region')) != '')
        ) ? explode(',', trim($request->input('emirates_region'))) : [];

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();
        $apiChannel = (
            $request->has('channel_filter')
            && (trim($request->input('channel_filter')) != '')
            && array_key_exists(trim($request->input('channel_filter')), $availableApiChannels)
        ) ? trim($request->input('channel_filter')) : '';

        $availableStatuses = $serviceHelper->getPicklistStatuses();
        $orderStatus = (
            $request->has('order_status_values')
            && (trim($request->input('order_status_values')) != '')
        ) ? explode(',', trim($request->input('order_status_values'))) : [];

        $pickerFilter = (
            $request->has('picker_values')
            && (trim($request->input('picker_values')) != '')
        ) ? explode(',', trim($request->input('picker_values'))) : [];

        $startDate = (
            $request->has('delivery_date_start_filter')
            && (trim($request->input('delivery_date_start_filter')) != '')
        ) ? trim($request->input('delivery_date_start_filter')) : date('Y-m-d');

        $endDate = (
            $request->has('delivery_date_end_filter')
            && (trim($request->input('delivery_date_end_filter')) != '')
        ) ? trim($request->input('delivery_date_end_filter')) : date('Y-m-d');

        $deliverySlot = (
            $request->has('delivery_slot_filter')
            && (trim($request->input('delivery_slot_filter')) != '')
        ) ? trim($request->input('delivery_slot_filter')) : '';

        $productCategories = $serviceHelper->getProductCategories();
        $categoryFilter = (
            $request->has('product_category_values')
            && (trim($request->input('product_category_values')) != '')
        ) ? explode(',', trim($request->input('product_category_values'))) : [];

        $storeAvailability = [
            '1' => 'Not Checked',
            '2' => 'Available',
            '3' => 'Not Available',
        ];
        $availabilityFilter = (
            $request->has('store_availability_values')
            && (trim($request->input('store_availability_values')) != '')
        ) ? explode(',', trim($request->input('store_availability_values'))) : [];

        $itemSelectedFilter = (
            $request->has('items_selected_values')
            && (trim($request->input('items_selected_values')) != '')
        ) ? explode(',', trim($request->input('items_selected_values'))) : [];

        $filteredOrders = $serviceHelper->getSaleOrderPickList($region, $apiChannel, $orderStatus, $startDate, $endDate, $deliverySlot);
        if ($filteredOrders) {
            if ($methodAction == 'datatable') {

                $filteredOrderData = [];
                $totalRec = 0;
                $collectRecStart = $dtStart;
                $collectRecEnd = $collectRecStart + $dtPageLength;
                $currentRec = -1;
                foreach ($filteredOrders as $record) {

                    $currentPicker = SaleOrderProcessHistory::select('*')
                        ->where('order_id', $record->id)
                        ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP)
                        ->orderBy('done_at', 'desc')
                        ->limit(1)->get();

                    $deliveryHistory = null;
                    $userEl = null;
                    $userCurrentId = 0;
                    if ($currentPicker && (count($currentPicker) > 0)) {
                        $deliveryHistory = $currentPicker->first();
                        $userElQ = User::select('*')
                            ->where('id', $deliveryHistory->done_by)->get();
                        $userEl = ($userElQ) ? $userElQ->first() : $deliveryHistory->actionDoer;
                        $userCurrentId = (int)$userEl->id;
                    }

                    if ((count($pickerFilter) == 0) || (in_array($userCurrentId, $pickerFilter))) {

                        $record->orderItems;
                        if ($record->orderItems && (count($record->orderItems) > 0)) {
                            $orderItems = $record->orderItems;
                            foreach ($orderItems as $orderItemEl) {

                                $qtyOrdered = (float)$orderItemEl->qty_ordered;
                                $qtyCanceled = (float)$orderItemEl->qty_canceled;
                                $qtyNeeded = $qtyOrdered - $qtyCanceled;
                                $epsilon = 0.00001;
                                if ($qtyNeeded <= $epsilon) {
                                    continue;
                                }

                                $productId = $orderItemEl->product_id;
                                $productCat = null;
                                if ($orderItemEl->productCategory) {
                                    $productCat = $orderItemEl->productCategory;
                                }

                                if (is_null($productCat)) {
                                    $catApiResult = $serviceHelper->getCategoryByProductId($productId, $record->env, $record->channel);
                                    if (!is_null($catApiResult)) {
                                        $productCat = ProductCategory::firstOrCreate([
                                            'env' => $record->env,
                                            'channel' => $record->channel,
                                            'product_id' => $productId
                                        ], [
                                            'product_sku' => $catApiResult['product_sku'],
                                            'product_name' => $catApiResult['product_name'],
                                            'category_id' => $catApiResult['category_id'],
                                            'category_name' => $catApiResult['category_name']
                                        ]);
                                    }
                                }

                                $productCatIdFinal = '0';
                                $productCatFinal = 'UnCat';
                                if (!is_null($productCat)) {
                                    $productCatIdFinal = strval($productCat->category_id);
                                    $productCatFinal = strval($productCat->category_name);
                                }

                                $storeAvailabilityStatus = null;
                                $storeAvailabilityStatusLabel = '';
                                if ($orderItemEl->isNotCheckedStoreAvailable()) {
                                    $storeAvailabilityStatus = '1';
                                    $storeAvailabilityStatusLabel = 'Not Checked';
                                } elseif ($orderItemEl->isStoreAvailable()) {
                                    $storeAvailabilityStatus = '2';
                                    $storeAvailabilityStatusLabel = 'Available';
                                } elseif ($orderItemEl->isNotStoreAvailable()) {
                                    $storeAvailabilityStatus = '3';
                                    $storeAvailabilityStatusLabel = 'Not Available';
                                }

                                if ((count($categoryFilter) == 0) || in_array($productCatIdFinal, $categoryFilter)) {

                                    if ((count($availabilityFilter) == 0) || (!is_null($storeAvailabilityStatus) && in_array($storeAvailabilityStatus, $availabilityFilter))) {

                                        $totalRec++;
                                        $currentRec++;
                                        if (($currentRec >= $collectRecStart) && ($currentRec < $collectRecEnd)) {

                                            $itemSelector = '<label class="checkbox checkbox-single checkbox-solid checkbox-primary mb-0 justify-content-center">';
                                            $itemSelector .= '<input type="checkbox" value="" class="checkable sales-picklist-item" id="sales-picklist-item-' . $orderItemEl->id;
                                            $itemSelector .= '" name="sales-picklist-item-' . $orderItemEl->id .'" data-item-id="' . $orderItemEl->id . '"/><span></span></label>';

                                            $filteredOrderData[] = [
                                                'itemSelector' => $itemSelector,
                                                'deliveryDate' => date('d-m-Y', strtotime($record->delivery_date)),
                                                'deliveryTimeSlot' => $record->delivery_time_slot,
                                                'picker' => (!is_null($userEl)) ? $userEl->name : 'Unassigned',
                                                'orderId' => $record->increment_id,
                                                'productType' => $productCatFinal,
                                                'productSku' => $orderItemEl->item_sku,
                                                'productName' => $orderItemEl->item_name,
                                                'quantity' => $qtyNeeded,
                                                'availability' => $storeAvailabilityStatusLabel,
                                            ];

                                        }

                                    }

                                }

                            }
                        }

                    }

                }

                $returnData = [
                    'draw' => $dtDraw,
                    'recordsTotal' => $totalRec,
                    'recordsFiltered' => $totalRec,
                    'data' => $filteredOrderData
                ];
                return response()->json($returnData, 200);

            } elseif ($methodAction == 'pdf_generator') {

                $filteredOrderData = [];
                $filteredOrderTotalWeight = [];

                foreach ($filteredOrders as $record) {

                    $currentPicker = SaleOrderProcessHistory::select('*')
                        ->where('order_id', $record->id)
                        ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP)
                        ->orderBy('done_at', 'desc')
                        ->limit(1)->get();

                    $deliveryHistory = null;
                    $userEl = null;
                    $userCurrentId = 0;
                    if ($currentPicker && (count($currentPicker) > 0)) {
                        $deliveryHistory = $currentPicker->first();
                        $userElQ = User::select('*')
                            ->where('id', $deliveryHistory->done_by)->get();
                        $userEl = ($userElQ) ? $userElQ->first() : $deliveryHistory->actionDoer;
                        $userCurrentId = (int)$userEl->id;
                    }

                    if ((count($pickerFilter) == 0) || (in_array($userCurrentId, $pickerFilter))) {

                        $record->orderItems;
                        $record->shippingAddress;

                        if ($record->orderItems && (count($record->orderItems) > 0)) {

                            $orderItems = $record->orderItems;
                            foreach ($orderItems as $orderItemEl) {

                                $qtyOrdered = (float)$orderItemEl->qty_ordered;
                                $qtyCanceled = (float)$orderItemEl->qty_canceled;
                                $qtyNeeded = $qtyOrdered - $qtyCanceled;
                                $epsilon = 0.00001;
                                if ($qtyNeeded <= $epsilon) {
                                    continue;
                                }

                                $productId = $orderItemEl->product_id;
                                $productCat = null;
                                if ($orderItemEl->productCategory) {
                                    $productCat = $orderItemEl->productCategory;
                                }

                                if (is_null($productCat)) {
                                    $catApiResult = $serviceHelper->getCategoryByProductId($productId, $record->env, $record->channel);
                                    if (!is_null($catApiResult)) {
                                        $productCat = ProductCategory::firstOrCreate([
                                            'env' => $record->env,
                                            'channel' => $record->channel,
                                            'product_id' => $productId
                                        ], [
                                            'product_sku' => $catApiResult['product_sku'],
                                            'product_name' => $catApiResult['product_name'],
                                            'category_id' => $catApiResult['category_id'],
                                            'category_name' => $catApiResult['category_name']
                                        ]);
                                    }
                                }

                                $productCatIdFinal = '0';
                                $productCatFinal = 'UnCat';
                                if (!is_null($productCat)) {
                                    $productCatIdFinal = strval($productCat->category_id);
                                    $productCatFinal = strval($productCat->category_name);
                                }

                                $storeAvailabilityStatus = null;
                                $storeAvailabilityStatusLabel = '';
                                if ($orderItemEl->isNotCheckedStoreAvailable()) {
                                    $storeAvailabilityStatus = '1';
                                    $storeAvailabilityStatusLabel = 'Not Checked';
                                } elseif ($orderItemEl->isStoreAvailable()) {
                                    $storeAvailabilityStatus = '2';
                                    $storeAvailabilityStatusLabel = 'Available';
                                } elseif ($orderItemEl->isNotStoreAvailable()) {
                                    $storeAvailabilityStatus = '3';
                                    $storeAvailabilityStatusLabel = 'Not Available';
                                }

                                if ((count($categoryFilter) == 0) || in_array($productCatIdFinal, $categoryFilter)) {

                                    if ((count($availabilityFilter) == 0) || (!is_null($storeAvailabilityStatus) && in_array($storeAvailabilityStatus, $availabilityFilter))) {

                                        if ((count($itemSelectedFilter) == 0) || in_array($orderItemEl->id, $itemSelectedFilter)) {

                                            $productWeight = 0;
                                            if (array_key_exists($productCatIdFinal, $filteredOrderTotalWeight)) {
                                                $productWeight = (float) $filteredOrderTotalWeight[$productCatIdFinal]['weight'];
                                            }
                                            $filteredOrderTotalWeight[$productCatIdFinal]['id'] = $productCatIdFinal;
                                            $filteredOrderTotalWeight[$productCatIdFinal]['title'] = $productCatFinal;
                                            $filteredOrderTotalWeight[$productCatIdFinal]['weight'] = $productWeight + (float) $orderItemEl->qty_ordered;

                                            $filteredOrderData[$record->delivery_date][$record->delivery_time_slot][$record->order_id]['items'][$productCatIdFinal][] = [
                                                'deliveryDate' => $record->delivery_date,
                                                'deliveryTimeSlot' => $record->delivery_time_slot,
                                                'picker' => (!is_null($userEl)) ? $userEl->name : 'Unassigned',
                                                'orderId' => $record->order_id,
                                                'orderNumber' => $record->increment_id,
                                                'productType' => $productCatFinal,
                                                'productSku' => $orderItemEl->item_sku,
                                                'productName' => $orderItemEl->item_name,
                                                'productInfo' => $orderItemEl->item_info,
                                                'quantity' => $qtyNeeded,
                                                'sellingUnit' => $orderItemEl->selling_unit
                                            ];

                                            $filteredOrderData[$record->delivery_date][$record->delivery_time_slot][$record->order_id]['deliveryNotes'] = $record->delivery_notes;
                                            $filteredOrderData[$record->delivery_date][$record->delivery_time_slot][$record->order_id]['shippingAddress'] = [];
                                            if ($record->shippingAddress) {
                                                $shippingAddress = $record->shippingAddress;
                                                $filteredOrderData[$record->delivery_date][$record->delivery_time_slot][$record->order_id]['shippingAddress'] = [
                                                    'firstName' => $shippingAddress['first_name'],
                                                    'lastName' => $shippingAddress['last_name'],
                                                    'address1' => $shippingAddress['address_1'],
                                                    'address2' => $shippingAddress['address_2'],
                                                    'address3' => $shippingAddress['address_3'],
                                                    'city' => $shippingAddress['city'],
                                                    'region' => (!is_null($shippingAddress['region']) && array_key_exists($shippingAddress['region'], $emirates)) ? $emirates[$shippingAddress['region']] : $shippingAddress['region'],
                                                    'region_code' => (!is_null($shippingAddress['region_code']) && array_key_exists($shippingAddress['region_code'], $emirates)) ? $emirates[$shippingAddress['region_code']] : $shippingAddress['region_code'],
                                                    'countryId' => $shippingAddress['country_id'],
                                                    'postCode' => $shippingAddress['post_code'],
                                                    'contactNumber' => $shippingAddress['contact_number'],
                                                ];
                                            }

                                        }

                                    }

                                }

                            }

                        }

                    }

                }

                try {

                    $pdfOrientation = 'P';
                    $pdfPaperSize = 'A4';
                    $pdfUseLang = 'en';
                    $pdfDefaultFont = 'Arial';

                    $path = public_path('ktmt/media/logos/aanacart-favicon-final.png');
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $logoEncoded = 'data:image/' . $type . ';base64,' . base64_encode($data);

                    $fulfilledBy = config('fms.fulfillment.done_by');

                    /*$pdfContent = view('sales::print-picklist', compact('filteredOrderData', 'filteredOrderTotalWeight', 'startDate', 'endDate', 'deliverySlot', 'logoEncoded', 'fulfilledBy'))->render();*/

                    $pdfContent = "";
                    $newOrderPrintData = $filteredOrderData;
                    $filteredOrderData = [];
                    if (is_array($newOrderPrintData) && (count($newOrderPrintData) > 0)) {
                        foreach ($newOrderPrintData as $dateOrderKey => $dateOrderEl) {
                            foreach ($dateOrderEl as $slotOrderKey => $slotOrderEl) {
                                foreach ($slotOrderEl as $idOrderKey => $idOrderEl) {

                                    $filteredOrderData = [];
                                    $filteredOrderData[$dateOrderKey][$slotOrderKey][$idOrderKey] = $idOrderEl;
                                    $pdfContent .= view('sales::print-picklist', compact('filteredOrderData', 'filteredOrderTotalWeight', 'startDate', 'endDate', 'deliverySlot', 'logoEncoded', 'fulfilledBy'))->render();

                                }
                            }
                        }
                    }

                    $pdfName = "print-sales-picklist-" . date('Ymd-His') . ".pdf";
                    $outputMode = 'D';

                    $html2pdf = new Html2Pdf($pdfOrientation, $pdfPaperSize, $pdfUseLang);
                    $html2pdf->setDefaultFont($pdfDefaultFont);
                    $html2pdf->setTestTdInOnePage(false);
                    $html2pdf->writeHTML($pdfContent);
                    $pdfOutput = $html2pdf->output($pdfName, $outputMode);

                } catch (Html2PdfException $e) {
                    $html2pdf->clean();
                    $formatter = new ExceptionFormatter($e);
                    return back()
                        ->with('error', $formatter->getMessage());
                }

            } elseif ($methodAction == 'csv_generator') {

                $filteredOrderData = [];

                foreach ($filteredOrders as $record) {

                    $currentPicker = SaleOrderProcessHistory::select('*')
                        ->where('order_id', $record->id)
                        ->where('action', SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP)
                        ->orderBy('done_at', 'desc')
                        ->limit(1)->get();

                    $deliveryHistory = null;
                    $userEl = null;
                    $userCurrentId = 0;
                    if ($currentPicker && (count($currentPicker) > 0)) {
                        $deliveryHistory = $currentPicker->first();
                        $userElQ = User::select('*')
                            ->where('id', $deliveryHistory->done_by)->get();
                        $userEl = ($userElQ) ? $userElQ->first() : $deliveryHistory->actionDoer;
                        $userCurrentId = (int)$userEl->id;
                    }

                    if ((count($pickerFilter) == 0) || (in_array($userCurrentId, $pickerFilter))) {

                        $record->orderItems;
                        $record->shippingAddress;

                        if ($record->orderItems && (count($record->orderItems) > 0)) {

                            $orderItems = $record->orderItems;
                            foreach ($orderItems as $orderItemEl) {

                                $qtyOrdered = (float)$orderItemEl->qty_ordered;
                                $qtyCanceled = (float)$orderItemEl->qty_canceled;
                                $qtyNeeded = $qtyOrdered - $qtyCanceled;
                                $epsilon = 0.00001;
                                if ($qtyNeeded <= $epsilon) {
                                    continue;
                                }

                                $productId = $orderItemEl->product_id;
                                $productCat = null;
                                if ($orderItemEl->productCategory) {
                                    $productCat = $orderItemEl->productCategory;
                                }

                                if (is_null($productCat)) {
                                    $catApiResult = $serviceHelper->getCategoryByProductId($productId, $record->env, $record->channel);
                                    if (!is_null($catApiResult)) {
                                        $productCat = ProductCategory::firstOrCreate([
                                            'env' => $record->env,
                                            'channel' => $record->channel,
                                            'product_id' => $productId
                                        ], [
                                            'product_sku' => $catApiResult['product_sku'],
                                            'product_name' => $catApiResult['product_name'],
                                            'category_id' => $catApiResult['category_id'],
                                            'category_name' => $catApiResult['category_name']
                                        ]);
                                    }
                                }

                                $productCatIdFinal = '0';
                                $productCatFinal = 'UnCat';
                                if (!is_null($productCat)) {
                                    $productCatIdFinal = strval($productCat->category_id);
                                    $productCatFinal = strval($productCat->category_name);
                                }

                                $storeAvailabilityStatus = null;
                                $storeAvailabilityStatusLabel = '';
                                if ($orderItemEl->isNotCheckedStoreAvailable()) {
                                    $storeAvailabilityStatus = '1';
                                    $storeAvailabilityStatusLabel = 'Not Checked';
                                } elseif ($orderItemEl->isStoreAvailable()) {
                                    $storeAvailabilityStatus = '2';
                                    $storeAvailabilityStatusLabel = 'Available';
                                } elseif ($orderItemEl->isNotStoreAvailable()) {
                                    $storeAvailabilityStatus = '3';
                                    $storeAvailabilityStatusLabel = 'Not Available';
                                }

                                if ((count($categoryFilter) == 0) || in_array($productCatIdFinal, $categoryFilter)) {

                                    if ((count($availabilityFilter) == 0) || (!is_null($storeAvailabilityStatus) && in_array($storeAvailabilityStatus, $availabilityFilter))) {

                                        if ((count($itemSelectedFilter) == 0) || in_array($orderItemEl->id, $itemSelectedFilter)) {

                                            $orderNumber = (!empty($record->increment_id)) ? "#" . $record->increment_id : "";
                                            $productCategory = (!empty($productCatFinal)) ? $productCatFinal : "";
                                            $productSku = (!empty($orderItemEl->item_sku)) ? $orderItemEl->item_sku : "";
                                            $productName = (!empty($orderItemEl->item_name)) ? $orderItemEl->item_name : "";
                                            $weightInfo = (!empty($orderItemEl->item_info)) ? $orderItemEl->item_info : "";
                                            $nameLabel = '';
                                            $nameLabel .= ($productName != '') ? $productName : '';
                                            $nameLabel .= (($nameLabel != '') && ($weightInfo != '')) ? ' ( Pack & Weight Info : ' . $weightInfo . ')' : '';
                                            $qtyToShow = (!empty($qtyNeeded)) ? $qtyNeeded : "";
                                            $sellingFormat = (!empty($orderItemEl->selling_unit)) ? $orderItemEl->selling_unit : "";
                                            $qtyLabel = '';
                                            $qtyLabel .= ($qtyToShow != '') ? $qtyToShow : '';
                                            $qtyLabel .= (($qtyLabel != '') && ($sellingFormat != '')) ? ' ' . $sellingFormat : '';

                                            $filteredOrderData[] = [
                                                'deliveryDate' => date('d-m-Y', strtotime($record->delivery_date)),
                                                'deliveryTimeSlot' => $record->delivery_time_slot,
                                                'picker' => (!is_null($userEl)) ? $userEl->name : 'Unassigned',
                                                'orderNumber' => $orderNumber,
                                                'productCat' => $productCategory,
                                                'productSku' => $productSku,
                                                'productName' => $nameLabel,
                                                'quantity' => $qtyLabel,
                                                'availability' => $storeAvailabilityStatusLabel
                                            ];

                                        }

                                    }

                                }

                            }

                        }

                    }

                }

                if (count($filteredOrderData) <= 0) {
                    return back()
                        ->with('error', "No Data to export the Sales Picklist!");
                }

                $fileName =  "sales_picklist_" . date('Ymd-His') . ".csv";
                $headers = array(
                    "Content-type"        => "text/csv",
                    "Content-Disposition" => "attachment; filename=$fileName",
                    "Pragma"              => "no-cache",
                    "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                    "Expires"             => "0"
                );

                $headingColumns = ["Delivery Date", "Time Slot", "Picker", "Order Number", "Product Type", "Product SKU", "Product Name", "Quantity", "Availability Status"];

                $callback = function() use($filteredOrderData, $headingColumns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, array_values($headingColumns));
                    if(!empty($filteredOrderData)) {
                        foreach($filteredOrderData as $row) {
                            fputcsv($file, [
                                $row['deliveryDate'],
                                $row['deliveryTimeSlot'],
                                $row['picker'],
                                $row['orderNumber'],
                                $row['productCat'],
                                $row['productSku'],
                                $row['productName'],
                                $row['quantity'],
                                $row['availability']
                            ]);
                        }
                    }
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);

            }
        } else {
            if ($methodAction == 'datatable') {
                $returnData = [
                    'draw' => $dtDraw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ];
                return response()->json($returnData, 200);
            } elseif ($methodAction == 'pdf_generator') {
                return back()
                    ->with('error', 'No Data to print the Sales Picklist!');
            } elseif ($methodAction == 'csv_generator') {
                return back()
                    ->with('error', 'No Data to export the Sales Picklist!');
            }
        }

    }

    public function regionsList(Request $request) {

        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Sales Regions List';

        $serviceHelper = new SalesServiceHelper();
        $emirates = $serviceHelper->getAvailableRegionsList('', '', '', false, true);
        $availableApiChannels = $serviceHelper->getAllAvailableChannels();

        return view('sales::regionslist', compact(
            'pageTitle',
            'pageSubTitle',
            'emirates',
            'availableApiChannels',
            'serviceHelper'
        ));

    }

    public function regionsListFilter(Request $request) {

        $serviceHelper = new SalesServiceHelper();

        $availableApiChannels = $serviceHelper->getAllAvailableChannels();

        $availableActions = ['datatable', 'server_sync', 'kerabiya_enable', 'kerabiya_disable'];
        $methodAction = (
            $request->has('filter_action')
            && (trim($request->input('filter_action')) != '')
            && in_array(trim($request->input('filter_action')), $availableActions)
        ) ? trim($request->input('filter_action')) : 'datatable';

        $dtDraw = (
            $request->has('draw')
            && (trim($request->input('draw')) != '')
        ) ? (int)trim($request->input('draw')) : 1;

        $dtStart = (
            $request->has('start')
            && (trim($request->input('start')) != '')
        ) ? (int)trim($request->input('start')) : 0;

        $dtPageLength = (
            $request->has('length')
            && (trim($request->input('length')) != '')
        ) ? (int)trim($request->input('length')) : 10;

        $itemSelectedFilter = (
            $request->has('region_items_selected_values')
            && (trim($request->input('region_items_selected_values')) != '')
        ) ? explode(',', trim($request->input('region_items_selected_values'))) : [];

        if ($methodAction == 'datatable') {

            $filteredEmiratesData = [];
            $totalRec = 0;

            $filteredEmirates = $serviceHelper->getAvailableRegionsList('', '', '', false, true);
            if (is_array($filteredEmirates) && (count($filteredEmirates) > 0)) {

                $collectRecStart = $dtStart;
                $collectRecEnd = $collectRecStart + $dtPageLength;
                $currentRec = -1;

                foreach ($filteredEmirates as $record) {

                    $totalRec++;
                    $currentRec++;
                    if (($currentRec < $collectRecStart) || ($currentRec >= $collectRecEnd)) {
                        continue;
                    }

                    $itemSelector = '<label class="checkbox checkbox-single checkbox-solid checkbox-primary mb-0 justify-content-center">';
                    $itemSelector .= '<input type="checkbox" value="" class="checkable sales-region-item" id="sales-region-item-' . $record['id'];
                    $itemSelector .= '" name="sales-region-item-' . $record['id'] .'" data-item-id="' . $record['id'] . '"/><span></span></label>';

                    $tempRecord = [];
                    $tempRecord['itemSelector'] = $itemSelector;
                    $tempRecord['apiChannel'] = $availableApiChannels[$record['channel']]['name'];
                    $tempRecord['regionId'] = $record['region_id'];
                    $tempRecord['regionName'] = $record['name'];
                    $tempRecord['countryId'] = $record['country_id'];
                    $tempRecord['kerabiyaAccess'] = ($record['kerabiya_access'] == SalesRegion::KERABIYA_ACCESS_ENABLED) ? 'Yes' : 'No';

                    $filteredEmiratesData[] = $tempRecord;

                }

            }

            $returnData = [
                'draw' => $dtDraw,
                'recordsTotal' => $totalRec,
                'recordsFiltered' => $totalRec,
                'data' => $filteredEmiratesData
            ];
            return response()->json($returnData, 200);


        } elseif ($methodAction == 'server_sync') {

            $emiratesSync = $serviceHelper->getAvailableRegionsList('', '', '', true);

            return response()->json([ 'message' => 'The Sales Regions updated successfully!' ], 200);

        } elseif ($methodAction == 'kerabiya_enable') {

            $updatableIds = [];
            $filteredEmirates = $serviceHelper->getAvailableRegionsList('', '', '', false, true);
            if (is_array($filteredEmirates) && (count($filteredEmirates) > 0)) {
                foreach ($filteredEmirates as $record) {
                    if ((count($itemSelectedFilter) == 0) || in_array($record['id'], $itemSelectedFilter)) {
                        $updatableIds[] = $record['id'];
                    }
                }
            }

            SalesRegion::whereIn('id', $updatableIds)->update([ 'kerabiya_access' => SalesRegion::KERABIYA_ACCESS_ENABLED ]);

            return response()->json([ 'message' => 'The Sales Regions Kerabiya Enable Status updated successfully!' ], 200);

        } elseif ($methodAction == 'kerabiya_disable') {

            $updatableIds = [];
            $filteredEmirates = $serviceHelper->getAvailableRegionsList('', '', '', false, true);
            if (is_array($filteredEmirates) && (count($filteredEmirates) > 0)) {
                foreach ($filteredEmirates as $record) {
                    if ((count($itemSelectedFilter) == 0) || in_array($record['id'], $itemSelectedFilter)) {
                        $updatableIds[] = $record['id'];
                    }
                }
            }

            SalesRegion::whereIn('id', $updatableIds)->update([ 'kerabiya_access' => SalesRegion::KERABIYA_ACCESS_DISABLED ]);

            return response()->json([ 'message' => 'The Sales Regions Kerabiya Disable Status updated successfully!' ], 200);

        }

    }

}
