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

    var select2ElementsInitiator = function () {

        $('#picker_filter').select2({
            placeholder: "Select Pickers",
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
                timeout:600000,
                data: function(d) {
                    var pickerValues = '';
                    $.each(targetForm.serializeArray(), function(key, val) {
                        if (val.name === 'filter_action') {
                            d[val.name] = 'datatable';
                        } else {
                            if (val.name === 'picker_filter') {
                                pickerValues = pickerValues + ((pickerValues === '') ? '' : ',') + val.value;
                            } else {
                                d[val.name] = val.value;
                            }
                        }
                    });
                    d['picker_values'] = pickerValues;
                    d['columnsDef'] = [
                        'pickerId', 'picker', 'active', 'date', 'totalOrders', 'pending', 'holded', 'completed', 'actions'
                    ];
                },
            },
            columns: [
                {data: 'pickerId'},
                {data: 'picker'},
                {data: 'active'},
                {data: 'date'},
                {data: 'totalOrders'},
                {data: 'pending'},
                {data: 'holded'},
                {data: 'completed'},
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

    var initRolePickersReportViewTable = function() {
        var table = $('#role_picker_report_view_table');
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

    return {
        listPage: function(hostUrl){
            initRolePickersListTable();
        },
        reportPage: function(hostUrl){
            initPickerReportDateRangePicker();
            select2ElementsInitiator();
            initPickerSaleOrderReportTable();
        },
        reportViewPage: function(hostUrl, token) {
            initRolePickersReportViewTable();
        },
        viewPage: function(hostUrl) {
            initRolePickerOrderListTable();
        },
    };

}();
