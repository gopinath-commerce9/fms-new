"use strict";
var AdminCustomJsBlocks = function() {

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
            var apiDRPicker = $('#api_channel_dates').daterangepicker({
                buttonClasses: ' btn',
                applyClass: 'btn-primary',
                cancelClass: 'btn-secondary'
            }, function(start, end, label) {
                $('input#api_channel_date_start').val(start.format('YYYY-MM-DD'));
                $('input#api_channel_date_end').val(end.format('YYYY-MM-DD'));
            });
            apiDRPicker.on('show.daterangepicker', function(ev, picker) {
                //do something, like clearing an input
                $('input#api_channel_date_start').val(picker.startDate.format('YYYY-MM-DD'));
                $('input#api_channel_date_end').val(picker.startDate.format('YYYY-MM-DD'));
            });
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
        },
        deliveryDetailsPage: function(hostUrl, orderIds, token) {
            $( document ).ready(function() {
                setInterval(function () {getRealData(hostUrl, orderIds, token)}, 5000);
            });
        },
        orderViewPage: function(hostUrl) {

        },
    };

}();
