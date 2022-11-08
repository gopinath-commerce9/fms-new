@extends('base::layouts.mt-main')

@section('page-title') <?= $pageTitle; ?> @endsection
@section('page-sub-title') <?= $pageSubTitle; ?> @endsection

@section('content')

    <div class="card card-custom">
        <div class="row border-bottom mb-7">

            <div class="col-md-12">
                <div class="card card-custom">
                    <form name="searchorder" action="{{ url('/supervisor/find-order') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group mb-8">
                                <div class="form-group row">
                                    <label  class="col-2 col-form-label">Search Order</label>
                                    <div class="col-8">
                                        <input class="form-control" type="text" value="" placeholder="Order Number" id="order_number" name="order_number" />
                                    </div>
                                    <div class="col-2">
                                        <button type="submit" class="btn btn-primary mr-2">Search</button>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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
                            <h3 class="card-label">Order Filter</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <form name="filter_supervisor_order_form" id="filter_supervisor_order_form" action="{{ url('/supervisor/filter-order') }}" method="POST">
                                    @csrf

                                    <div class="form-group row">
                                        <div class="col-lg-4">
                                            <select class="form-control datatable-input" id="channel_filter" name="channel_filter" >
                                                <option value="" >Select a Channel</option>
                                                @foreach($availableApiChannels as $channelKey => $channelEl)
                                                    <option value="{{ $channelEl['id'] }}" >{{ $channelEl['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <select class="form-control datatable-input-multiselect" id="emirates_filter" name="emirates_filter" multiple>
                                                @foreach($emirates as $emirateKey => $emirateName)
                                                    <option value="{{ $emirateKey }}" >{{ $emirateName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <select class="form-control datatable-input-multiselect" id="zone_filter" name="zone_filter" multiple>
                                                @foreach($deliveryZones as $zoneKey => $zoneEl)
                                                    <option value="{{ $zoneEl }}" >{{ ucfirst($zoneEl) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-lg-4">
                                            <select class="form-control datatable-input" id="order_status_filter" name="order_status_filter" >
                                                <option value="" >Select an Order Status</option>
                                                @foreach($availableStatuses as $statusKey => $statusEl)
                                                    <option value="{{ $statusKey }}" >{{ $statusEl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <input type='text' class="form-control" name="delivery_date_range_filter" id="delivery_date_range_filter" readonly placeholder="Select Delivery Date Range" type="text"/>
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
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-lg-4"></div>
                                        <div class="col-lg-4"></div>
                                        <div class="col-lg-4 text-right">
                                            <input type="hidden" name="action" id="action" value="datatable" />
                                            <input type="hidden" name="emirates_region" id="emirates_region" value="" />
                                            <input type="hidden" name="region_zone" id="region_zone" value="" />
                                            <button type="button" id="filter_supervisor_order_filter_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="la la-search"></i>Search</span>
                                            </button>
                                            <button type="button" id="filter_supervisor_order_reset_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="la la-close"></i>Reset</span>
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

            <div class="col-md-6">

                <div class="card card-custom gutter-b">

                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="card-label">Sale Orders Sales Chart</h3>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="row" id="sale_order_sales_chart_card_row">
                            <div class="col col-12">
                                <div id="sale_orders_sales_bar_chart"></div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <div class="col-md-6">

                <div class="card card-custom gutter-b">

                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="card-label">Sale Orders Status Chart</h3>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="row" id="sale_order_status_chart_card_row">
                            <div class="col col-12">
                                <div id="sale_orders_status_bar_chart"></div>
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
                            <h3 class="card-label">Sale Order List</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="supervisor_order_filter_table_area">
                                    <table class="table table-bordered" id="supervisor_order_filter_table">

                                        <thead>
                                            <tr>
                                                <th># Order Id</th>
                                                <th>Emirates</th>
                                                <th>Zone</th>
                                                <th>Customer Name</th>
                                                <th>Customer Address</th>
                                                <th>Delivery Date</th>
                                                <th>Delivery Schedule Interval</th>
                                                <th>Picker</th>
                                                <th>Driver</th>
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

    <script src="{{ asset('js/supervisor.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            SupervisorCustomJsBlocks.dashboardPage('{{ url('/') }}', '{{ csrf_token() }}');
        });
    </script>

@endsection
