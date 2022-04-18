"use strict";
var DriverCustomJsBlocks = function() {

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

    var initDriverSaleOrderTable = function() {

        var table = $('#driver_order_filter_table');
        var targetForm = $('form#filter_driver_order_form');
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
                        d[val.name] = val.value;
                    });
                    d['columnsDef'] = [
                        'incrementId', 'channel', 'region', 'customerName', 'customerAddress', 'deliveryDate', 'deliveryTimeSlot',
                        'deliveryPickerTime', 'deliveryDriverTime', 'orderStatus', 'actions'
                    ];
                },
            },
            columns: [
                {data: 'incrementId', className: 'text-wrap'},
                {data: 'channel', className: 'text-wrap'},
                {data: 'region', className: 'text-wrap'},
                {data: 'customerName', className: 'text-wrap'},
                {data: 'customerAddress', className: 'text-wrap'},
                {data: 'deliveryDate', className: 'text-wrap'},
                {data: 'deliveryTimeSlot', className: 'text-wrap'},
                {data: 'deliveryPickerTime', className: 'text-wrap'},
                {data: 'deliveryDriverTime', className: 'text-wrap'},
                {data: 'orderStatus', className: 'text-nowrap'},
                {data: 'actions', className: 'text-wrap', responsivePriority: -1},
            ],
            columnDefs: [{
                targets: -1,
                title: 'Actions',
                orderable: false,
                render: function(data, type, full, meta) {
                    return '<a href="' + data + '" target="_blank">View Order</a>';
                },
            }, {
                targets: 9,
                title: 'Status',
                orderable: true,
                render: function(data, type, full, meta) {
                    return '<span class="label label-lg font-weight-bold label-light-primary label-inline">' + data + '</span>';
                },
            }],
        });

        $('button#filter_driver_order_filter_btn').on('click', function(e) {
            e.preventDefault();
            dataTable.table().draw();
        });

        $('button#filter_driver_order_reset_btn').on('click', function(e) {
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
            initDriverSaleOrderTable();
        },
        orderViewPage: function(hostUrl) {

        },
    };

}();
