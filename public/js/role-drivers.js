"use strict";
var RoleDriversCustomJsBlocks = function() {

    var initRoleDriversListTable = function() {
        var table = $('#role_driver_list_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    var initRoleDriversReportViewTable = function() {
        var table = $('#role_driver_report_view_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    var initRoleDriverOrderListTable = function() {
        var table = $('#driver_view_orders_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [50, 100, 250],
            pageLength: 50,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    var initDriverReportDateRangePicker = function () {
        var apiDRPicker = $('#delivery_date_range_filter').daterangepicker({
            buttonClasses: ' btn',
            applyClass: 'btn-primary',
            cancelClass: 'btn-secondary',
            locale: {
                format: 'DD/MM/YYYY'
            }
        }, function(start, end, label) {
            $('input#delivery_date_start_filter').val(start.format('YYYY-MM-DD'));
            $('input#delivery_date_end_filter').val(end.format('YYYY-MM-DD'));
        });
        apiDRPicker.on('show.daterangepicker', function(ev, picker) {
            //do something, like clearing an input
            $('input#delivery_date_start_filter').val(picker.startDate.format('YYYY-MM-DD'));
            $('input#delivery_date_end_filter').val(picker.endDate.format('YYYY-MM-DD'));
        });
    };

    var select2ElementsInitiator = function () {

        $('#driver_filter').select2({
            placeholder: "Select Drivers",
        });

    };

    var initDriverSaleOrderReportTable = function() {

        var table = $('#driver_report_filter_table');
        var targetForm = $('form#filter_driver_report_form');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [50, 100, 200],
            pageLength: 50,
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
                timeout:600000,
                data: function(d) {
                    var driverValues = '';
                    $.each(targetForm.serializeArray(), function(key, val) {
                        if (val.name === 'filter_action') {
                            d[val.name] = 'datatable';
                        } else {
                            if (val.name === 'driver_filter') {
                                driverValues = driverValues + ((driverValues === '') ? '' : ',') + val.value;
                            } else {
                                d[val.name] = val.value;
                            }
                        }
                    });
                    d['driver_values'] = driverValues;
                    d['columnsDef'] = [
                        'driverId', 'driver', 'active', 'feeder', 'date', 'assignedOrders', 'deliveryOrders',
                        'deliveredOrders', 'canceledOrders', 'actions'
                    ];
                },
            },
            columns: [
                {data: 'driverId'},
                {data: 'driver'},
                {data: 'active'},
                {data: 'feeder'},
                {data: 'date'},
                {data: 'assignedOrders'},
                {data: 'deliveryOrders'},
                {data: 'deliveredOrders'},
                {data: 'canceledOrders'},
                {data: 'actions', className: 'text-wrap', responsivePriority: -1},
            ],
            columnDefs: [{
                targets: -1,
                title: 'Actions',
                orderable: false,
                render: function(data, type, full, meta) {
                    return '<a href="' + data + '" target="_blank">View More</a>';
                },
            }, {
                targets: 2,
                title: 'Active',
                orderable: true,
                render: function(data, type, full, meta) {
                    return '<span class="label label-lg font-weight-bold label-light-primary label-inline">' + data + '</span>';
                },
            }, {
                targets: 3,
                title: 'Feeder',
                orderable: true,
                render: function(data, type, full, meta) {
                    return '<span class="label label-lg font-weight-bold label-light-primary label-inline">' + data + '</span>';
                },
            }],
        });

        $('button#filter_driver_report_filter_btn').on('click', function(e) {
            e.preventDefault();
            dataTable.table().draw();
        });

        $('button#filter_driver_report_reset_btn').on('click', function(e) {
            e.preventDefault();
            $('.datatable-input').each(function() {
                $(this).val('');
            });
            dataTable.table().draw();
        });

        $('button#filter_driver_report_excel_btn').on('click', function(e) {
            e.preventDefault();
            getDriverReportExcel();
        });

    };

    var getDriverReportExcel = function () {
        var targetForm = $('#filter_driver_report_form');
        $('#filter_action').val('excel_sheet');
        var driverValues = '';
        $.each(targetForm.serializeArray(), function(key, val) {
            if (val.name === 'driver_filter') {
                driverValues = driverValues + ((driverValues === '') ? '' : ',') + val.value;
            }
        });
        $('#driver_values').val(driverValues);
        targetForm.submit();
    };

    var initFeederReportDateRangePicker = function () {
        var apiDRPicker = $('#delivery_date_range_filter').daterangepicker({
            buttonClasses: ' btn',
            applyClass: 'btn-primary',
            cancelClass: 'btn-secondary',
            locale: {
                format: 'DD/MM/YYYY'
            }
        }, function(start, end, label) {
            $('input#delivery_date_start_filter').val(start.format('YYYY-MM-DD'));
            $('input#delivery_date_end_filter').val(end.format('YYYY-MM-DD'));
        });
        apiDRPicker.on('show.daterangepicker', function(ev, picker) {
            //do something, like clearing an input
            $('input#delivery_date_start_filter').val(picker.startDate.format('YYYY-MM-DD'));
            $('input#delivery_date_end_filter').val(picker.endDate.format('YYYY-MM-DD'));
        });
    };

    var feederViewSelect2ElementsInitiator = function () {

        $('#driver_filter').select2({
            placeholder: "Select Drivers",
        });

    };

    var initFeederSaleOrderReportTable = function() {

        var table = $('#feeder_report_filter_table');
        var targetForm = $('form#filter_feeder_report_form');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [50, 100, 200],
            pageLength: 50,
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
                timeout:600000,
                data: function(d) {
                    var driverValues = '';
                    $.each(targetForm.serializeArray(), function(key, val) {
                        if (val.name === 'filter_action') {
                            d[val.name] = 'datatable';
                        } else {
                            if (val.name === 'driver_filter') {
                                driverValues = driverValues + ((driverValues === '') ? '' : ',') + val.value;
                            } else {
                                d[val.name] = val.value;
                            }
                        }
                    });
                    d['driver_values'] = driverValues;
                    d['columnsDef'] = [
                        'orderNumber', 'feeders', 'channel', 'region', 'customerName', 'orderDeliveryDate', 'driverDeliveryDate', 'paymentMethod',
                        'collectionVerified', 'initialPay', 'amountCollected', 'totalCollected', 'totalPaid', 'orderTotal', 'paymentStatus', 'collectionVerifiedAt',
                        'driver', 'deliveredAt', 'orderStatus', 'customerAddress', 'action',
                    ];
                },
            },
            columns: [
                {data: 'orderNumber', className: 'text-wrap'},
                {data: 'feeders', className: 'text-wrap'},
                {data: 'channel', className: 'text-wrap'},
                {data: 'region', className: 'text-wrap'},
                {data: 'customerName', className: 'text-wrap'},
                {data: 'orderDeliveryDate', className: 'text-wrap'},
                {data: 'driverDeliveryDate', className: 'text-wrap'},
                {data: 'paymentMethod', className: 'text-wrap'},
                {data: 'collectionVerified', className: 'text-wrap'},
                {data: 'initialPay', className: 'text-wrap'},
                {data: 'amountCollected', className: 'text-wrap'},
                {data: 'totalCollected', className: 'text-wrap'},
                {data: 'totalPaid', className: 'text-wrap'},
                {data: 'orderTotal', className: 'text-wrap'},
                {data: 'paymentStatus', className: 'text-wrap'},
                {data: 'collectionVerifiedAt', className: 'text-wrap'},
                {data: 'driver', className: 'text-wrap'},
                {data: 'deliveredAt', className: 'text-wrap'},
                {data: 'orderStatus', className: 'text-wrap'},
                {data: 'customerAddress', className: 'text-wrap'},
                {data: 'actions', className: 'text-nowrap', responsivePriority: -1},
            ],
            columnDefs: [],
        });

        $('button#filter_feeder_report_filter_btn').on('click', function(e) {
            e.preventDefault();
            dataTable.table().draw();
        });

        $('button#filter_feeder_report_reset_btn').on('click', function(e) {
            e.preventDefault();
            $('.datatable-input').each(function() {
                $(this).val('');
            });
            dataTable.table().draw();
        });

        $('button#filter_feeder_report_excel_btn').on('click', function(e) {
            e.preventDefault();
            getFeederReportExcel();
        });

    };

    var getFeederReportExcel = function () {
        var targetForm = $('#filter_feeder_report_form');
        $('#filter_action').val('excel_sheet');
        var driverValues = '';
        $.each(targetForm.serializeArray(), function(key, val) {
            if (val.name === 'driver_filter') {
                driverValues = driverValues + ((driverValues === '') ? '' : ',') + val.value;
            }
        });
        $('#driver_values').val(driverValues);
        targetForm.submit();
    };

    var reportViewActions = function (hostUrl, token) {

        $(document).on('click', 'a.driver-report-single-order-verify-btn', function(e) {
            e.preventDefault();
            var targetHref = $(this).attr('href');
            var orderId = $(this).data('order-id');
            var orderNumber = $(this).data('order-number');
            if (confirm('You are verifying the amount collected by driver for Order#"' + orderNumber + '". Do you want to continue?')) {
                var data = {
                    _token: token
                };
                $.ajax({
                    url: targetHref,
                    data: data,
                    method: 'POST',
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
                    }
                });
            } else {

            }
        });

    };

    var feederReportViewActions = function (hostUrl, token) {

        $(document).on('click', 'a.feeder-report-single-order-verify-btn', function(e) {
            e.preventDefault();
            var targetHref = $(this).attr('href');
            var orderId = $(this).data('order-id');
            var orderNumber = $(this).data('order-number');
            if (confirm('You are verifying the amount collected by driver for Order#"' + orderNumber + '". Do you want to continue?')) {
                var data = {
                    _token: token
                };
                $.ajax({
                    url: targetHref,
                    data: data,
                    method: 'POST',
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
                    }
                });
            } else {

            }
        });

    };

    var reportEditViewFormActions = function() {
        var form = jQuery('#driver_report_single_order_edit_form');
        $('input.amount_collected_input').on("keyup", function(evt) {
            var amountStr = $(this).val();
            var allowedChars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'];
            var cleanStr = '';
            var dotCount = 0;
            var amountStrArray = amountStr.toString().split('');
            amountStrArray.forEach(function(letter, index) {
                if (allowedChars.indexOf(letter.toString()) >= 0) {
                    if ((letter.toString() === '.')) {
                        if ((dotCount === 0) && (index > 0)) {
                            cleanStr = cleanStr + letter.toString();
                            dotCount = 1;
                        }
                    } else {
                        cleanStr = cleanStr + letter.toString();
                    }
                }
            });
            $(this).val(cleanStr);
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
        listPage: function(hostUrl){
            initRoleDriversListTable();
        },
        reportPage: function(hostUrl){
            initDriverReportDateRangePicker();
            select2ElementsInitiator();
            initDriverSaleOrderReportTable();
        },
        viewPage: function(hostUrl) {
            initRoleDriverOrderListTable();
        },
        reportViewPage: function(hostUrl, token) {
            initRoleDriversReportViewTable();
            reportViewActions(hostUrl, token);
        },
        feederReportPage: function(hostUrl, token) {
            initFeederReportDateRangePicker();
            feederViewSelect2ElementsInitiator();
            initFeederSaleOrderReportTable();
            feederReportViewActions(hostUrl, token);
        },
        reportEditViewPage: function(hostUrl) {
            reportEditViewFormActions();
        },
    };

}();
