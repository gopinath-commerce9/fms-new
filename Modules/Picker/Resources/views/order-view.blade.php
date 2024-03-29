@extends('base::layouts.mt-main')

@section('page-title') <?= $pageTitle; ?> @endsection
@section('page-sub-title') <?= $pageSubTitle; ?> @endsection

@section('content')

    <div class="row">
        <div class="col-md-12">

            <div class="card card-custom overflow-hidden">

                <div class="card-header flex-wrap py-3">
                    <div class="card-toolbar">
                        <div class="col text-left">
                            <a href="{{ url()->previous() }}" class="btn btn-outline-primary">
                                <i class="flaticon2-back"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <form action="{{ url('/picker/order-status-change/' . $saleOrderData['id']) }}" method="POST" id="order_view_status_change_form">
                    @csrf

                    <div class="card-body p-0">

                        <!-- begin: Invoice-->

                        <!-- begin: Invoice header-->
                        <div class="row justify-content-center py-8 px-8 py-md-27 px-md-0">
                            <div class="col-md-11">
                                <div class="d-flex justify-content-between pb-10 pb-md-20 flex-column flex-md-row">
                                    <h1 class="display-2 font-weight-boldest mb-10"><?php echo ($saleOrderData['zone_id']) ?? ''; ?></h1>
                                    <div class="d-flex flex-column align-items-md-end px-0">

                                        <span class="d-flex flex-column align-items-md-end opacity-70">
                                            <span class="font-weight-bolder mb-2">Shipping Information</span>
                                            <span><?php echo $saleOrderData['shipping_address']['first_name'];?> <?php echo $saleOrderData['shipping_address']['last_name']; ?></span>
                                             <?php if(isset($saleOrderData['shipping_address']['company'])){ ?>
                                            <span><?php echo $saleOrderData['shipping_address']['company'];?></span>
                                            <?php } ?>
                                            <span>
                                                <?php echo $saleOrderData['shipping_address']['address_1']; ?>
                                                <?php echo ($saleOrderData['shipping_address']['address_2'] != null) ?  ', ' . $saleOrderData['shipping_address']['address_2'] : ''; ?>
                                                <?php echo ($saleOrderData['shipping_address']['address_3'] != null) ?  ', ' . $saleOrderData['shipping_address']['address_3'] : ''; ?>
                                            </span>
                                            <span><?php echo $saleOrderData['shipping_address']['city'];?>,
                                            <?php if(isset($saleOrderData['shipping_address']['region'])) { ?>
                                                  <?php echo $saleOrderData['shipping_address']['region'].', '; ?>
                                            <?php } ?>
                                                <?php echo $saleOrderData['shipping_address']['post_code']; ?>
                                            </span>
                                            <span><?php echo $saleOrderData['shipping_address']['contact_number']; ?></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="border-bottom w-100"></div>
                                <div class="d-flex justify-content-between pt-4 pb-4">
                                    <div class="d-flex flex-column flex-root">
                                        <span class="font-weight-bolder mb-2">Order Date</span>
                                        <span class="opacity-70">
                                            <?php echo $serviceHelper->getFormattedTime($saleOrderData['order_created_at'], 'F d, Y, h:i:s A'); ?>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column flex-root">
                                        <span class="font-weight-bolder mb-2">Order ID</span>
                                        <span class="opacity-70"># <?php echo $saleOrderData['increment_id'];?></span>
                                    </div>
                                    <div class="d-flex flex-column flex-root">
                                        <span class="font-weight-bolder mb-2">Payment Method</span>
                                        <span class="opacity-70">
                                            <?php
                                            if(isset($saleOrderData['payment_data'][0]['cc_last4']) && !empty($saleOrderData['payment_data'][0]['cc_last4'])){ ?>
                                                Credit Card ending **** <?php echo $saleOrderData['payment_data'][0]['cc_last4']; ?><br>
                                            <?php } ?>
                                            <?php
                                            $paymentMethodTitle = '';
                                            $payInfoLoopTargetLabel = 'method_title';
                                            if (isset($saleOrderData['payment_data'][0]['extra_info'])) {
                                                $paymentAddInfo = json5_decode($saleOrderData['payment_data'][0]['extra_info'], true);
                                                if (is_array($paymentAddInfo) && (count($paymentAddInfo) > 0)) {
                                                    foreach ($paymentAddInfo as $paymentInfoEl) {
                                                        if ($paymentInfoEl['key'] == $payInfoLoopTargetLabel) {
                                                            $paymentMethodTitle = $paymentInfoEl['value'];
                                                        }
                                                    }
                                                }
                                            }
                                            ?>
                                            <?= (trim($paymentMethodTitle) != '') ? $paymentMethodTitle : 'Online' ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="border-bottom w-100"></div>
                                <div class="d-flex justify-content-between pt-4 pb-4">
                                    <div class="d-flex flex-column flex-root">
                                        <span class="font-weight-bolder mb-2">Order Status</span>
                                        <span class="opacity-70">
                                            <?php
                                            $status = $saleOrderData['order_status'];
                                            if(array_key_exists($status, $orderStatuses)) {
                                                $status = $orderStatuses[$status];
                                            }
                                            ?>
                                            <?php echo $status;?>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column flex-root">
                                        <span class="font-weight-bolder mb-2">Delivery Info.</span>
                                        <span class="opacity-70">
                                            Delivery Date :<?php if(isset($saleOrderData['delivery_date'])){ echo date('d-m-Y', strtotime($saleOrderData['delivery_date'])); } ?><br>
                                            Delivery Time Slot :<?php if(isset($saleOrderData['delivery_time_slot'])){ echo $saleOrderData['delivery_time_slot']; }?>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column flex-root">
                                        <span class="font-weight-bolder mb-2">Order Comment</span>
                                        <span class="opacity-70"><?php if(isset($saleOrderData['customer_order_comment'])){ echo $saleOrderData['customer_order_comment']; } ?></span>
                                    </div>
                                </div>
                                <div class="border-bottom w-100"></div>
                                <div class="d-flex justify-content-between pt-4 pb-4">
                                    <div class="d-flex flex-column flex-root">
                                        <span class="font-weight-bolder mb-2">Order Info.</span>
                                        <span class="opacity-70">
                                            <?php $orderCustomerGroup = $saleOrderData['sale_customer']['customer_group_id']; ?>
                                            <?php echo (array_key_exists($orderCustomerGroup, $customerGroups) ? $customerGroups[$orderCustomerGroup] : '') ?>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column flex-root">
                                        <span class="font-weight-bolder mb-2">Vendor Status.</span>
                                        <span class="opacity-70 vendor_status" >

                                         </span>
                                    </div>
                                    <div class="d-flex flex-column flex-root">
                                        <span class="font-weight-bolder mb-2">Order Status Histories</span>
                                        <span class="opacity-70"><?php
                                            foreach ($saleOrderData['status_history'] as $orderhistory) {
                                                echo "<b>Comment :</b>";
                                                echo $orderhistory['comments'];
                                                echo "<br/>";
                                                echo "<b>Date :</b>";
                                                echo $serviceHelper->getFormattedTime($orderhistory['status_created_at'], 'F d, Y, h:i:s A');
                                                echo "<br/>";
                                            }
                                            ?>
                                        </span>
                                    </div>

                                    <div class="d-flex flex-column flex-root">
                                        <span class="font-weight-bolder mb-2">Order Process Histories</span>
                                        <span class="opacity-70"><?php
                                            $processHistoryIndex = 1;
                                            foreach ($saleOrderData['process_history'] as $processHistory) {
                                                $actionDoer = 'AutoSync';
                                                if (isset($processHistory['action_doer']) && isset($processHistory['action_doer']['name'])) {
                                                    $actionDoer = trim($processHistory['action_doer']['name']);
                                                }
                                                echo $processHistoryIndex++ . ".) ";
                                                echo "<b>" . ucwords(str_replace('_', ' ', trim($processHistory['action']))) . "</b>";
                                                echo " By ";
                                                echo "<b>" . $actionDoer . "</b>";
                                                echo " on ";
                                                echo "<b>" . $serviceHelper->getFormattedTime($processHistory['done_at'], 'F d, Y, h:i:s A') . "</b>";
                                                echo "<br/>";
                                            }
                                            ?>
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- end: Invoice header-->

                        <div class="row justify-content-center py-8 px-8 py-md-10 px-md-0">
                            <div class="col-md-11">
                                <div class="d-flex justify-content-between">
                                    <?php if($saleOrderData['order_status'] === \Modules\Sales\Entities\SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED) {?>

                                    <div class="row">
                                        <div class="col col-12">
                                            <table class="table table-borderless">

                                                {{--<tr>

                                                    <td>
                                                        <input type="button" name="picker_btn_scan" id="picker_btnscan" class="btn btn-primary font-weight-bold" value="Scan Product">
                                                    </td>

                                                    <td>
                                                        <input type="button" name="picker_btn_enable_entry" id="picker_btn_enable_entry" class="btn btn-primary font-weight-bold" value="Enable Manual Entry in Actual Qty">
                                                        <input type="button" name="picker_btn_disable_entry" id="picker_btn_disable_entry" class="btn btn-primary font-weight-bold" value="Disable Manual Entry in Actual Qty">
                                                    </td>

                                                </tr>--}}

                                                <tr>

                                                    <td>
                                                        Number of Boxes : <input type="text"  min="1"  class="box_qty"  style="text-align:center" size="5" name="box_qty" id="box_qty_1"  required="" value="{{ old('box_qty') }}">
                                                    </td>

                                                    <td>
                                                        <input type="submit" name="picker_btn_submit" id="picker_btn_submit" class="btn btn-primary font-weight-bold" value="Ready To Dispatch">
                                                    </td>

                                                </tr>

                                            </table>
                                        </div>
                                    </div>

                                    <?php  } ?>

                                </div>
                            </div>
                        </div>

                        <!-- begin: Invoice body-->

                        <div class="row justify-content-center py-8 px-8 py-md-27 px-md-0">
                            <div class="col-md-11">
                                <div class="table-responsive">
                                    <div class="border-bottom w-100"></div>
                                    <div>
                                        <table class="table text-center" id="item-list-table">
                                            <thead>
                                                <tr>
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Store Availability</th>
                                                    {{--<th class="pl-0 font-weight-bold text-muted text-uppercase">Actual Quantity</th>--}}
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Quantity</th>
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Item</th>
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Country</th>
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Sku</th>
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Shelf Number</th>
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Scale Number</th>
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Price</th>
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Totals</th>
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Vendor</th>
                                                    <th class="pl-0 font-weight-bold text-muted text-uppercase">Vendor Availability</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <!-- foreach ($order->lineItems as $line) or some such thing here -->
                                            <?php
                                            $i = 0;

                                            foreach ($saleOrderData['order_items'] as $item) {

                                                $itemInputId = $item['item_sku'];
                                                // echo "<pre>";
                                                //print_r($item);

                                                $qtyOrdered = (float)$item['qty_ordered'];
                                                $qtyCanceled = (float)$item['qty_canceled'];
                                                $qtyNeeded = $qtyOrdered - $qtyCanceled;
                                                $epsilon = 0.00001;

                                                $row_subtotal = $item['row_grand_total'];
                                                $actualQty = (!empty($qtyNeeded) && ($qtyNeeded > $epsilon)) ? $qtyNeeded : "";
                                                $sellingFormat = (!empty($item['selling_unit'])) ? $item['selling_unit'] : "";

                                                if(!empty($item['item_barcode'])){
                                                    $barcode = $item['item_barcode'];
                                                    if(substr($barcode,7)!=000000) {
                                                        $itemInputId = $barcode;
                                                    } else {
                                                        $barcode = "";
                                                    }
                                                } else {
                                                    $barCode = "";
                                                }

                                                $weightInfo = (!empty($item['item_info'])) ? $item['item_info'] : "";
                                                $countryLabel = (!empty($item['country_label'])) ? $item['country_label'] : "";
                                                $productName = (!empty($item['item_name'])) ? $item['item_name'] : "";

                                                $storeAvailabilityValue = 1;
                                                /*if ($item['store_availability'] === \Modules\Sales\Entities\SaleOrderItem::STORE_AVAILABLE_NOT_CHECKED) {
                                                    $storeAvailabilityValue = '';
                                                } else*/if ($item['store_availability'] === \Modules\Sales\Entities\SaleOrderItem::STORE_AVAILABLE_YES) {
                                                    $storeAvailabilityValue = $item['store_availability'];
                                                } elseif ($item['store_availability'] === \Modules\Sales\Entities\SaleOrderItem::STORE_AVAILABLE_NO) {
                                                    $storeAvailabilityValue = $item['store_availability'];
                                                }

                                            ?>
                                            <tr>
                                                <td>
                                                    @if ($qtyNeeded > $epsilon)
                                                        <select class="form-control store-availability" name="store_availability[{{ $item['id'] }}]" id="store_availability_{{ $item['id'] }}">
                                                            <option value="" {{ ($storeAvailabilityValue === '') ? " selected " : '' }}>Not Checked</option>
                                                            <option value="1" {{ ($storeAvailabilityValue === 1) ? " selected " : '' }}>Yes</option>
                                                            <option value="0" {{ ($storeAvailabilityValue === 0) ? " selected " : '' }}>No</option>
                                                        </select>
                                                    @else
                                                        <span class="label label-lg font-weight-bold label-light-danger label-inline">
                                                                Canceled
                                                            </span>
                                                    @endif
                                                </td>

                                                {{--<td>

                                                    <input type="text" class="actual_qty"  name="actual_qty[<?php echo $itemInputId;?>]" id="actual_qty_<?php echo $itemInputId;?>"  value="<?php echo $actualQty; ?>" style="width: 80px;">
                                                    <input type="hidden"  class="actual_qty_tmp" name="tmpactual_qty[<?php echo $itemInputId;?>]" id="tmpactual_qty_<?php echo $itemInputId;?>" value="<?php echo $actualQty; ?>"  style="width: 80px;">

                                                    <input type="hidden" class="ordered_qty" name="ordered_qty[<?php echo $itemInputId;?>]" id="ordered_qty_<?php echo $itemInputId;?>" value="<?php echo $item['qty_ordered']; ?>" >
                                                    <input type="hidden" class="selling_format" name="selling_format[<?php echo $itemInputId;?>]" id="selling_format_<?php echo $itemInputId;?>" value="<?php echo $sellingFormat; ?>"  >
                                                    <?php echo $sellingFormat;?>


                                                    <span id="tick_mark_<?php echo $item['item_sku'];?>"  style="font-size: 20px; font-weight: bold; color: green"></span>

                                                    <span>
                                                        <a href="javascript:;" onclick="clearValue('<?php echo $itemInputId;?>')">Clear </a>
                                                    </span>

                                                </td>--}}

                                                <td class="border-top-0 pl-0 py-4"><?php echo $item['qty_ordered']." ".$sellingFormat;?></td>
                                                <td class="border-top-0 pl-0 py-4"><?php echo $productName;?> <br> <b>Pack & Weight Info :</b> <?php echo $weightInfo;?>

                                                    <br>

                                                    <?php if(!empty($item['gift_message'])) { ?>
                                                    <p><b>Gift Message</b><br>
                                                        From : <?= $item['gift_message']['sender'] ? $item['gift_message']['sender'] : '';?><br>
                                                        To : <?= $item['gift_message']['recipient'] ? $item['gift_message']['recipient'] : '';?> <br>
                                                        Message : <?= $item['gift_message']['message'] ? $item['gift_message']['message'] : '';?> <br>
                                                    </p>
                                                    <?php } ?>

                                                </td>
                                                <td class="border-top-0 pl-0 py-4"><?php echo $countryLabel;?></td>
                                                <td class="border-top-0 pl-0 py-4"><?php echo $item['item_sku']?></td>
                                                <td class="border-top-0 pl-0 py-4"><?php echo isset($item['shelf_number']) ? $item['shelf_number'] : '';?></td>
                                                <td class="border-top-0 pl-0 py-4"><?php echo $item['scale_number'] ? $item['scale_number'] : '';?></td>
                                                <td class="border-top-0 text-right py-4"><?php echo $saleOrderData['order_currency'] . " " . $item['price'];?></td>

                                                <!--  <td class="text-center"><?php echo $item['discount_amount']?></td>
                                                <td class="text-center"><?php echo $item['tax_amount']?></td> -->
                                                <td class="text-danger border-top-0 pr-0 py-4 text-right"><?php echo $saleOrderData['order_currency'] . " " . $row_subtotal;?></td>
                                                <td class="border-top-0 text-center py-4"><?php
                                                    if(!empty($item['vendor_id'])) {
                                                        echo $vendorList[$item['vendor_id']] ? $vendors[$item['vendor_id']] : '';

                                                    }
                                                    ?>
                                                </td>

                                                <td class="border-top-0 text-center py-4" id="availability_<?php echo $i?>"><?php if($item['vendor_availability']==1){ ?><i class="la la-check text-success mr-5 icon-xl"></i> <?php } ?>
                                                    <?php if($item['vendor_availability']==2){ ?><i class="la la-remove text-danger mr-5 icon-xl"></i> <?php } ?>
                                                </td>
                                            </tr>
                                            <?php $i++;} ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="border-bottom w-100 my-13 opacity-15"></div>
                                <!--begin::Invoice total-->


                                <div class="table-responsive">
                                    <table class="table text-md-right font-weight-boldest">
                                        <tbody>
                                            <tr>
                                                <td class="align-middle title-color font-size-lg border-0 pt-0 pl-0 w-50">SUBTOTAL</td>
                                                <td class="align-middle font-size-h3 border-0 pt-0"><?php echo $saleOrderData['order_currency'] . " " . $saleOrderData['order_subtotal'];?></td>
                                            </tr>
                                            <tr>
                                                <td class="align-middle title-color font-size-h4 border-0 py-7 pl-0 w-50">Shipping (<?php echo $saleOrderData['shipping_method'];?>)</td>
                                                <td class="align-middle font-size-h3 border-0 py-7"><?php echo $saleOrderData['order_currency'] . " " . $saleOrderData['shipping_total'];?></td>
                                            </tr>
                                            <?php if( !empty($saleOrderData['discount_amount']) ) {?>
                                            <tr>

                                                <td class="align-middle title-color font-size-h4 border-0 py-7 pl-0 w-50">Discount (<?php if(isset($saleOrderData['coupon_code']) && !empty($saleOrderData['coupon_code'])) { echo $saleOrderData['coupon_code']; } ?>)</td>
                                                <td class="no-line text-align-middle font-size-h3 border-0 py-7"><?php echo $saleOrderData['order_currency'] . " " . $saleOrderData['discount_amount'];?></td>

                                            </tr>
                                            <?php } ?>
                                            <?php if(!is_null($saleOrderData['eco_friendly_packing_fee']) ) {?>
                                            <tr>
                                                <td class="align-middle title-color font-size-h4 border-0 py-7 pl-0 w-50">Eco-Friendly Packing</td>
                                                <td class="no-line text-align-middle font-size-h3 border-0 py-7"><?php echo $saleOrderData['order_currency'] . " " . $saleOrderData['eco_friendly_packing_fee'];?></td>
                                            </tr>
                                            <?php } ?>
                                            <tr>
                                                <td class="align-middle title-color font-size-h4 border-0 pl-0 w-50">GRAND TOTAL</td>
                                                <td class="text-danger font-size-h3 font-weight-boldest"><?php echo $saleOrderData['order_currency'] . " " . $saleOrderData['order_total'];?></td>
                                            </tr>
                                            @if(!is_null($saleOrderData['canceled_total']))
                                                <tr>
                                                    <td class="align-middle title-color font-size-h4 border-0 pl-0 w-50">Canceled TOTAL</td>
                                                    <td class="text-danger font-size-h3 border-0 py-7"><?php echo $saleOrderData['order_currency'] . " " . $saleOrderData['canceled_total'];?></td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>


                                <!--end::Invoice total-->
                            </div>
                        </div>

                        <!-- end: Invoice body-->

                        <!-- end: Invoice -->

                    </div>

                </form>
            </div>

        </div>
    </div>


@endsection

@section('custom-js-section')

    <script src="{{ asset('js/picker.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            PickerCustomJsBlocks.orderViewPage('{{ url('/') }}', '{{ $saleOrderData['id'] }}');
        });
    </script>

@endsection
