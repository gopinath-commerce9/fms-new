<page backtop="10mm">

    <page_header>
        <p style="width: 100%">
            <span style="width: 100%; text-align: right"><?= date('d-m-Y h:i:s a') ?></span>
        </p>
    </page_header>
    <page_footer>
        <p style="width: 100%">
            <span style="width: 100%; text-align: right">[[page_cu]]/[[page_nb]]</span>
        </p>
    </page_footer>

    <table id="main-print-label-table" style="width: 100%; border-collapse: collapse;">

        <tbody id="main-print-label-table-body">

            <tr id="order-details-row" style="width: 100%; padding-top: 5px; padding-bottom: 5px">
                <td id="order-details-row-data" style="width: 100%; border: 1px solid #000000;">
                    <table id="order-details-table" style="width: 100%; border-collapse: collapse;">
                        <tr id="order-details-table-id-row" style="width: 100%">
                            <td id="order-details-table-id-data" style="width: 50%; border: 1px solid #000000;">
                                <div id="order-details-table-id-data-label-div" style="text-align: center;">
                                    <label id="order-details-id-data-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">Delivery Date From : <span style="font-weight: bold;"><?= date('d-m-Y', strtotime($startDate)) ?></span></span>
                                    </label>
                                </div>
                            </td>
                            <td id="order-details-table-increment-data" style="width: 50%; border: 1px solid #000000;">
                                <div id="order-details-table-increment-data-label-div" style="text-align: center;">
                                    <label id="order-details-increment-data-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">Delivery Date To : <span style="font-weight: bold;"><?= date('d-m-Y', strtotime($endDate)) ?></span></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr id="order-details-table-destination-row" style="width: 100%">
                            <td id="order-details-table-region-data" style="width: 50%; border: 1px solid #000000;">
                                <div id="order-details-table-region-data-label-div" style="text-align: center;">
                                    <label id="order-details-region-data-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">Time Slot : <span style="font-weight: bold;"><?= ($deliverySlot == '') ? 'All' : $deliverySlot ?></span></span>
                                    </label>
                                </div>
                            </td>
                            <td id="order-details-table-city-data" style="width: 50%; border: 1px solid #000000;">
                                <div id="order-details-table-city-data-label-div" style="text-align: center;">
                                    <label id="order-details-city-data-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">Generated : <span style="font-weight: bold;"><?= date('d-m-Y h:i:s a') ?></span></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr id="order-details-table-delivery-datetime-row" style="width: 100%">
                            <td id="order-details-table-date-data" colspan="2" style="width: 100%; border: 1px solid #000000;">
                                <div id="order-details-table-date-data-label-div" style="text-align: center;">
                                    <label id="order-details-region-date-label-label" class="highlight-info-label" style="font-style: normal; font-weight: normal;">
                                        <span class="font-weight-bolder mb-2">
                                            Total Weight :
                                            <?php
                                                $weightIndexer = 1;
                                                foreach ($filteredOrderTotalWeight as $weightKey => $weightEl) {
                                                    echo '<span style="font-weight: bold;">' . $weightEl['title']. " : " . $weightEl['weight'] . (($weightIndexer < count($filteredOrderTotalWeight)) ? "; " : " "). "</span>";
                                                    $weightIndexer++;
                                                }
                                            ?>
                                        </span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr id="order-item-list-table-before-row" style="width: 100%; padding-top: 5px; padding-bottom: 5px">
                <td id="order-item-list-table-before-data" style="border: 1px solid #000000;">
                    <div id="order-total-amount-label-div" style="text-align: center;">
                        <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: larger; font-style: normal; font-weight: bold;">
                            Order Item Pick List
                        </label>
                    </div>
                </td>
            </tr>

            <tr id="order-item-list-table-wrapper-row" style="width: 100%; padding-top: 5px; padding-bottom: 10px">
                <td id="order-item-list-table-wrapper-data" style="">

                    <table style="width: 100%; border: 1px solid #000000; border-collapse: collapse;">

                        <tr id="order-item-list-table-header-row" style="width: 100%">
                            <th style="width: 12%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                        Date
                                    </label>
                                </div>
                            </th>
                            <th style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                        Slot
                                    </label>
                                </div>
                            </th>
                            <th style="width: 8%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                        Picker
                                    </label>
                                </div>
                            </th>
                            <th style="width: 12%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                        Order #
                                    </label>
                                </div>
                            </th>
                            <th style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                        Type
                                    </label>
                                </div>
                            </th>
                            <th style="width: 13%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                        SKU
                                    </label>
                                </div>
                            </th>
                            <th style="width: 25%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                        Item Name
                                    </label>
                                </div>
                            </th>
                            <th style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                <div id="order-total-amount-label-div" style="text-align: center;">
                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                        Qty
                                    </label>
                                </div>
                            </th>
                        </tr>

                    <?php
                    $orderIterator = 0;
                    $orderItemIterator = 0;
                    foreach ($filteredOrderData as $dateOrderKey => $dateOrderEl) {
                        foreach ($dateOrderEl as $slotOrderKey => $slotOrderEl) {
                            foreach ($slotOrderEl as $idOrderKey => $idOrderEl) {
                                $orderIterator++;
                                $orderItems = $idOrderEl['items'];
                                $shipAddress = $idOrderEl['shippingAddress'];
                                $customerName = '';
                                $customerName .= (isset($shipAddress['firstName'])) ? $shipAddress['firstName'] : '';
                                $customerName .= (isset($shipAddress['lastName'])) ? ' ' . $shipAddress['lastName'] : '';
                                $shipAddressString = '';
                                $shipAddressString .= (isset($shipAddress['address1'])) ? $shipAddress['address1'] : '';
                                $shipAddressString .= (isset($shipAddress['address2'])) ? ', ' . $shipAddress['address2'] : '';
                                $shipAddressString .= (isset($shipAddress['address3'])) ? ', ' . $shipAddress['address3'] : '';
                                $shipAddressString .= (isset($shipAddress['city'])) ? ', ' . $shipAddress['city'] : '';
                                $shipAddressString .= (isset($shipAddress['region'])) ? ', ' . $shipAddress['region'] : '';
                                $shipAddressString .= (isset($shipAddress['postCode'])) ? ', ' . $shipAddress['postCode'] : '';

                    ?>

                                        <tr id="order-item-list-table-customer-name-row-<?= $orderIterator ?>" style="width: 100%; background-color: <?= ($orderIterator % 2 == 0) ? '#9a9c9e' : '#ffffff' ?>">
                                            <td style="width: 12%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" style="font-style: normal; font-weight: normal; word-break: break-all;">

                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" style="font-size: medium; font-style: normal; font-weight: normal; word-break: break-all;">

                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 8%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">

                                                    </label>
                                                </div>
                                            </td>
                                            <td colspan="5" style="width: 70%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                                        <?= 'Customer Name : ' . $customerName ?>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr id="order-item-list-table-customer-address-row-<?= $orderIterator ?>" style="width: 100%; background-color: <?= ($orderIterator % 2 == 0) ? '#9a9c9e' : '#ffffff' ?>">
                                            <td style="width: 12%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" style="font-style: normal; font-weight: normal; word-break: break-all;">

                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" style="font-size: medium; font-style: normal; font-weight: normal; word-break: break-all;">

                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 8%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">

                                                    </label>
                                                </div>
                                            </td>
                                            <td colspan="5" style="width: 70%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                                        <?= 'Customer Address : ' . $shipAddressString ?>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr id="order-item-list-table-customer-address-row-<?= $orderIterator ?>" style="width: 100%; background-color: <?= ($orderIterator % 2 == 0) ? '#9a9c9e' : '#ffffff' ?>">
                                            <td style="width: 12%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" style="font-style: normal; font-weight: normal; word-break: break-all;">

                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" style="font-size: medium; font-style: normal; font-weight: normal; word-break: break-all;">

                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 8%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">

                                                    </label>
                                                </div>
                                            </td>
                                            <td colspan="5" style="width: 70%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                                        <?= 'Delivery Notes : ' . $idOrderEl['deliveryNotes'] ?>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>

                    <?php

                                foreach ($orderItems as $itemTypeKey => $itemTypeEl) {
                                    foreach ($itemTypeEl as $itemKey => $itemEl) {

                                        $orderItemIterator++;
                                        $pickerName = (!empty($itemEl['picker'])) ? $itemEl['picker'] : "";
                                        $orderNumber = (!empty($itemEl['orderNumber'])) ? $itemEl['orderNumber'] : "";
                                        $productCat = (!empty($itemEl['productType'])) ? $itemEl['productType'] : "";
                                        $productSku = (!empty($itemEl['productSku'])) ? $itemEl['productSku'] : "";
                                        $productName = (!empty($itemEl['productName'])) ? $itemEl['productName'] : "";
                                        $weightInfo = (!empty($itemEl['productInfo'])) ? $itemEl['productInfo'] : "";
                                        $originCountry = (!empty($itemEl['originCountry'])) ? $itemEl['originCountry'] : "";
                                        $nameLabel = '';
                                        $nameLabel .= ($productName != '') ? $productName : '';
                                        $nameLabel .= (($nameLabel != '') && ($weightInfo != '')) ? ' ( Pack & Weight Info : ' . $weightInfo . ')' : '';
                                        $nameLabel .= (trim($originCountry) != '') ? ' / ' . $originCountry : '';
                                        $qtyOrdered = (!empty($itemEl['quantity'])) ? $itemEl['quantity'] : "";
                                        $sellingFormat = (!empty($itemEl['sellingUnit'])) ? $itemEl['sellingUnit'] : "";
                                        $qtyLabel = '';
                                        $qtyLabel .= ($qtyOrdered != '') ? $qtyOrdered : '';
                                        $qtyLabel .= (($qtyLabel != '') && ($sellingFormat != '')) ? ' ' . $sellingFormat : '';

                    ?>

                                        <tr id="order-item-list-table-item-row-<?= $orderItemIterator ?>" style="width: 100%; background-color: <?= ($orderIterator % 2 == 0) ? '#9a9c9e' : '#ffffff' ?>">
                                            <td style="width: 12%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" style="font-style: normal; font-weight: normal; word-break: break-all;">
                                                        <?= date('d-m-Y', strtotime($dateOrderKey)) ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" style="font-size: medium; font-style: normal; font-weight: normal; word-break: break-all;">
                                                        <?= $slotOrderKey ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 8%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: normal; word-break: break-all;">
                                                        <?= $pickerName ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 12%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: normal; word-break: break-all;">
                                                        <?= $orderNumber ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                                        <?= $productCat ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 13%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: normal; word-break: break-all;">
                                                        <?= $productSku ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 25%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                                        <?= $nameLabel ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td style="width: 10%; border: 1px solid #000000; padding: 5px;">
                                                <div id="order-total-amount-label-div" style="text-align: center;">
                                                    <label id="order-total-amount-label-label" class="highlight-info-label" style="font-size: medium; font-style: normal; font-weight: bold; word-break: break-all;">
                                                        <?= $qtyLabel ?>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>

                    <?php

                                    }
                                }
                            }
                        }
                    }

                    ?>

                    </table>

                </td>
            </tr>

        </tbody>

    </table>

</page>
