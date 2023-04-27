"use strict";
var CashierCustomJsBlocks = function() {

    var scanOrderNumberBarcode = function() {
        $('#scan-order-number-form-submit-btn').on('click', function(e){
            e.preventDefault();
            var targetForm = $('form#scan-order-number-form');
            var formData = targetForm.serializeArray();
            $.ajax({
                url: targetForm.attr('action'),
                type: targetForm.attr('method'),
                data: formData,
                beforeSend: function() {
                    KTApp.blockPage({
                        overlayColor: '#000000',
                        state: 'danger',
                        message: 'Please wait...'
                    });
                },
                success: function(data){
                    KTApp.unblockPage();
                    showAlertMessage(data.message);
                    $('#sale-order-details-main-area').html(data.data.orderDetailsHtml);
                    $('#sale-order-items-main-area').html(data.data.orderItemsHtml);
                    $('#item_order_id').val(data.data.recordId);
                    $('#order_item_rescan').val(0);
                    $('#order_item_barcode').val('');
                    /*$('#order_number').val('');*/
                    $('#order_item_barcode').focus('');
                },
                error: function (jqXhr, textStatus, errorMessage) {
                    KTApp.unblockPage();
                    showAlertMessage(errorMessage);
                }
            });
        });
    };
    var scanOrderItemBarcode = function() {
        $('#scan-order-item-form-submit-btn').on('click', function(e){
            e.preventDefault();
            var targetForm = $('form#scan-order-item-form');
            var formData = targetForm.serializeArray();
            $.ajax({
                url: targetForm.attr('action'),
                type: targetForm.attr('method'),
                data: formData,
                beforeSend: function() {
                    KTApp.blockPage({
                        overlayColor: '#000000',
                        state: 'danger',
                        message: 'Please wait...'
                    });
                },
                success: function(data){
                    if (data.success === true) {
                        KTApp.unblockPage();
                        showAlertMessage(data.message);
                        $('#sale-order-items-main-area').html(data.data.orderItemsHtml);
                        $('#item_order_id').val(data.data.recordId);
                        $('#order_item_rescan').val(0);
                        $('#order_item_barcode').val('');
                        /*$('#order_number').val('');*/
                        $('#order_item_barcode').focus('');
                    } else {
                        if (data.data.hasOwnProperty('rescanBarcode') && (data.data.rescanBarcode === 1)) {
                            KTApp.unblockPage();
                            var confirmMessage = "";
                            confirmMessage += data.message;
                            confirmMessage += " Do you want to Proceed?";
                            if (confirm(confirmMessage)) {
                                $('#order_item_rescan').val(1);
                                $('#scan-order-item-form-submit-btn').click();
                            }
                        } else {
                            KTApp.unblockPage();
                            alert(data.message);
                        }
                    }
                },
                error: function (jqXhr, textStatus, errorMessage) {
                    KTApp.unblockPage();
                    showAlertMessage(errorMessage);
                }
            });
        });
    };

    var orderItemBarcodesReset = function(hostUrl, token) {

        jQuery(document).on('click', 'button.reset_barcodes_sale_item_btn', function (e) {
            var orderId = $(this).data('order-id');
            var orderItemId = $(this).data('order-item-id');
            var orderItemSku = $(this).data('order-item-sku');
            if (confirm('This will reset and clear the barcodes scanned for this Order Item "' + orderItemSku + '". Do you want to continue?')) {
                $.ajax({
                    url: hostUrl + '/cashier/clear-sale-item-barcodes',
                    type: 'POST',
                    data: {
                        orderId: orderId,
                        itemId: orderItemId,
                        itemSku: orderItemSku,
                        _token: token
                    },
                    beforeSend: function() {
                        KTApp.blockPage({
                            overlayColor: '#000000',
                            state: 'danger',
                            message: 'Please wait...'
                        });
                    },
                    success: function(data){
                        if (data.success === true) {
                            KTApp.unblockPage();
                            showAlertMessage(data.message);
                            $('#sale-order-items-main-area').html(data.data.orderItemsHtml);
                            $('#item_order_id').val(data.data.recordId);
                            $('#order_item_rescan').val(0);
                            $('#order_item_barcode').val('');
                            /*$('#order_number').val('');*/
                            $('#order_item_barcode').focus('');
                        } else {
                            KTApp.unblockPage();
                            showAlertMessage(data.message);
                        }
                    },
                    error: function (jqXhr, textStatus, errorMessage) {
                        KTApp.unblockPage();
                        showAlertMessage(errorMessage);
                    }
                });
            }
        });

    };

    var orderFormActions = function() {

        jQuery(document).on('click', 'input#cashier_order_scan_submit_btn', function (e) {
            e.preventDefault();
            var boxCount = jQuery('input#box_qty_1').val();
            if (isNaN(boxCount) || (parseInt(boxCount) <= 0)) {
                alert('The Box Count should be minimum 1.');
            } else {
                var allItemsChecked = true;
                var allItemsAvailable = true;
                $("select.store-availability").each(function() {
                    var itemAvailableCheck = $(this).val();
                    if (itemAvailableCheck == '') {
                        allItemsChecked = false;
                    } else if (parseInt(itemAvailableCheck) == 0) {
                        allItemsAvailable = false;
                    }
                });
                if (!allItemsChecked) {
                    alert('Some order items not scanned.');
                } else {
                    if (!allItemsAvailable) {
                        if (confirm('Some of the order items are not available. The Sale Order will be put to "On Hold" status. Do you want to continue?')) {
                            jQuery('form#order_view_status_change_form').submit();
                        }
                    } else {
                        jQuery('form#order_view_status_change_form').submit();
                    }
                }
            }

        });

    };

    var showAlertMessage = function(message) {
        $("div.custom_alert_trigger_messages_area")
            .html('<div class="alert alert-custom alert-dark alert-light-dark fade show" role="alert">' +
                '<div class="alert-icon"><i class="flaticon-information"></i></div>' +
                '<div class="alert-text">' + message + '</div>' +
                '<div class="alert-close">' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true"><i class="ki ki-close"></i></span>' +
                '</button>' +
                '</div>' +
                '</div>');
    };

    return {
        dashboardPage: function(hostUrl, token){
            $('input#order_number').val('');
            $('input#order_number').focus();
            scanOrderNumberBarcode();
            scanOrderItemBarcode();
            orderItemBarcodesReset(hostUrl, token);
            orderFormActions();
        }
    };

}();
