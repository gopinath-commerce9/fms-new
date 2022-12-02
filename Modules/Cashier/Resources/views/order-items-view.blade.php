<div class="row justify-content-center py-8 px-8 py-md-27 px-md-0">

    <form action="{{ url('/cashier/prepare-order-status-change/' . $saleOrderData['id']) }}" class="mw-100 w-100" method="POST" id="order_view_status_change_form">
        @csrf

        <input type="hidden" class="box_qty" name="box_qty" id="box_qty_1" value="1">

        <div class="col-md-12">

            <div class="row">
                <div class="col-md-12">

                    <div class="table-responsive">
                        <div class="border-bottom w-100"></div>
                        <div>
                            <table class="table text-center" id="item-list-table">
                                <thead>
                                    <tr>
                                        <th class="pl-0 font-weight-bold text-muted text-uppercase">Store Availability</th>
                                        <th class="pl-0 font-weight-bold text-muted text-uppercase">Item</th>
                                        <th class="pl-0 font-weight-bold text-muted text-uppercase">Sku</th>
                                        <th class="pl-0 font-weight-bold text-muted text-uppercase">Country</th>
                                        <th class="pl-0 font-weight-bold text-muted text-uppercase">Qty</th>
                                        <th class="pl-0 font-weight-bold text-muted text-uppercase">Price</th>
                                        <th class="pl-0 font-weight-bold text-muted text-uppercase">Totals</th>
                                        <th class="pl-0 font-weight-bold text-muted text-uppercase">BarCode(s)</th>
                                        <th class="pl-0 font-weight-bold text-muted text-uppercase">Qty Scanned</th>
                                        <th class="pl-0 font-weight-bold text-muted text-uppercase">Amount Scanned</th>
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

                                $storeAvailabilityValue = '';
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

                                    <td class="border-top-0 pl-0 py-4"><?php echo $item['item_sku']?></td>
                                    <td class="border-top-0 pl-0 py-4"><?php echo $countryLabel;?></td>

                                    <td class="border-top-0 pl-0 py-4"><?php echo $item['qty_ordered'] . " " . $sellingFormat; ?></td>
                                    <td class="border-top-0 text-right py-4"><?php echo $saleOrderData['order_currency'] . " " . $item['price']; ?></td>
                                    <td class="text-danger border-top-0 pr-0 py-4 text-right"><?php echo $saleOrderData['order_currency'] . " " . $row_subtotal; ?></td>

                                    <td class="border-top-0 pl-0 py-4"><?php echo isset($item['scan_barcode']) ? $item['scan_barcode'] : ''; ?></td>
                                    <td class="border-top-0 pl-0 py-4"><?php echo isset($item['qty_delivered']) ? $item['qty_delivered'] . " " . $sellingFormat : ''; ?></td>
                                    <td class="text-danger border-top-0 pl-0 py-4 text-right"><?php echo isset($item['row_total_delivered']) ? $saleOrderData['order_currency'] . " " . $item['row_total_delivered'] : ''; ?></td>

                                </tr>
                                <?php $i++;} ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border-bottom w-100 my-13 opacity-15"></div>

                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-center">

                    <input type="submit" name="cashier_order_scan_submit_btn" id="cashier_order_scan_submit_btn" class="btn btn-primary font-weight-bold" value="Ready To Dispatch">

                </div>
            </div>


        </div>

    </form>

</div>
