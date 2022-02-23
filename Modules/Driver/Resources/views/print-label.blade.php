<page>

    <page_header>

    </page_header>
    <page_footer>

    </page_footer>

    <table id="main-print-label-table" style="width: 100%;">

        <colgroup>
            <col style="width: 50%">
            <col style="width: 50%">
        </colgroup>

        <tbody id="main-print-label-table-body">

            <tr id="top-head-row">

                <td id="package-count-td">
                    <div id="package-count-div" style="text-align: center;">
                        <label id="package-count-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                            Package: 1/1
                        </label>
                    </div>
                </td>

                <td id="package-barcode-td">
                    <div id="package-barcode-div" style="text-align: center;">
                        <label id="package-barcode-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                            <barcode dimension="1D" type="C93" value="<?= $orderData['increment_id']?>"
                                     label="label" style=""></barcode>
                        </label>
                    </div>
                </td>

            </tr>

            <?php if (isset($orderData['box_count']) && ((int) $orderData['box_count'] > 0)) { ?>
            <tr id="parcel-count-row">

                <td id="parcel-count-label-td" class="bordered-table-data-left" style="border: 1px solid #000000; border-right: unset;">
                    <div id="parcel-count-label-div" style="text-align: center;">
                        <label id="parcel-count-label-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                            Total Parcel Count:
                        </label>
                    </div>
                </td>

                <td id="parcel-count-value-td" class="bordered-table-data-right" style="border: 1px solid #000000; border-left: unset;">
                    <div id="parcel-count-value-div" style="text-align: center;">
                        <label id="parcel-count-value-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                            <?= $orderData['box_count'] ?>
                        </label>
                    </div>
                </td>

            </tr>
            <?php } ?>

            <tr id="order-amount-row">

                <td id="order-amount-label-td" class="bordered-table-data-left" style="border: 1px solid #000000; border-right: unset;">

                    <table style="width: 100%;">

                        <tr>
                            <td style="width: 100%;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                                        Order Total Amount:
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="width: 100%;">
                                <div id="order-due-amount-label-div" style="text-align: center;">
                                    <label id="order-due-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                                        Amount To Collect:
                                    </label>
                                </div>
                            </td>
                        </tr>

                    </table>

                </td>

                <td id="order-amount-value-td" class="bordered-table-data-right" style="border: 1px solid #000000; border-left: unset;">

                    <table style="width: 100%;">

                        <tr>
                            <td style="width: 100%;">
                                <div id="order-total-amount-value-div" style="text-align: center;">
                                    <label id="order-total-amount-value-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                                        <?= $orderData['order_total'] . " " . $orderData['order_currency'] ?>
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="width: 100%;">
                                <div id="order-due-amount-value-div" style="text-align: center;">
                                    <label id="order-due-amount-value-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                                        <?php
                                        $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
                                        $totalDueValue = $orderData['order_due'];
                                        if (in_array($orderData['payment_data'][0]['method'], $fixTotalDueArray)) {
                                            $totalDueValue = $orderData['order_total'];
                                        }
                                        ?>
                                        <?= $totalDueValue . " " . $orderData['order_currency'] ?>
                                    </label>
                                </div>
                            </td>
                        </tr>

                    </table>

                </td>

            </tr>

            <tr id="payment-method-row">

                <td id="payment-method-label-td" class="bordered-table-data-left" style="border: 1px solid #000000; border-right: unset;">
                    <div id="payment-method-label-div" style="text-align: center;">
                        <label id="payment-method-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                            Mode Of Payment:
                        </label>
                    </div>
                </td>

                <td id="payment-method-value-td" class="bordered-table-data-right" style="border: 1px solid #000000; border-left: unset;">
                    <div id="payment-method-value-div" style="text-align: center;">
                        <label id="payment-method-value-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                            <?php
                            $paymentMethodTitle = '';
                            $payInfoLoopTargetLabel = 'method_title';
                            if (isset($orderData['payment_data'][0]['extra_info'])) {
                                $paymentAddInfo = json5_decode($orderData['payment_data'][0]['extra_info'], true);
                                if (is_array($paymentAddInfo) && (count($paymentAddInfo) > 0)) {
                                    foreach ($paymentAddInfo as $paymentInfoEl) {
                                        if ($paymentInfoEl['key'] == $payInfoLoopTargetLabel) {
                                            $paymentMethodTitle = $paymentInfoEl['value'];
                                        }
                                    }
                                }
                            }
                            ?>
                            <?= $paymentMethodTitle ?>
                        </label>
                    </div>
                </td>

            </tr>

            <tr id="pickdrop-total-row">

                <td id="pickdrop-total-label-td" class="bordered-table-data-left" style="border: 1px solid #000000; border-right: unset;">

                    <table style="width: 100%;">

                        <tr>
                            <td style="width: 100%;">
                                <div id="pickup-total-label-div" style="text-align: center;">
                                    <label id="pickup-total-label-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        Pick Up Total Collectible:
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="width: 100%;">
                                <div id="dropoff-total-label-div" style="text-align: center;">
                                    <label id="dropoff-total-label-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        Drop Off Total Collectible:
                                    </label>
                                </div>
                            </td>
                        </tr>

                    </table>

                </td>

                <td id="pickdrop-total-value-td" class="bordered-table-data-right" style="border: 1px solid #000000; border-left: unset;">

                    <table style="width: 100%;">

                        <tr>
                            <td style="width: 100%;">
                                <div id="pickup-total-value-div" style="text-align: center;">
                                    <label id="pickup-total-value-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        <?= "0 " . $orderData['order_currency'] ?>
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="width: 100%;">
                                <div id="dropoff-total-value-div" style="text-align: center;">
                                    <label id="dropoff-total-value-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        <?= "0 " . $orderData['order_currency'] ?>
                                    </label>
                                </div>
                            </td>
                        </tr>

                    </table>

                </td>

            </tr>

            <tr id="weight-row">

                <td id="weight-label-td" colspan="2"  class="bordered-table-data" style="border: 1px solid #000000;">
                    <div id="weight-label-div" class="table-div-left-padded" style="text-align: center;">
                        <label id="weight-label-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                            Weight : <?= $orderData['order_weight'] ?>
                        </label>
                    </div>
                </td>

            </tr>

            <tr id="delivery-date-row">

                <td id="delivery-date-label-td" class="bordered-table-data-left" style="border: 1px solid #000000; border-right: unset;">

                    <table style="width: 100%;">

                        <tr>
                            <td style="width: 100%;">
                                <div id="delivery-date-label-div" style="text-align: center;">
                                    <label id="delivery-date-label-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        Expected Delivery Date:
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="width: 100%;">
                                <div id="delivery-time-label-div" style="text-align: center;">
                                    <label id="delivery-time-label-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        Time Slot:
                                    </label>
                                </div>
                            </td>
                        </tr>

                    </table>

                </td>

                <td id="delivery-date-value-td" class="bordered-table-data-right" style="border: 1px solid #000000; border-left: unset;">

                    <table style="width: 100%;">

                        <tr>
                            <td style="width: 100%;">
                                <div id="delivery-date-value-div" style="text-align: center;">
                                    <label id="delivery-date-value-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        <?= date("F j, Y", strtotime($orderData['delivery_date'])) ?>
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="width: 100%;">
                                <div id="delivery-time-value-div" style="text-align: center;">
                                    <label id="delivery-time-value-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        <?= $orderData['delivery_time_slot'] ?>
                                    </label>
                                </div>
                            </td>
                        </tr>

                    </table>

                </td>

            </tr>

            <tr id="from-address-row">

                <?php $shippingAddress = $orderData['shipping_address']; ?>

                <td id="from-address-main-td" colspan="2"  class="bordered-table-data" style="border: 1px solid #000000;">

                    <table style="width: 100%">

                        <tr id="from-address-top-area">
                            <td class="address-location-left-panel" style="width: 25%;">
                                <div id="from-address-top-area-label-div" style="text-align: center;">
                                    <label id="from-address-top-area-label-label" style="font-size: larger; font-style: normal; font-weight: normal; float: right !important; text-align: right !important;">
                                        To / Recipient
                                    </label>
                                </div>
                            </td>
                            <td class="address-location-right-panel" style="width: 75%;">
                                <div id="from-address-top-area-value-div" style="text-align: center;">
                                    <label id="from-address-top-area-value-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold; float: left !important; text-align: left !important;">
                                        <?= $shippingAddress['first_name'] . " " . $shippingAddress['last_name'] ?>
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr id="from-address-location-heading">

                            <td class="address-location-left-panel" style="width: 25%;">
                                <div id="from-address-location-heading-label-div" style="text-align: center;">
                                    <label id="from-address-location-area-label-label" style="font-size: larger; font-style: normal; font-weight: normal; float: right !important; text-align: right !important;">
                                        Address
                                    </label>
                                </div>
                            </td>

                            <td class="address-location-right-panel" style="width: 75%;">
                                <div id="from-address-location-heading-value-div" style="text-align: center;">
                                    <label id="from-address-location-heading-value-label" style="font-size: larger; font-style: normal; font-weight: normal; float: left !important; text-align: left !important;">

                                    </label>
                                </div>
                            </td>

                        </tr>

                        <tr id="from-address-location-area">

                            <td colspan="2" style="width: 100%;">
                                <div id="from-address-location-area-value-div" style="text-align: center;">
                                    <label id="from-address-location-area-value-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                                        <?php
                                        $shipAddressLoc = "";
                                        if(isset($shippingAddress['company'])) {
                                            $shipAddressLoc .= $shippingAddress['company'] . " ,";
                                        }
                                        $shipAddressLoc .= $shippingAddress['address_1'];
                                        $shipAddressLoc .= ($shippingAddress['address_2'] != null) ? ", " . $shippingAddress['address_2'] : '';
                                        $shipAddressLoc .= ($shippingAddress['address_3'] != null) ? ", " . $shippingAddress['address_3'] . ", " : ', ';
                                        $shipAddressLoc .= $shippingAddress['city'] . ", ";
                                        if(isset($shippingAddress['region'])) {
                                            $shipAddressLoc .= $shippingAddress['region'] . " ,";
                                        }
                                        $shipAddressLoc .= $shippingAddress['post_code'];
                                        ?>
                                        <?= $shipAddressLoc; ?>
                                    </label>
                                </div>
                            </td>

                        </tr>

                        <tr id="from-address-contact-area">

                            <td class="address-location-left-panel" style="width: 25%;">
                                <div id="from-address-contact-area-label-div" style="text-align: center;">
                                    <label id="from-address-contact-area-label-label" style="font-size: larger; font-style: normal; font-weight: normal; float: right !important; text-align: right !important;">
                                        Phone
                                    </label>
                                </div>
                            </td>
                            <td class="address-location-right-panel" style="width: 75%;">
                                <div id="from-address-contact-area-value-div" style="text-align: center;">
                                    <label id="from-address-contact-area-value-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold; float: left !important; text-align: left !important;">
                                        <?= $shippingAddress['contact_number'] ?>
                                    </label>
                                </div>
                            </td>

                        </tr>

                    </table>

                </td>

            </tr>

            <tr id="reference-row">

                <td id="reference-label-td" colspan="2"  class="bordered-table-data" style="border: 1px solid #000000;">
                    <div id="reference-label-div" class="table-div-left-padded" style="text-align: center;">
                        <label id="reference-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                            Reference # : <?= $orderData['increment_id'] ?>
                        </label>
                    </div>
                </td>

            </tr>

            <tr id="city-region-row">

                <td id="city-display-td" class="bordered-table-data-left" style="border: 1px solid #000000; border-right: unset;">

                    <table style="width: 100%;">

                        <tr>
                            <td style="width: 100%;">
                                <div id="city-display-label-div" style="text-align: center;">
                                    <label id="city-display-label-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        City:
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="width: 100%;">
                                <div id="city-display-value-div" style="text-align: center;">
                                    <label id="city-display-value-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                                        <?= $shippingAddress['city'] ?>
                                    </label>
                                </div>
                            </td>
                        </tr>

                    </table>

                </td>

                <td id="region-display-td" class="bordered-table-data-right" style="border: 1px solid #000000; border-left: unset;">

                    <table style="width: 100%;">

                        <tr>
                            <td style="width: 100%;">
                                <div id="region-display-label-div" style="text-align: center;">
                                    <label id="region-display-label-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        Area:
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="width: 100%;">
                                <div id="region-display-value-div" style="text-align: center;">
                                    <label id="region-display-value-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                                        <?= (isset($shippingAddress['region'])) ? $shippingAddress['region'] : 'UNKNOWN'; ?>
                                    </label>
                                </div>
                            </td>
                        </tr>

                    </table>

                </td>

            </tr>

            <tr id="fulfilled-by-row">

                <td id="fulfilled-by-label-td" colspan="2">
                    <div id="fulfilled-by-label-div" class="table-div-left-padded" style="text-align: center;">
                        <label id="fulfilled-by-label-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                            Fulfilled by : {{ $fulfilledBy }}
                        </label>
                    </div>
                </td>

            </tr>

            <tr id="bottom-footer-row">

                <td id="gb-logo-td">
                    <div id="gb-logo-div" style="text-align: center;">
                        <img src="{{ $logoEncoded }}" />
                    </div>
                </td>

                <td id="package-barcode-td">
                    <div id="package-barcode-div" style="text-align: center;">
                        <label id="package-barcode-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                            <barcode dimension="1D" type="C93" value="<?= $orderData['increment_id']?>"
                                     label="label" style=""></barcode>
                        </label>
                    </div>
                </td>

            </tr>

        </tbody>

    </table>

</page>
