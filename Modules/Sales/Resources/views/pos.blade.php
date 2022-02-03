@extends('base::layouts.mt-main')

@section('page-title') <?= $pageTitle; ?> @endsection
@section('page-sub-title') <?= $pageSubTitle; ?> @endsection

@section('content')

    <div class="row justify-content-md-center">
        <div class="col-md-9">

            <form class="form card card-custom barqut" method="POST" id="barcode-form" action="{{ url('/sales/pos/add-cart') }}">
                @csrf

                <div class="row">

                    <div class="col-md-2 text-center">
                        <img src="{{ asset('ktmt/media/svg/icons/Shopping/Barcode-read.svg') }}" alt="" class="img-fluid mt-7 w-50px">
                    </div>

                    <div class="col-md-10">
                        <div class="">

                            <div class="card-body">

                                <div class="form-group mb-0">

                                    <label>Barcode:</label>

                                    <div class="row">

                                        <div class="col-md-4">
                                            <input type="text" class="form-control" placeholder="Scan Barcode" required="" name="barcode" id="barcode">
                                        </div>

                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary mr-2" id="cart-btn">
                                                <i class="flaticon-shopping-basket icon-nm"></i> Add to Cart
                                            </button>
                                        </div>

                                        <div class="col-5 col-form-label">
                                            <!--<div class="col-5">-->
                                            <div class="radio-inline">
                                                <!--<button type="submit" class="btn btn-primary mr-2" >Grocery</button>
                                                <button type="reset" class="btn btn-secondary">Fresh</button>-->

                                                <label class="radio radio-success">
                                                    <input type="radio" name="product_type" checked="checked" value="grocery">
                                                    <span></span>Grocery
                                                </label>
                                                <label class="radio radio-success">
                                                    <input type="radio" name="product_type" value="fresh">
                                                    <span></span>Fresh
                                                </label>

                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>

                </div>

            </form>

        </div>
    </div>

    <div class="row mt-10">

        <hr>

        <div class="col-md-8">

            <div class="card card-custom pt-0">

                <div class="card-header mb-5">
                    <h3 class="card-title">
                        Cart Item
                    </h3>
                </div>

                <a class="text-right mb-2" href="javascript:void(0);" id="clear-cart-btn" tabindex="-1">Clear Cart</a>

                <div class="table-responsive" id="cart-item">

                    <?php
                        $total = 0;
                        if(session()->has('cart') && !empty(session()->get('cart'))){
                            $index = 0;
                            if(!session()->has('qty_array')){
                                session()->put('qty_array', array_fill(0, count(session()->get('cart')), 1));
                            }
                    ?>

                    <table class="table table-bordered table-checkable cart-table">

                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price(AED)</th>
                                <th>Quantity</th>
                                <th class="text-right">Row Total(AED)</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php
                            foreach(session()->get('cart') as $product){
                            //echo $product;
                            $productData = $serviceHelper->getProductData($product);

                            if (is_array($productData) && (count($productData) > 0) && array_key_exists('items', $productData)) {

                                $sessionQtyArray = session()->get('qty_array');
                                if(empty($sessionQtyArray[$index])) {
                                    $sessionQtyArray[$index] = 1;
                                    session()->put('qty_array', $sessionQtyArray);
                                }

                                foreach($productData['items'] as $item) {

                                    $saleFormat = $item['extension_attributes']['selling_format_label'];
                                    $currentQty = session()->get('qty_array')[$index];

                        ?>

                            <tr>

                                <td>
                                    <span class="font-weight-bold ">{{ $item['name'] }}</span><br>
                                    <strong>{{ $item['sku'] }}</strong>
                                    <p>
                                        <small>
                                            <a href="#" class="text-danger item-remove-btn" data-product-id="{{ $product }}" >
                                                <i class="flaticon2-trash text-danger icon-nm"></i> Remove
                                            </a>
                                        </small>
                                    </p>
                                </td>

                                <?php

                                    $spprice = 0;
                                    foreach($item['custom_attributes'] as $attribute) {
                                        if($attribute['attribute_code'] == 'special_price') {
                                            $spprice = 1;
                                            $price = $attribute['value'];
                                ?>
                                <td>{{ number_format($attribute['value'], 2) }} <small>per {{ $saleFormat }}</small></td>
                                <?php
                                        }
                                    }

                                    if ($spprice == 0) {
                                        $price = $item['price'];
                                ?>
                                <td>{{ number_format($item['price'], 2) }} <small>per {{ $saleFormat }}</small></td>
                                <?php
                                    }
                                ?>

                                <td id="quan_td_{{ $index }}">

                                    <div class="input-group-sm input-group">

                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <a href="#" class="product-remove-btn" data-product-id="{{ $product }}" data-row-index="{{ $index }}" >-</a>
                                            </span>
                                        </div>

                                        <input type="text" readonly value="{{ $currentQty . ' ' . $saleFormat }}" class="form-control col-md-4" id="input-quantity-{{ $item["id"] }}">

                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <a href="#" class="product-add-btn" data-product-id="{{ $product }}" data-row-index="{{ $index }}" >+</a>
                                            </span>
                                        </div>

                                    </div>

                                    <small>Actual Qty.: {{ session()->get('actual_qty')[$index] . ' ' . $saleFormat }} </small>

                                </td>

                                <td class="text-right">{{ number_format($currentQty * $price, 2) }}</td>
                                <?php $total += $currentQty * $price; ?>

                            </tr>

                        <?php
                                }
                                $index++;

                            }
                        ?>
                            <input type="hidden" name="subtotal" id="subtotal" value="<?php echo $total?>">
                    <?php } ?>
                        </tbody>

                    </table>
                <?php } ?>

                </div>

            </div>

        </div>

        <div class="col-md-4 ord-info">

            <form method="POST" id="order-form" action="{{ url('/sales/pos/create-order') }}">
                @csrf

                <div class="card card-custom pb-5">

                    <div class="card-header">
                        <h3 class="card-title">
                            Order Information
                        </h3>
                    </div>

                    <div class="row px-0">

                        <div class="form-group mb-0">

                        </div>

                        <fieldset class="col-md-12">

                            <div class="form-group">
                                <h4>Source Info</h4>
                            </div>

                            <div class="row">

                                <div class="form-group col-md-12">
                                    <label class="col-12 col-form-label">Order Source</label>
                                    <div class="col-12">
                                        <select class="form-control" name="channel_id" id="channel-Id" required="" >
                                            <option value="">Select Source</option>
                                            @foreach($orderSources as $key => $arr)
                                                <option value="{{ $arr['code'] }}">{{ $arr['source'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div id="order_source_id_div" class="form-group col-md-12">
                                    <label class="col-12 col-form-label">Source Order ID</label>
                                    <div class="col-12">
                                        <input class="form-control" type="text" value="" name="source_order_id" id="source_order_id" >
                                    </div>
                                </div>

                            </div>

                        </fieldset>

                        <!--<div class="form-group col-md-6">
                            <label class="col-12 col-form-label">Discount</label>
                            <div class="col-12">
                                <div class="input-group-sm input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">AED</span>
                                </div>
                                <input type="text" value="" class="form-control" name="discount">
                                </div>
                            </div>
                        </div>-->

                        <fieldset class="col-md-12 customer_info">

                            <div class="form-group">
                                <h4>Customer Info</h4>
                            </div>

                            <div class="row">

                                <div class="form-group col-md-12">
                                    <label class="col-12 col-form-label">Firstname</label>
                                    <div class="col-12">
                                        <input class="form-control" type="text" value="" name="firstname" id="firstname" required="">
                                    </div>
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="example-search-input" class="col-12 col-form-label">Lastname</label>
                                    <div class="col-12">
                                        <input class="form-control" type="text" value="" id="lastname" name="lastname" required="">
                                    </div>
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="example-email-input" class="col-12 col-form-label">Email</label>
                                    <div class="col-12">
                                        <input class="form-control" type="email" value="" id="email" name="email" required="">
                                    </div>
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="example-tel-input" class="col-12 col-form-label">Contact No.</label>
                                    <div class="col-12">
                                        <input class="form-control" type="tel" value="" id="telephone" name="telephone" required="">
                                    </div>
                                </div>

                            </div>

                        </fieldset>

                        <fieldset class="col-md-12">

                            <div class="form-group">
                                <h4>Delivery Info</h4>
                            </div>

                            <div class="row">

                                <div class="form-group col-md-12">
                                    <label for="example-number-input" class="col-12 col-form-label">Emirates</label>
                                    <div class="col-12">
                                        <select class="form-control" name="region" id="region" required="">
                                            <option value="">Select Region</option>
                                            @foreach($regionsList['available_regions'] as $area)
                                                <option value="{{ $area['id'] }}">{{ $area['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="example-number-input" class="col-12 col-form-label">Area</label>
                                    <div class="col-12">
                                        <?php $checkArea = $areaList[$regionsList['available_regions'][0]['id']]; ?>
                                        <select class="form-control" name="city" id="city" required="">
                                            <option value="">Select Area</option>
                                            @foreach($checkArea as $loc)
                                                <option value="{{ $loc['area_code'] }}">{{ $loc['area_name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="example-search-input" class="col-12 col-form-label">Address</label>
                                    <div class="col-12">
                                        <input class="form-control" type="text" value="" id="street" name="street" required="">
                                    </div>
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="example-date-input" class="col-12 col-form-label">Delivery Date</label>
                                    <div class="col-12">
                                        <input class="form-control" type="date" value="{{ $todayDate }}" id="delivery_date" name="delivery_date" required="">
                                    </div>
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="example-date-input" class="col-12 col-form-label">Delivery Time</label>
                                    <div class="col-12">
                                        <select class="form-control" id="delivery_time_slot" name="delivery_time_slot" required="">
                                            <option value="">Select Slot</option>
                                            @foreach($deliveryTimeSlots as $slot)
                                                <option value="{{ $slot }}">{{ $slot }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>

                        </fieldset>

                        <fieldset class="col-md-12">
                            <div class="form-group">
                                <h4>Payment Info</h4>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="example-number-input" class="col-12 col-form-label">Payment Method</label>
                                    <div class="col-12">
                                        <select class="form-control" name="paymentMethod"
                                                id="paymentMethod" required="">
                                            <option value="">Select Method</option>
                                            @foreach($paymentMethods as $method)
                                                <option value="{{ $method['method'] }}">{{ $method['title'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="col-12 col-form-label">Discount</label>
                                    <div class="col-12">
                                        <div class="input-group-sm input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">AED</span>
                                            </div>
                                            <input type="text" value="" class="form-control" name="discount" id="discount" onkeyup="calculateTotal()">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="col-12 col-form-label">Number of Boxes</label>
                                    <div class="col-12">
                                        <div class="input-group-sm input-group">
                                            <input type="text" value="1" class="form-control" name="number_of_box" id="number_of_box" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                    </div>


                </div>

        </div>

    </div>

@endsection

@section('content-before-footer')

    <div class="total_price">

        <input type="hidden" id="service_charge" name="service_charge" value="0.00">

        <span>
            Sub Total :  <span id="subtotal-span" class="text-success">AED<?php echo number_format($total, 2)?></span>
            Discount :  <span id="discount-span" class="text-warning">AED0.00</span>
            Service Charge :  <span id="sc-span" class="text-danger">AED0.00</span>
            Grand Total: <span id="total-span"><strong>AED<?php echo number_format($total, 2)?></strong></span>
        </span>

        <div class="btn-grp">
            <button type="submit" class="btn btn-primary mr-2" id="create_btn" <?php if($total <= 0){?> disabled <?php } ?>>Create Order</button>
            <button type="reset" class="btn btn-secondary">Cancel</button>
        </div>

        </form>

    </div>

@endsection

@section('custom-js-section')

    <script src="{{ asset('js/sales.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            SalesCustomJsBlocks.posPage('{{ url('/') }}', <?php echo json_encode($areaList) ?>, <?php echo json_encode($orderSources) ?>);
        });
    </script>

@endsection
