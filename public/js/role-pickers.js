"use strict";
var RolePickersCustomJsBlocks = function() {

    var initRolePickersListTable = function() {
        var table = $('#role_picker_list_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [25, 50, 100, 200],
            pageLength: 25,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    var initRolePickerOrderListTable = function() {
        var table = $('#picker_view_orders_table');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [50, 100, 200],
            pageLength: 50,
            order: [[0, 'asc']],
            columnDefs: []
        });
    };

    var initPickerReportDateRangePicker = function () {
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

    var initPickerSaleOrderReportTable = function() {

        var table = $('#picker_report_filter_table');
        var targetForm = $('form#filter_picker_report_form');
        var dataTable = table.DataTable({
            responsive: true,
            dom: `<'row'<'col-sm-12'tr>>
			<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,
            lengthMenu: [25, 50, 100, 200],
            pageLength: 25,
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
                        'pickerId', 'picker', 'active', 'date', 'assignedOrders', 'pickedOrders', 'holdedOrders'
                    ];
                },
            },
            columns: [
                {data: 'pickerId'},
                {data: 'picker'},
                {data: 'active'},
                {data: 'date'},
                {data: 'assignedOrders'},
                {data: 'pickedOrders'},
                {data: 'holdedOrders'},
            ],
            columnDefs: [{
                targets: 2,
                title: 'Active',
                orderable: true,
                render: function(data, type, full, meta) {
                    return '<span class="label label-lg font-weight-bold label-light-primary label-inline">' + data + '</span>';
                },
            }],
        });

        $('button#filter_picker_report_filter_btn').on('click', function(e) {
            e.preventDefault();
            dataTable.table().draw();
        });

        $('button#filter_picker_report_reset_btn').on('click', function(e) {
            e.preventDefault();
            $('.datatable-input').each(function() {
                $(this).val('');
            });
            dataTable.table().draw();
        });

    };

    return {
        listPage: function(hostUrl){
            initRolePickersListTable();
            initPickerReportDateRangePicker();
            initPickerSaleOrderReportTable();
        },
        viewPage: function(hostUrl) {
            initRolePickerOrderListTable();
        },
    };

}();
