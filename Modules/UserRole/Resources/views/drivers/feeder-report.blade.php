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
                            <h3 class="card-label">Feeder Drivers Report Filter</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <form name="filter_feeder_report_form" id="filter_feeder_report_form" action="{{ url('/userrole/feeders-report-filter') }}" method="POST">
                                    @csrf

                                    <div class="form-group row">
                                        <div class="col-lg-4">
                                            <select class="form-control datatable-input" id="emirates_region" name="emirates_region" >
                                                <option value="" >Select a Region</option>
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
                                            <select class="form-control datatable-input" id="driver_filter" name="driver_filter" multiple>
                                                @if(count($drivers->mappedUsers) > 0)
                                                    @foreach($drivers->mappedUsers as $userEl)
                                                        @if($userEl->pivot->is_feeder_driver == '1')
                                                            <option value="{{ $userEl->id }}" >{{ $userEl->name }}</option>
                                                        @endif
                                                    @endforeach
                                                @endif
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
                                            <input type="hidden" name="driver_values" id="driver_values" value="" />
                                            <button type="button" id="filter_feeder_report_filter_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="la la-search"></i>Search</span>
                                            </button>
                                            <button type="button" id="filter_feeder_report_reset_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="la la-close"></i>Reset</span>
                                            </button>
                                            <button type="button" id="filter_feeder_report_excel_btn" class="btn btn-primary btn-lg mr-2">
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
                            <h3 class="card-label">Feeder Drivers Report Result</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="feeder_report_filter_table_area">
                                    <table class="table table-bordered table-hover" id="feeder_report_filter_table">

                                        <thead>
                                            <tr>
                                                <th># Order Id</th>
                                                <th>Feeder(s) Involved</th>
                                                <th>Channel</th>
                                                <th>Emirates</th>
                                                <th>Customer Name</th>
                                                <th>Customer Phone</th>
                                                <th>Order Delivery Date</th>
                                                <th>Driver Delivery Date</th>
                                                <th>Payment Method</th>
                                                <th>Collection Verified</th>
                                                <th>Initial Pay</th>
                                                <th>Amount Collected</th>
                                                <th>Total Collected</th>
                                                <th>Total Paid</th>
                                                <th>Order Total</th>
                                                <th>Payment Status</th>
                                                <th>Collection Verified At</th>
                                                <th>Driver</th>
                                                <th>Delivered At</th>
                                                <th>Status</th>
                                                <th>Address</th>
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
            RoleDriversCustomJsBlocks.feederReportPage('{{ url('/') }}', '{{ csrf_token() }}');
        });
    </script>

@endsection
