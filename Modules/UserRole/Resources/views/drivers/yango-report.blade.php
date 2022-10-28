@extends('base::layouts.mt-main')

@section('page-title') <?= $pageTitle; ?> @endsection
@section('page-sub-title') <?= $pageSubTitle; ?> @endsection

@section('content')

    <div class="card card-custom">
        <div class="row border-bottom mb-7">

            <div class="col-md-12">
                <div class="card card-custom">

                    <div class="card-header flex-wrap border-0 pt-6 pb-0">
                        <div class="card-title">
                            <h3 class="card-label">Sale Orders Report Filter</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <form name="filter_yango_report_form" id="filter_yango_report_form" action="{{ url('/userrole/yango-logistics-report-filter') }}" method="POST">
                                    @csrf

                                    <div class="form-group row">
                                        <div class="col-lg-4">
                                            <select class="form-control datatable-input-multiselect" id="emirates_filter" name="emirates_filter" multiple>
                                                @foreach($emirates as $emirateKey => $emirateName)
                                                    <option value="{{ $emirateKey }}" >{{ $emirateName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <select class="form-control datatable-input" id="channel_filter" name="channel_filter" >
                                                <option value="" >Select a Channel</option>
                                                @foreach($availableApiChannels as $channelKey => $channelEl)
                                                    <option value="{{ $channelEl['id'] }}" >{{ $channelEl['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <select class="form-control datatable-input-multiselect" id="order_status_filter" name="order_status_filter" multiple>
                                                @foreach($availableStatuses as $statusKey => $statusEl)
                                                    <option value="{{ $statusKey }}" >{{ $statusEl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-lg-4">
                                            <input type='text' class="form-control" name="delivery_date_range_filter" id="delivery_date_range_filter" readonly placeholder="Select Date Range" type="text"/>
                                            <input  type="hidden" class="datatable-date-input" value="{{ date('Y-m-d') }}" id="delivery_date_start_filter" name="delivery_date_start_filter" />
                                            <input  type="hidden" class="datatable-date-input" value="{{ date('Y-m-d') }}" id="delivery_date_end_filter" name="delivery_date_end_filter" />
                                        </div>
                                        <div class="col-lg-4">
                                            <select class="form-control datatable-input" id="delivery_slot_filter" name="delivery_slot_filter" >
                                                <option value="" >Select a Time Slot</option>
                                                @foreach($deliveryTimeSlots as $deliveryEl)
                                                    <option value="{{ $deliveryEl }}" >{{ $deliveryEl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4 text-right">
                                            <input type="hidden" name="filter_action" id="filter_action" value="datatable" />
                                            <input type="hidden" name="emirates_region" id="emirates_region" value="" />
                                            <input type="hidden" name="order_status_values" id="order_status_values" value="" />
                                            <button type="button" id="filter_yango_report_filter_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="la la-search"></i>Search</span>
                                            </button>
                                            <button type="button" id="filter_yango_report_reset_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="la la-close"></i>Reset</span>
                                            </button>
                                            <button type="button" id="filter_yango_report_excel_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="la la-file-excel"></i>Export</span>
                                            </button>
                                        </div>
                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>

    <div class="card card-custom">
        <div class="row border-bottom mb-7">

            <div class="col-md-12">
                <div class="card card-custom">

                    <div class="card-header flex-wrap border-0 pt-6 pb-0">
                        <div class="card-title">
                            <h3 class="card-label">Sale Orders Report Result</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="yango_report_filter_table_area">
                                    <table class="table table-bordered table-hover" id="yango_report_filter_table">

                                        <thead>
                                            <tr>
                                                <th># Order Id</th>
                                                <th>Channel</th>
                                                <th>Emirates</th>
                                                <th>Latitude</th>
                                                <th>Longitude</th>
                                                <th>Order Delivery Date</th>
                                                <th>Order Time Slot</th>
                                                <th>Customer Name</th>
                                                <th>Customer Address</th>
                                                <th>Customer Phone</th>
                                                <th>Payment Method</th>
                                                <th>Order Total</th>
                                                <th>Payment Status</th>
                                                <th>COD Amount</th>
                                                <th>Delivery Note</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>

                                    </table>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>

@endsection

@section('custom-js-section')

    <script src="{{ asset('js/role-drivers.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            RoleDriversCustomJsBlocks.yangoReportPage('{{ url('/') }}', '{{ csrf_token() }}');
        });
    </script>

@endsection
