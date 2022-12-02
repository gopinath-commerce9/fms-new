<div class="row justify-content-center py-8 px-8 py-md-27 px-md-0">

    <div class="col-md-11">

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
                <span class="font-weight-bolder mb-2">Shipping Information</span>
                <span class="opacity-70"><?php echo $saleOrderData['shipping_address']['first_name'];?> <?php echo $saleOrderData['shipping_address']['last_name']; ?></span>
                <?php if(isset($saleOrderData['shipping_address']['company'])){ ?>
                <span class="opacity-70"><?php echo $saleOrderData['shipping_address']['company'];?></span>
                <?php } ?>
                <span class="opacity-70">
                    <?php echo $saleOrderData['shipping_address']['address_1']; ?>
                <?php echo ($saleOrderData['shipping_address']['address_2'] != null) ?  ', ' . $saleOrderData['shipping_address']['address_2'] : ''; ?>
                <?php echo ($saleOrderData['shipping_address']['address_3'] != null) ?  ', ' . $saleOrderData['shipping_address']['address_3'] : ''; ?>
                </span>
                <span class="opacity-70"><?php echo $saleOrderData['shipping_address']['city'];?>,
                <?php if(isset($saleOrderData['shipping_address']['region'])) { ?>
                      <?php echo $saleOrderData['shipping_address']['region'].', '; ?>
                <?php } ?>
                <?php echo $saleOrderData['shipping_address']['post_code']; ?>
                </span>
                <span class="opacity-70"><?php echo $saleOrderData['shipping_address']['contact_number']; ?></span>
            </div>

            <div class="d-flex flex-column flex-root">
                <span class="font-weight-bolder mb-2">Delivery Info.</span>
                <span class="opacity-70">
                    Delivery Date :<?php if(isset($saleOrderData['delivery_date'])){ echo date('d-m-Y', strtotime($saleOrderData['delivery_date'])); } ?><br>
                    Delivery Time Slot :<?php if(isset($saleOrderData['delivery_time_slot'])){ echo $saleOrderData['delivery_time_slot']; }?>
                </span>
            </div>

            <div class="d-flex flex-column flex-root">
                <span class="font-weight-bolder mb-2">Delivery Notes</span>
                <span class="opacity-70"><?php if(isset($saleOrderData['delivery_notes'])){ echo $saleOrderData['delivery_notes']; } ?></span>
            </div>

        </div>

        <div class="border-bottom w-100"></div>

    </div>

</div>
