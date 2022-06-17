<style type="text/css">
<!--
    table#main-print-invoice-table {
        width: 100%;
    }
    table#main-print-invoice-table > tr {
        width: 100%;
    }

    tbody#main-print-invoice-table-body > tr td div {
        text-align: center;
    }

    tbody#main-print-invoice-table-body > tr td div label {
        font-size: small;
        font-style: normal;
        font-weight: normal;
    }

    tr#top-head-row td {
        width: 30%;
    }

    tr#top-head-row > td div {
        padding: 2px;
    }

    tr#top-head-row td div label {
        font-size: small;
        font-style: normal;
        font-weight: normal;
        text-align: center;
    }

    tr#top-head-row td div label img {
        height: 75px;
        width: 75px;
    }

    tr#top-head-row td div label barcode {
        height: 50px;
    }

    tr#top-head-row td div#package-company-info-div label {
        /*display: grid;*/
    }

    tr#top-head-row td div#package-company-info-div label table {
        width: 100%;
    }

    tr#top-head-row td div#package-company-info-div label table tbody tr {
        width: 100%;
    }

    tr#top-head-row td div#package-company-info-div label table tbody tr td {
        text-align: right;
        width: 100%;
    }

    tr#general-info-row td {
        width: 100%;
    }

    tr#general-info-row > td div {
        padding: 2px;
    }

    tr#general-info-row td div#general-info-div label table {
        background-color: #727272;
        border: 1px solid #000000;
        color: #FFFFFF;
        padding: 10px 20px 10px 20px;
        width: 100%;
    }

    tr#general-info-row td div#general-info-div label table tbody tr td {
        text-align: left;
    }

    tr#invoice-address-row > td {
        width: 100%;
    }

    tr#invoice-address-row > td div {
        padding: 2px;
    }

    tr#invoice-address-row td div#invoice-address-div label table#invoice-address-subtable {
        border: 1px solid #000000;
        /*padding: 10px 20px 10px 20px;*/
        width: 100%;
    }

    tr#invoice-address-row td div#invoice-address-div label table#invoice-address-subtable thead {
        width: 100%;
    }

    tr#invoice-address-row td div#invoice-address-div label table#invoice-address-subtable thead tr th {
        background-color: #ABA6A6;
        border: 1px solid #000000;
        color: #000000;
        padding: 2px 15px 2px 15px;
        text-align: left;
        width: 50%;
    }

    tr#invoice-address-row td div#invoice-address-div label table#invoice-address-subtable > tbody {
        width: 100%;
    }

    tr#invoice-address-row td div#invoice-address-div label table#invoice-address-subtable tbody tr td table tbody tr td {
        color: #000000;
        padding: 2px 15px 2px 15px;
        text-align: left;
        width: 50%;
        word-break: break-word;
    }

    tr#invoice-methods-row > td {
        width: 100%;
    }

    tr#invoice-methods-row > td div {
        padding: 2px;
    }

    tr#invoice-methods-row td div#invoice-methods-div label table#invoice-methods-subtable {
        border: 1px solid #000000;
        /*padding: 10px 20px 10px 20px;*/
        width: 100%;
    }

    tr#invoice-methods-row td div#invoice-methods-div label table#invoice-methods-subtable thead {
        width: 100%;
    }

    tr#invoice-methods-row td div#invoice-methods-div label table#invoice-methods-subtable thead tr th {
        background-color: #ABA6A6;
        border: 1px solid #000000;
        color: #000000;
        padding: 2px 15px 2px 15px;
        text-align: left;
        width: 50%;
    }

    tr#invoice-methods-row td div#invoice-methods-div label table#invoice-methods-subtable > tbody {
        width: 100%;
    }

    tr#invoice-methods-row td div#invoice-methods-div label table#invoice-methods-subtable tbody tr td table tbody tr td {
        color: #000000;
        padding: 2px 15px 2px 15px;
        text-align: left;
        width: 50%;
        word-break: break-word;
    }

    tr#invoice-items-row > td {
        width: 100%;
    }

    tr#invoice-items-row > td div {
        padding: 2px;
    }

    tr#invoice-items-row td div#invoice-items-div label table#invoice-items-subtable {
        border: 1px solid #000000;
        /*padding: 10px 20px 10px 20px;*/
        width: 100%;
    }

    tr#invoice-items-row td div#invoice-items-div label table#invoice-items-subtable thead {
        width: 100%;
    }

    tr#invoice-items-row td div#invoice-items-div label table#invoice-items-subtable thead tr th {
        background-color: #ABA6A6;
        border: 1px solid #000000;
        color: #000000;
        padding: 2px 15px 2px 15px;
        text-align: center;
    }

    tr#invoice-items-row td div#invoice-items-div label table#invoice-items-subtable tbody {
        width: 100%;
    }

    tr#invoice-items-row td div#invoice-items-div label table#invoice-items-subtable tbody tr td {
        color: #000000;
        padding: 1px 5px 1px 5px;
        text-align: center;
        word-break: break-word;
    }

    th.invoice-items-subtable-products-head, td.invoice-items-subtable-products-data {
        width: 30%;
    }

    th.invoice-items-subtable-sku-head, td.invoice-items-subtable-sku-data {
        width: 10%;
    }

    th.invoice-items-subtable-price-head, td.invoice-items-subtable-price-data {
        width: 15%;
    }

    th.invoice-items-subtable-qty-head, td.invoice-items-subtable-qty-data {
        width: 8%;
    }

    th.invoice-items-subtable-unit-head, td.invoice-items-subtable-unit-data {
        width: 10%;
    }

    th.invoice-items-subtable-tax-head, td.invoice-items-subtable-tax-data {
        width: 12%;
    }

    th.invoice-items-subtable-subtotal-head, td.invoice-items-subtable-subtotal-data {
        width: 15%;
    }

    td.invoice-items-subtable-price-data,
    td.invoice-items-subtable-tax-data,
    td.invoice-items-subtable-subtotal-data,
    td.invoice-items-subtable-order-subtotal-label,
    td.invoice-items-subtable-order-subtotal-data,
    td.invoice-items-subtable-order-grandtotal-label,
    td.invoice-items-subtable-order-grandtotal-data {
        font-weight: bold;
    }

    td.invoice-items-subtable-order-subtotal-label, td.invoice-items-subtable-order-grandtotal-label {
        text-align: right;
    }

    tr#delivery-info-row td {
        width: 100%;
    }

    tr#delivery-info-row > td div {
        padding: 2px;
    }

    tr#delivery-info-row td div#delivery-info-div label table {
        background-color: #ABA6A6;
        border: 1px solid #000000;
        color: #000000;
        padding: 10px 20px 10px 20px;
        width: 100%;
    }

    tr#delivery-info-row td div#delivery-info-div label table tbody tr td {
        text-align: left;
    }

-->
</style>

<page>

    <page_header>

    </page_header>
    <page_footer>
        <p style="width: 100%">
            <span style="width: 100%; text-align: right">[[page_cu]]/[[page_nb]]</span>
        </p>
    </page_footer>

    <table id="main-print-invoice-table">

        <tbody id="main-print-invoice-table-body">

            <tr id="top-head-row">

                <td id="package-logo-td">
                    <div id="package-logo-div">
                        <label id="package-logo-label">
                            <img src="{{ $logoEncoded }}" />
                        </label>
                    </div>
                </td>

                <td id="package-barcode-td">
                    <div id="package-barcode-div">
                        <label id="package-barcode-label">
                            <barcode dimension="1D" type="C93" value="{{ $orderData['increment_id'] }}" label="label" style=""></barcode>
                        </label>
                    </div>
                </td>

                <td id="package-company-info-td">
                    <div id="package-company-info-div">
                        <label id="package-company-info-label">
                            <table id="package-company-info-subtable">
                                <tbody>
                                    <tr>
                                        <td>{{ $companyInfo['website'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $companyInfo['location'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $companyInfo['support'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $companyInfo['contact'] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </label>
                    </div>
                </td>

            </tr>

            <tr id="general-info-row">

                <td id="general-info-td" colspan="3">
                    <div id="general-info-div">
                        <label id="general-info-label">
                            <table id="general-info-subtable">
                                <tbody>
                                    <tr>
                                        <td>{{ 'Invoice: #' . $invoiceData['increment_id'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ 'Order: #' . $orderData['increment_id'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ 'Order Date: ' . $serviceHelper->getFormattedTime($orderData['order_created_at'], 'F d, Y, h:i:s A') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </label>
                    </div>
                </td>

            </tr>

            <tr id="invoice-address-row">

                <td id="invoice-address-td" colspan="3">
                    <div id="invoice-address-div">
                        <label id="invoice-address-label">
                            <table id="invoice-address-subtable">
                                <thead>
                                    <tr>
                                        <th>Sold To: </th>
                                        <th>Shipped To: </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>

                                        <?php
                                            $soldToAddress = $orderData['billing_address'];
                                            $soldToName = $soldToAddress['first_name'] . " " . $soldToAddress['last_name'];
                                            $soldToCompany = (isset($soldToAddress['company'])) ? $soldToAddress['company'] . ' ' : '';
                                            $soldToMainAddress = '';
                                            $soldToMainAddress .= (isset($soldToAddress['address_1'])) ? $soldToAddress['address_1'] . ', ' : '';
                                            $soldToMainAddress .= (isset($soldToAddress['address_2'])) ? $soldToAddress['address_2'] . ', ' : '';
                                            $soldToMainAddress .= (isset($soldToAddress['address_3'])) ? $soldToAddress['address_3'] . ' ' : '';
                                            $soldToCity = (isset($soldToAddress['city'])) ? $soldToAddress['city'] . ' ' : '';
                                            $soldToRegion = (isset($soldToAddress['region'])) ? $soldToAddress['region'] . ' ' : '';
                                            $soldToRegion .= (isset($soldToAddress['post_code'])) ? $soldToAddress['post_code'] . ' ' : '';
                                            $soldToCountry = (isset($soldToAddress['country_id'])) ? $soldToAddress['country_id'] . ' ' : '';
                                            $soldToContact = (isset($soldToAddress['contact_number'])) ? $soldToAddress['contact_number'] . ' ' : '';
                                        ?>

                                        <td id="invoice-address-sold-to-td">

                                            <table id="invoice-address-sold-to-table">
                                                <tbody>
                                                    <?php if (trim($soldToName) != '') { ?>
                                                    <tr>
                                                        <td>{{ $soldToName }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if (trim($soldToMainAddress) != '') { ?>
                                                    <tr>
                                                        <td>{{ $soldToMainAddress }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if (trim($soldToCity) != '') { ?>
                                                    <tr>
                                                        <td>{{ $soldToCity }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if (trim($soldToRegion) != '') { ?>
                                                    <tr>
                                                        <td>{{ $soldToRegion }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if (trim($soldToCountry) != '') { ?>
                                                    <tr>
                                                        <td>{{ $soldToCountry }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if (trim($soldToContact) != '') { ?>
                                                    <tr>
                                                        <td>{{ 'T: ' . $soldToContact }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>

                                        </td>

                                        <?php
                                            $shippedToAddress = $orderData['shipping_address'];
                                            $shippedToName = $shippedToAddress['first_name'] . " " . $shippedToAddress['last_name'];
                                            $shippedToCompany = (isset($shippedToAddress['company'])) ? $shippedToAddress['company'] . ' ' : '';
                                            $shippedToMainAddress = '';
                                            $shippedToMainAddress .= (isset($shippedToAddress['address_1'])) ? $shippedToAddress['address_1'] . ', ' : '';
                                            $shippedToMainAddress .= (isset($shippedToAddress['address_2'])) ? $shippedToAddress['address_2'] . ', ' : '';
                                            $shippedToMainAddress .= (isset($shippedToAddress['address_3'])) ? $shippedToAddress['address_3'] . ' ' : '';
                                            $shippedToCity = (isset($shippedToAddress['city'])) ? $shippedToAddress['city'] . ' ' : '';
                                            $shippedToRegion = (isset($shippedToAddress['region'])) ? $shippedToAddress['region'] . ' ' : '';
                                            $shippedToRegion .= (isset($shippedToAddress['post_code'])) ? $shippedToAddress['post_code'] . ' ' : '';
                                            $shippedToCountry = (isset($shippedToAddress['country_id'])) ? $shippedToAddress['country_id'] . ' ' : '';
                                            $shippedToContact = (isset($shippedToAddress['contact_number'])) ? $shippedToAddress['contact_number'] . ' ' : '';
                                        ?>

                                        <td id="invoice-address-shipped-to-td">

                                            <table id="invoice-address-sold-to-table">
                                                <tbody>
                                                    <?php if (trim($shippedToName) != '') { ?>
                                                    <tr>
                                                        <td>{{ $shippedToName }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if (trim($shippedToMainAddress) != '') { ?>
                                                    <tr>
                                                        <td>{{ $shippedToMainAddress }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if (trim($shippedToCity) != '') { ?>
                                                    <tr>
                                                        <td>{{ $shippedToCity }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if (trim($shippedToRegion) != '') { ?>
                                                    <tr>
                                                        <td>{{ $shippedToRegion }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if (trim($shippedToCountry) != '') { ?>
                                                    <tr>
                                                        <td>{{ $shippedToCountry }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if (trim($shippedToContact) != '') { ?>
                                                    <tr>
                                                        <td>{{ 'T: ' . $shippedToContact }}</td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>

                                        </td>

                                    </tr>
                                </tbody>
                            </table>
                        </label>
                    </div>
                </td>

            </tr>

            <tr id="invoice-methods-row">

                <td id="invoice-methods-td" colspan="3">
                    <div id="invoice-methods-div">
                        <label id="invoice-methods-label">
                            <table id="invoice-methods-subtable">
                                <thead>
                                    <tr>
                                        <th>Payment Method : </th>
                                        <th>Shipping Method : </th>
                                    </tr>
                                </thead>
                                <tbody>

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

                                    <tr>

                                        <td id="invoice-methods-payment-method-td">

                                            <table id="invoice-methods-payment-method-table">
                                                <tbody>
                                                    <tr>
                                                        <td>{{ (trim($paymentMethodTitle) != '') ? $paymentMethodTitle : 'Online' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </td>

                                        <td id="invoice-methods-shipping-method-td">

                                            <table id="invoice-methods-shipping-method-table">
                                                <tbody>
                                                    <tr>
                                                        <td>{{ $orderData['shipping_method'] }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{ '(Total Shipping Charges: ' . $invoiceData['order_currency_code'] . ' ' . number_format((float)$invoiceData['shipping_amount'], 2, '.', ',') . ')' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </td>

                                    </tr>

                                </tbody>
                            </table>
                        </label>
                    </div>
                </td>

            </tr>

            <tr id="invoice-items-row">

                <td id="invoice-items-td" colspan="3">
                    <div id="invoice-items-div">
                        <label id="invoice-items-label">
                            <table id="invoice-items-subtable">
                                <thead>
                                    <tr>
                                        <th class="invoice-items-subtable-products-head">Products</th>
                                        <th class="invoice-items-subtable-sku-head">SKU</th>
                                        <th class="invoice-items-subtable-price-head">Price</th>
                                        <th class="invoice-items-subtable-qty-head">Qty</th>
                                        <th class="invoice-items-subtable-unit-head">Unit</th>
                                        <th class="invoice-items-subtable-tax-head">Tax</th>
                                        <th class="invoice-items-subtable-subtotal-head">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $invoiceItems = $invoiceData['items'];
                                        if (is_array($invoiceItems) && (count($invoiceItems) > 0)) {
                                            $orderItems = $orderData['order_items'];
                                            $orderItemArray = [];
                                            foreach($orderItems as $itemEl) {
                                                $orderItemArray[$itemEl['item_id']] = $itemEl;
                                            }
                                            foreach ($invoiceItems as $itemEl) {

                                                $itemPriceAmount = array_key_exists('price_incl_tax', $itemEl) ? (float)$itemEl['price_incl_tax'] : (float)$itemEl['price'];
                                                $itemTaxAmount = array_key_exists('tax_amount', $itemEl) ? (float)$itemEl['tax_amount'] : 0;
                                                $itemRowTotalAmount = array_key_exists('row_total_incl_tax', $itemEl) ? (float)$itemEl['row_total_incl_tax'] : (float)$itemEl['row_total'];

                                    ?>
                                        <tr>
                                            <td class="invoice-items-subtable-products-data">{{ $itemEl['name'] }}</td>
                                            <td class="invoice-items-subtable-sku-data">{{ $itemEl['sku'] }}</td>
                                            <td class="invoice-items-subtable-price-data">{{ $invoiceData['order_currency_code'] . ' ' . number_format($itemPriceAmount, 2, '.', ',') }}</td>
                                            <td class="invoice-items-subtable-qty-data">{{ $itemEl['qty'] }}</td>
                                            <td class="invoice-items-subtable-unit-data">{{ (array_key_exists($itemEl['order_item_id'], $orderItemArray)) ? $orderItemArray[$itemEl['order_item_id']]['selling_unit'] : '' }}</td>
                                            <td class="invoice-items-subtable-tax-data">{{ $invoiceData['order_currency_code'] . ' ' . number_format($itemTaxAmount, 2, '.', ',') }}</td>
                                            <td class="invoice-items-subtable-subtotal-data">{{ $invoiceData['order_currency_code'] . ' ' . number_format($itemRowTotalAmount, 2, '.', ',') }}</td>
                                        </tr>
                                    <?php

                                            }

                                            $orderSubTotalAmount = array_key_exists('subtotal_incl_tax', $invoiceData) ? (float)$invoiceData['subtotal_incl_tax'] : (float)$invoiceData['subtotal'];
                                            $orderGrandTotalAmount = array_key_exists('grand_total', $invoiceData) ? (float)$invoiceData['grand_total'] : 0;

                                    ?>
                                        <tr>
                                            <td class="order-total-divider-row" colspan="7"></td>
                                        </tr>
                                        <tr>
                                           <td class="invoice-items-subtable-order-subtotal-label" colspan="6">Subtotal: </td>
                                           <td class="invoice-items-subtable-order-subtotal-data">{{ $invoiceData['order_currency_code'] . ' ' . number_format($orderSubTotalAmount, 2, '.', ',') }}</td>
                                        </tr>
                                        <tr>
                                           <td class="invoice-items-subtable-order-grandtotal-label" colspan="6">Grand Total: </td>
                                           <td class="invoice-items-subtable-order-grandtotal-data">{{ $invoiceData['order_currency_code'] . ' ' . number_format($orderGrandTotalAmount, 2, '.', ',') }}</td>
                                        </tr>
                                    <?php

                                        } else {
                                            echo '<tr><td colspan="7">No Items Found !</td></tr>';
                                        }
                                    ?>
                                </tbody>

                            </table>
                        </label>
                    </div>
                </td>
            </tr>

            <tr id="delivery-info-row">

                <td id="delivery-info-td" colspan="3">
                    <div id="delivery-info-div">
                        <label id="delivery-info-label">
                            <table id="delivery-info-subtable">
                                <tbody>
                                    <tr>
                                        <td><span style="font-weight: bold">Delivery Date:</span>{{ ' ' . $serviceHelper->getFormattedTime($orderData['delivery_date'], 'F d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><span style="font-weight: bold">Delivery Time:</span>{{ ' ' . $orderData['delivery_time_slot'] }}</td>
                                    </tr>
                                    @if(trim($orderData['delivery_notes'] != ''))
                                    <tr>
                                        <td><span style="font-weight: bold">Delivery Notes:</span>{{ ' ' . $orderData['delivery_notes'] }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </label>
                    </div>
                </td>

            </tr>

        </tbody>

    </table>

</page>
