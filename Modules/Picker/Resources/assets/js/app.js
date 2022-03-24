"use strict";
var PickerCustomJsBlocks = function() {

    var setDeliveryDateFilterDatePicker = function() {
        var arrows;
        if (KTUtil.isRTL()) {
            arrows = {
                leftArrow: '<i class="la la-angle-right"></i>',
                rightArrow: '<i class="la la-angle-left"></i>'
            }
        } else {
            arrows = {
                leftArrow: '<i class="la la-angle-left"></i>',
                rightArrow: '<i class="la la-angle-right"></i>'
            }
        }
        $('#delivery_date_picker').datepicker({
            rtl: KTUtil.isRTL(),
            todayHighlight: true,
            orientation: "bottom left",
            templates: arrows,
            format: {
                toDisplay: function (date, format, language) {
                    var dObj = new Date(date);
                    var dayValue = dObj.getDate();
                    var dayStr = (dayValue <= 9) ? '0' + dayValue : dayValue;
                    var monthValue = dObj.getMonth() + 1;
                    var monthStr = (monthValue <= 9) ? '0' + monthValue : monthValue;
                    var yearString = dObj.getFullYear();
                    var dateString = '' + yearString + '-' + monthStr + '-' + dayStr;
                    var dateStringDisplay = '' + dayStr + '-' + monthStr + '-' + yearString;
                    $('#delivery_date_filter').val(dateString);
                    return dateStringDisplay;
                },
                toValue: function (date, format, language) {
                    var dObj = new Date(date);
                    var dayValue = dObj.getDate();
                    var dayStr = (dayValue <= 9) ? '0' + dayValue : dayValue;
                    var monthValue = dObj.getMonth() + 1;
                    var monthStr = (monthValue <= 9) ? '0' + monthValue : monthValue;
                    var yearString = dObj.getFullYear();
                    var dateString = '' + yearString + '-' + monthStr + '-' + dayStr;
                    var dateStringDisplay = '' + dayStr + '-' + monthStr + '-' + yearString;
                    $('#delivery_date_filter').val(dateString);
                    return dateStringDisplay;
                }
            }
        });
    };

    $.fn.dataTable.Api.register('column().title()', function() {
        return $(this.header()).text().trim();
    });

    var initPickerSaleOrderTable = function() {

        var table = $('#picker_order_filter_table');
        var targetForm = $('form#filter_picker_order_form');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 10,
            order: [[0, 'asc']],
            searchDelay: 500,
            processing: true,
            language: {
                processing: '<div class="btn btn-secondary spinner spinner-dark spinner-right">Please Wait</div>',
            },
            serverSide: true,
            ajax: {
                url: targetForm.attr('action'),
                type: targetForm.attr('method'),
                data: function(d) {
                    $.each(targetForm.serializeArray(), function(key, val) {
                        d[val.name] = val.value;
                    });
                    d['columnsDef'] = [
                        'incrementId', 'channel', 'region', 'customerName', 'deliveryDate', 'deliveryTimeSlot',
                        'deliveryPickerTime', 'orderStatus', 'actions'
                    ];
                },
            },
            columns: [
                {data: 'incrementId'},
                {data: 'channel'},
                {data: 'region'},
                {data: 'customerName'},
                {data: 'deliveryDate'},
                {data: 'deliveryTimeSlot'},
                {data: 'deliveryPickerTime'},
                {data: 'orderStatus'},
                {data: 'actions', responsivePriority: -1},
            ],
            columnDefs: [{
                targets: -1,
                title: 'Actions',
                orderable: false,
                render: function(data, type, full, meta) {
                    return '<a href="' + data + '" target="_blank">View Order</a>';
                },
            }, {
                targets: 7,
                title: 'Status',
                orderable: true,
                render: function(data, type, full, meta) {
                    return '<span class="label label-lg font-weight-bold label-light-primary label-inline">' + data + '</span>';
                },
            }],
        });

        $('button#filter_picker_order_filter_btn').on('click', function(e) {
            e.preventDefault();
            dataTable.table().draw();
        });

        $('button#filter_picker_order_reset_btn').on('click', function(e) {
            e.preventDefault();
            $('.datatable-input').each(function() {
                $(this).val('');
            });
            dataTable.table().draw();
        });

    };

    var orderFormActions = function() {

        var form = jQuery('#order_view_status_change_form');
        jQuery('input#picker_btn_submit').on('click', function (e) {
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
                    alert('Some order items not checked for availability.');
                } else {
                    if (!allItemsAvailable) {
                        if (confirm('Some of the order items are not available. The Sale Order will be put to "On Hold" status. Do you want to continue?')) {
                            // Save it!
                            form.submit();
                        } else {

                        }
                    } else {
                        form.submit();
                    }
                }
            }

        });

    };

    var resyncOrderData = function(orderId) {
        $('#order_resync_btn').on('click', function(e){
            e.preventDefault();
            if (confirm('Do you want to re-sync and update the Sale Order details from the Server?')) {
                $.ajax({
                    url: $(this).attr('href'),
                    method: 'GET',
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
                        location.reload();
                    },
                    error: function (jqXhr, textStatus, errorMessage) {
                        KTApp.unblockPage();
                        showAlertMessage(errorMessage);
                    }
                });
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
        dashboardPage: function(hostUrl){
            setDeliveryDateFilterDatePicker();
            initPickerSaleOrderTable();
        },
        orderViewPage: function(hostUrl, orderId) {
            orderFormActions();
            resyncOrderData(orderId);
        },
    };

}();
