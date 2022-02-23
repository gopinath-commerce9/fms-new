<page>

    <page_header>

    </page_header>
    <page_footer>

    </page_footer>

    <table id="main-print-label-table" style="width: 100%;">

        <tbody id="main-print-label-table-body">

            <tr id="top-head-row" style="width: 100%; padding-top: 5px; padding-bottom: 5px">

                <td id="top-head-row-data" style="width: 100%">

                    <table id="top-head-wrapper-table" style="width: 100%">

                        <tr id="top-head-wrapper-table-row" style="width: 100%">

                            <td id="package-count-td" style="width: 50%">
                                <div id="package-count-div" style="text-align: center;">
                                    <label id="package-count-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        Item Count: <?= count($orderData['order_items']) ?>
                                    </label>
                                </div>
                            </td>

                            <td id="package-barcode-td" style="width: 50%">
                                <div id="package-barcode-div" style="text-align: center;">
                                    <label id="package-barcode-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        <barcode dimension="1D" type="C93" value="<?= $orderData['increment_id'] ?>"
                                                 label="label" style=""></barcode>
                                    </label>
                                </div>
                            </td>

                        </tr>

                    </table>

                </td>

            </tr>

            <tr id="order-details-row" style="width: 100%; padding-top: 5px; padding-bottom: 5px">
                <td id="order-details-row-data" style="width: 100%; border: 1px solid #000000;">
                    <table id="order-details-table" style="width: 100%">
                        <tr id="order-details-table-id-row" style="width: 100%">
                            <td id="order-details-table-id-data" style="width: 50%">
                                <div id="order-details-table-id-data-label-div" style="text-align: center;">
                                    <label id="order-details-id-data-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">Order # : <?= $orderData['order_id'] ?></span>
                                    </label>
                                </div>
                            </td>
                            <td id="order-details-table-increment-data" style="width: 50%">
                                <div id="order-details-table-increment-data-label-div" style="text-align: center;">
                                    <label id="order-details-increment-data-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">Reference # : <?= $orderData['increment_id'] ?></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr id="order-details-table-destination-row" style="width: 100%">
                            <td id="order-details-table-region-data" style="width: 50%">
                                <div id="order-details-table-region-data-label-div" style="text-align: center;">
                                    <label id="order-details-region-data-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">Region : <?= $orderData['region'] ?></span>
                                    </label>
                                </div>
                            </td>
                            <td id="order-details-table-city-data" style="width: 50%">
                                <div id="order-details-table-city-data-label-div" style="text-align: center;">
                                    <label id="order-details-city-data-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">City : <?= $orderData['city'] ?></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr id="order-details-table-delivery-datetime-row" style="width: 100%">
                            <td id="order-details-table-date-data" style="width: 50%">
                                <div id="order-details-table-date-data-label-div" style="text-align: center;">
                                    <label id="order-details-region-date-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">Expected Delivery : <?= date("F j, Y", strtotime($orderData['delivery_date'])) ?></span>
                                    </label>
                                </div>
                            </td>
                            <td id="order-details-table-time-data" style="width: 50%">
                                <div id="order-details-table-time-data-label-div" style="text-align: center;">
                                    <label id="order-details-time-data-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">Time : <?= $orderData['delivery_time_slot'] ?></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr id="order-item-list-table-before-row" style="padding-top: 5px; padding-bottom: 5px">
                <td id="order-item-list-table-before-data" style="border: 1px solid #000000;">
                    <div id="order-total-amount-label-div" style="text-align: center;">
                        <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                            Order Item List
                        </label>
                    </div>
                </td>
            </tr>

            <tr id="order-item-list-table-wrapper-row" style="padding-top: 5px; padding-bottom: 10px">
                <td id="order-item-list-table-wrapper-data" style="">

                    <table style="width: 100%; border: 1px solid #000000;">

                        <tr id="order-item-list-table-header-row" style="width: 100%">
                            <th style="width: 7%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold; overflow-wrap: break-word;">
                                        S. No
                                    </label>
                                </div>
                            </th>
                            <th style="width: 15%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold; overflow-wrap: break-word;">
                                        SKU
                                    </label>
                                </div>
                            </th>
                            <th style="width: 50%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold; overflow-wrap: break-word;">
                                        Item Name
                                    </label>
                                </div>
                            </th>
                            <th style="width: 18%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold; overflow-wrap: break-word;">
                                        Quantity
                                    </label>
                                </div>
                            </th>
                            <th style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold; overflow-wrap: break-word;">
                                        Added
                                    </label>
                                </div>
                            </th>
                        </tr>

                    <?php
                    $orderItemIterator = 1;
                    foreach ($orderData['order_items'] as $item) {

                        $productSku = (!empty($item['item_sku'])) ? $item['item_sku'] : "";
                        $productName = (!empty($item['item_name'])) ? $item['item_name'] : "";
                        $weightInfo = (!empty($item['item_info'])) ? $item['item_info'] : "";
                        $nameLabel = '';
                        $nameLabel .= ($productName != '') ? $productName : '';
                        $nameLabel .= (($nameLabel != '') && ($weightInfo != '')) ? ' ( Pack & Weight Info : ' . $weightInfo . ')' : '';
                        $qtyOrdered = (!empty($item['qty_ordered'])) ? $item['qty_ordered'] : "";
                        $sellingFormat = (!empty($item['selling_unit'])) ? $item['selling_unit'] : "";
                        $qtyLabel = '';
                        $qtyLabel .= ($qtyOrdered != '') ? $qtyOrdered : '';
                        $qtyLabel .= (($qtyLabel != '') && ($sellingFormat != '')) ? ' ' . $sellingFormat : '';

                    ?>

                        <tr id="order-item-list-table-item-row-<?= $orderItemIterator ?>" style="width: 100%">
                            <th style="width: 7%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" style="font-style: normal; font-weight: normal; overflow-wrap: break-word;">
                                        <?= $orderItemIterator ?>
                                    </label>
                                </div>
                            </th>
                            <th style="width: 15%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" style="font-style: normal; font-weight: normal; overflow-wrap: break-word;">
                                        <?= $productSku ?>
                                    </label>
                                </div>
                            </th>
                            <th style="width: 50%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold; overflow-wrap: break-word;">
                                        <?= $nameLabel ?>
                                    </label>
                                </div>
                            </th>
                            <th style="width: 18%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-style: normal; font-weight: bold; overflow-wrap: break-word;">
                                        <?= $qtyLabel ?>
                                    </label>
                                </div>
                            </th>
                            <th style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold; overflow-wrap: break-word;">

                                    </label>
                                </div>
                            </th>
                        </tr>

                    <?php

                        $orderItemIterator++;

                    }

                    ?>

                    </table>

                </td>
            </tr>

            <tr id="bottom-footer-row" style="width: 100%; padding-top: 5px; padding-bottom: 5px">

                <td id="bottom-footer-row-data" style="width: 100%">

                    <table id="bottom-footer-wrapper-table" style="width: 100%">

                        <tr id="bottom-footer-wrapper-table-row" style="width: 100%">

                            <td id="gb-logo-td" style="width: 50%">
                                <div id="gb-logo-div" style="text-align: center;">
                                    <img src="{{ $logoEncoded }}" />
                                </div>
                            </td>

                            <td id="package-barcode-td" style="width: 50%">
                                <div id="package-barcode-div" style="text-align: center;">
                                    <label id="package-barcode-label" style="font-size: larger; font-style: normal; font-weight: normal;">
                                        <barcode dimension="1D" type="C93" value="<?= $orderData['increment_id']?>"
                                                 label="label" style=""></barcode>
                                    </label>
                                </div>
                            </td>

                        </tr>

                    </table>

                </td>

            </tr>

        </tbody>

    </table>

</page>
