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
                data: function(d) {
                    $.each(targetForm.serializeArray(), function(key, val) {
                        if (val.name === 'filter_action') {
                            d[val.name] = 'datatable';
                        } else {
                            d[val.name] = val.value;
                        }
                    });
                    d['columnsDef'] = [
                        'driverId', 'driver', 'active', 'date', 'assignedOrders', 'deliveryOrders',
                        'deliveredOrders', 'canceledOrders', 'actions'
                    ];
                },
            },
            columns: [
                {data: 'driverId'},
                {data: 'driver'},
                {data: 'active'},
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
        targetForm.submit();
    };

    return {
        listPage: function(hostUrl){
            initRoleDriversListTable();
            initDriverReportDateRangePicker();
            initDriverSaleOrderReportTable();
        },
        viewPage: function(hostUrl) {
            initRoleDriverOrderListTable();
        },
        reportViewPage: function(hostUrl) {
            initRoleDriversReportViewTable();
        },
    };

}();
