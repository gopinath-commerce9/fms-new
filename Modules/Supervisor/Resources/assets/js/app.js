"use strict";
var SupervisorCustomJsBlocks = function() {

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
        $('#delivery_date_filter').datepicker({
            rtl: KTUtil.isRTL(),
            todayHighlight: true,
            orientation: "bottom left",
            templates: arrows
        });
    };

    $.fn.dataTable.Api.register('column().title()', function() {
        return $(this.header()).text().trim();
    });

    var initSupervisorPickersListTable = function() {
        var table = $('#supervisor_picker_list_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 5,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    var initSupervisorDriversListTable = function() {
        var table = $('#supervisor_driver_list_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 5,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    var initSupervisorPickerOrderListTable = function() {
        var table = $('#picker_view_orders_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 5,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    var initSupervisorDriverOrderListTable = function() {
        var table = $('#driver_view_orders_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 5,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    var initSupervisorSaleOrderTable = function() {

        var table = $('#supervisor_order_filter_table');
        var targetForm = $('form#filter_supervisor_order_form');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [5, 10, 25, 50],
            pageLength: 5,
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
                        'incrementId', 'channel', 'region', 'customerName', 'deliveryDate', 'deliveryTimeSlot', 'deliveryPicker',
                        'deliveryPickerTime', 'deliveryDriver', 'deliveryDriverTime', 'orderStatus', 'actions'
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
                {data: 'deliveryPicker'},
                {data: 'deliveryPickerTime'},
                {data: 'deliveryDriver'},
                {data: 'deliveryDriverTime'},
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
                targets: 10,
                title: 'Status',
                orderable: true,
                render: function(data, type, full, meta) {
                    return '<span class="label label-lg font-weight-bold label-light-primary label-inline">' + data + '</span>';
                },
            }],
        });

        $('button#filter_supervisor_order_filter_btn').on('click', function(e) {
            e.preventDefault();
            dataTable.table().draw();
        });

        $('button#filter_supervisor_order_reset_btn').on('click', function(e) {
            e.preventDefault();
            $('.datatable-input').each(function() {
                $(this).val('');
            });
            dataTable.table().draw();
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
            initSupervisorPickersListTable();
            initSupervisorDriversListTable();
            initSupervisorSaleOrderTable();
        },
        pickerViewPage: function(hostUrl) {
            initSupervisorPickerOrderListTable();
        },
        driverViewPage: function(hostUrl) {
            initSupervisorDriverOrderListTable();
        },
        orderViewPage: function(hostUrl) {

        },
    };

}();
