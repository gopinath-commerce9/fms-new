"use strict";
var AdminCustomJsBlocks = function() {

    var initApiDeliveryDateRangePicker = function () {
        var apiDRPicker = $('#api_channel_dates').daterangepicker({
            buttonClasses: ' btn',
            applyClass: 'btn-primary',
            cancelClass: 'btn-secondary',
            locale: {
                format: 'DD/MM/YYYY'
            }
        }, function(start, end, label) {
            $('input#api_channel_date_start').val(start.format('YYYY-MM-DD'));
            $('input#api_channel_date_end').val(end.format('YYYY-MM-DD'));
        });
        apiDRPicker.on('show.daterangepicker', function(ev, picker) {
            //do something, like clearing an input
            $('input#api_channel_date_start').val(picker.startDate.format('YYYY-MM-DD'));
            $('input#api_channel_date_end').val(picker.endDate.format('YYYY-MM-DD'));
        });
    };

    var fetchApiOrdersFromServer = function () {
        $('#fetch_api_orders_form').on('submit', function(e){
            e.preventDefault();
            var data = $(this).serialize();
            $.ajax({
                url: $(this).attr('action'),
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
                }
            });
        });
    };

    var fetchApiIndividualOrdersFromServer = function () {
        $('#fetch_api_individual_orders_form').on('submit', function(e){
            e.preventDefault();
            var data = $(this).serialize();
            $.ajax({
                url: $(this).attr('action'),
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
                }
            });
        });
    };

    var initFilterDeliveryDateRangePicker = function () {
        var filterDRPicker = $('#delivery_date_range_filter').daterangepicker({
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
        filterDRPicker.on('show.daterangepicker', function(ev, picker) {
            //do something, like clearing an input
            $('input#delivery_date_start_filter').val(picker.startDate.format('YYYY-MM-DD'));
            $('input#delivery_date_end_filter').val(picker.endDate.format('YYYY-MM-DD'));
        });
    };

    var saleOrderSalesBarChartSetter = function () {
        var chart = new ApexCharts(document.querySelector("#sale_orders_sales_bar_chart"), {
            series: [],
            chart: {
                type: 'bar',
                height: 400,
                stacked: true,
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '50%'
                },
            },
            stroke: {
                width: 1,
                colors: ['#fff']
            },
            title: {
                text: 'Sale Order Sales'
            },
            xaxis: {
                title: {
                    text: 'Sale Order Total Amount'
                },
                categories: [],
                labels: {
                    formatter: function (val) {
                        return val
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Delivery Date(s)'
                },
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val
                    }
                }
            },
            fill: {
                opacity: 1
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                offsetX: 40
            },
            noData: {
                text: 'No Data Found!'
            }
        });
        chart.render();
        return chart;
    };

    var saleOrderStatusBarChartSetter = function () {
        var chart = new ApexCharts(document.querySelector("#sale_orders_status_bar_chart"), {
            series: [],
            chart: {
                type: 'bar',
                height: 400,
                stacked: true,
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '50%'
                },
            },
            stroke: {
                width: 1,
                colors: ['#fff']
            },
            title: {
                text: 'Sale Order Status'
            },
            xaxis: {
                title: {
                    text: 'Number Of Orders'
                },
                categories: [],
                labels: {
                    formatter: function (val) {
                        return val
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Delivery Date(s)'
                },
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + " Order(s)"
                    }
                }
            },
            fill: {
                opacity: 1
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                offsetX: 40
            },
            noData: {
                text: 'No Data Found!'
            }
        });
        chart.render();
        return chart;
    };

    var getSalesChartData = function (chartObj) {
        var targetForm = $('#fetch_admin_sale_orders_form');
        $('#action').val('sales_chart');
        var regionValues = '';
        var zoneValues = '';
        $.each(targetForm.serializeArray(), function(key, val) {
            if (val.name === 'emirates_filter') {
                regionValues = regionValues + ((regionValues === '') ? '' : ',') + val.value;
            } else if (val.name === 'zone_filter') {
                zoneValues = zoneValues + ((zoneValues === '') ? '' : ',') + val.value;
            }
        });
        $('#emirates_region').val(regionValues);
        $('#region_zone').val(zoneValues);
        var formData = targetForm.serializeArray();
        $.ajax({
            url: targetForm.attr('action'),
            method: targetForm.attr('method'),
            data: formData,
            beforeSend: function() {
                KTApp.block('#sale_order_sales_chart_card_row', {
                    overlayColor: '#000000',
                    state: 'danger',
                    message: 'Please wait...'
                });
            },
            success: function(data){
                KTApp.unblock('#sale_order_sales_chart_card_row');
                chartObj.updateOptions({
                    series: data.series,
                    xaxis: {
                        categories: data.xaxis
                    }
                });
            }
        });
    };

    var getStatusChartData = function (chartObj) {
        var targetForm = $('#fetch_admin_sale_orders_form');
        $('#action').val('status_chart');
        var regionValues = '';
        var zoneValues = '';
        $.each(targetForm.serializeArray(), function(key, val) {
            if (val.name === 'emirates_filter') {
                regionValues = regionValues + ((regionValues === '') ? '' : ',') + val.value;
            } else if (val.name === 'zone_filter') {
                zoneValues = zoneValues + ((zoneValues === '') ? '' : ',') + val.value;
            }
        });
        $('#emirates_region').val(regionValues);
        $('#region_zone').val(zoneValues);
        var formData = targetForm.serializeArray();
        $.ajax({
            url: targetForm.attr('action'),
            method: targetForm.attr('method'),
            data: formData,
            beforeSend: function() {
                KTApp.block('#sale_order_status_chart_card_row', {
                    overlayColor: '#000000',
                    state: 'danger',
                    message: 'Please wait...'
                });
            },
            success: function(data){
                KTApp.unblock('#sale_order_status_chart_card_row');
                chartObj.updateOptions({
                    series: data.series,
                    xaxis: {
                        categories: data.xaxis
                    }
                });
            }
        });
    };

    $.fn.dataTable.Api.register('column().title()', function() {
        return $(this.header()).text().trim();
    });

    var select2ElementsInitiator = function () {

        $('#emirates_filter').select2({
            placeholder: "Select Emirate Regions",
        });

        $('#zone_filter').select2({
            placeholder: "Select Zones",
        });

    };

    var initAdminSaleOrderTable = function(saleOrderSalesChart, saleOrderStatusChart) {

        var table = $('#admin_order_filter_table');
        var targetForm = $('form#fetch_admin_sale_orders_form');
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

                    var regionValues = '';
                    var zoneValues = '';
                    $.each(targetForm.serializeArray(), function(key, val) {
                        if (val.name === 'action') {
                            d[val.name] = 'datatable';
                        } else {
                            if (val.name === 'emirates_filter') {
                                regionValues = regionValues + ((regionValues === '') ? '' : ',') + val.value;
                            } else if (val.name === 'zone_filter') {
                                zoneValues = zoneValues + ((zoneValues === '') ? '' : ',') + val.value;
                            } else  {
                                d[val.name] = val.value;
                            }
                        }
                    });

                    d['emirates_region'] = regionValues;
                    d['region_zone'] = zoneValues;

                    d['columnsDef'] = [
                        'incrementId', 'channel', 'region', 'zone', 'customerName', 'customerAddress', 'deliveryDate', 'deliveryTimeSlot', 'deliveryPicker',
                        'deliveryPickerTime', 'deliveryDriver', 'deliveryDriverTime', 'orderStatus', 'actions'
                    ];
                },
            },
            columns: [
                {data: 'incrementId', className: 'text-wrap'},
                {data: 'channel', className: 'text-wrap'},
                {data: 'region', className: 'text-wrap'},
                {data: 'zone', className: 'text-wrap'},
                {data: 'customerName', className: 'text-wrap'},
                {data: 'customerAddress', className: 'text-wrap'},
                {data: 'deliveryDate', className: 'text-wrap'},
                {data: 'deliveryTimeSlot', className: 'text-wrap'},
                {data: 'deliveryPicker', className: 'text-wrap'},
                {data: 'deliveryPickerTime', className: 'text-wrap'},
                {data: 'deliveryDriver', className: 'text-wrap'},
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
                targets: 12,
                title: 'Status',
                orderable: true,
                render: function(data, type, full, meta) {
                    return '<span class="label label-lg font-weight-bold label-light-primary label-inline">' + data + '</span>';
                },
            }],
        });

        $('#filter_admin_order_filter_btn').on('click', function(e) {
            e.preventDefault();
            getSalesChartData(saleOrderSalesChart);
            getStatusChartData(saleOrderStatusChart);
            dataTable.table().draw();
        });
        $('#filter_admin_order_reset_btn').on('click', function(e) {
            e.preventDefault();
            $('.datatable-input').each(function() {
                $(this).val('');
            });
            $('.datatable-input-multiselect').each(function() {
                $(this).val('').trigger('change');
            });
            $('.datatable-date-input').each(function() {
                var d = new Date();
                var strDate = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();
                $(this).val(strDate);
            });
            getSalesChartData(saleOrderSalesChart);
            getStatusChartData(saleOrderStatusChart);
            dataTable.table().draw();
        });

    };

    var getRealData = function(hostUrl, orderIds, token) {
        $.ajax({
            url: hostUrl + "/admin/get-vendor-status",
            type: "POST",
            data: { orderids: orderIds, _token: token },
            dataType: "json",
            success: function (response) {
                $.each(response, function(index,value) {
                    if(value.indexOf('new') >=0 || value.indexOf('in_process') >=0) {
                        var status= '<span style="background:yellow;color:#000;padding:6px">Under Process</span>';;
                    } else if(value.indexOf('finished_with_missing') >=0) {
                        var status= '<span style="background:red;color:#fff;padding:6px">Missing</span>';
                    } else {
                        var status= '<span style="background:green;color:#fff;padding:6px">Finished</span>';
                    }
                    $('#vendor_'+index).html(status);
                });
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
        indexPage: function(hostUrl){
            initApiDeliveryDateRangePicker();
            initFilterDeliveryDateRangePicker();
            select2ElementsInitiator();
            var saleOrderSalesChart = saleOrderSalesBarChartSetter();
            var saleOrderStatusChart = saleOrderStatusBarChartSetter();
            getSalesChartData(saleOrderSalesChart);
            getStatusChartData(saleOrderStatusChart);
            initAdminSaleOrderTable(saleOrderSalesChart, saleOrderStatusChart);
            fetchApiOrdersFromServer();
            fetchApiIndividualOrdersFromServer();
        },
        deliveryDetailsPage: function(hostUrl, orderIds, token) {
            $( document ).ready(function() {
                setInterval(function () {getRealData(hostUrl, orderIds, token)}, 5000);
            });
        },
        orderViewPage: function(hostUrl, orderId) {
            resyncOrderData(orderId);
        },
    };

}();
