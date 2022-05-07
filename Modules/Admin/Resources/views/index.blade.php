@extends('base::layouts.mt-main')

@section('page-title') <?= $pageTitle; ?> @endsection
@section('page-sub-title') <?= $pageSubTitle; ?> @endsection

@section('content')

    <div class="card card-custom">
        <div class="row border-bottom mb-7">

            <?php /* ?>
            <div class="col-md-6">
                <div class="card card-custom">
                    <form name="searchorder" action="{{ url('/admin/fetch-channel-orders') }}" method="POST" id="fetch_api_orders_form">
                        @csrf
                        <div class="card-body">
                            <div class="form-group mb-8">
                                <div class="form-group row">
                                    <div class="col-4">
                                        <select class="form-control" id="api_channel" name="api_channel" >
                                            @foreach($availableApiChannels as $apiChannel)
                                                <option value="{{ $apiChannel['id'] }}">
                                                    {{ $apiChannel['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <input type='text' class="form-control" name="api_channel_dates" id="api_channel_dates" readonly placeholder="Select Order Placed Date Range" type="text"/>
                                        <input  type="hidden" value="{{ date('Y-m-d') }}" id="api_channel_date_start" name="api_channel_date_start" />
                                        <input  type="hidden" value="{{ date('Y-m-d') }}" id="api_channel_date_end" name="api_channel_date_end" />
                                    </div>
                                    <div class="col-4">
                                        <button type="submit" class="btn btn-primary mr-2">Fetch Orders From Server</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php */ ?>

            <div class="col-md-6">
                <div class="card card-custom">
                    <form name="searchIndividualOrder" action="{{ url('/admin/fetch-channel-individual-orders') }}" method="POST" id="fetch_api_individual_orders_form">
                        @csrf
                        <div class="card-body">
                            <div class="form-group mb-8">
                                <div class="form-group row">
                                    <div class="col-4">
                                        <select class="form-control" id="api_channel" name="api_channel" >
                                            @foreach($availableApiChannels as $apiChannel)
                                                <option value="{{ $apiChannel['id'] }}">
                                                    {{ $apiChannel['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <input type='text' class="form-control" name="api_channel_order_numbers" id="api_channel_order_numbers" placeholder="Give Individual Order Number" />
                                    </div>
                                    <div class="col-4">
                                        <button type="submit" class="btn btn-primary mr-2">Fetch Order From Server</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom">
                    <form name="searchorder" action="{{ url('/admin/find-order') }}" method="POST">
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

                <div class="card card-custom gutter-b">

                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="card-label">Sale Orders Filters</h3>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="row">
                            <div class="col col-12">
                                <form name="salesChartForm" action="{{ url('/admin/filter-orders') }}" method="POST" id="fetch_admin_sale_orders_form">
                                    @csrf
                                    <div class="form-group mb-8">
                                        <div class="form-group row">
                                            <div class="col-4">
                                                <select class="form-control datatable-input" id="api_channel_filter" name="api_channel_filter" >
                                                    <option value="">All Channels</option>
                                                    @foreach($availableApiChannels as $apiChannel)
                                                        <option value="{{ $apiChannel['id'] }}">
                                                            {{ $apiChannel['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-control datatable-input" id="emirates_region_filter" name="emirates_region_filter" >
                                                    <option value="">All Emirates</option>
                                                    @foreach($emirates as $key => $emirate)
                                                        <option value="{{ $key }}">
                                                            {{ $emirate }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-lg-4">
                                                <select class="form-control datatable-input" id="order_status_filter" name="order_status_filter" >
                                                    <option value="" >All Order Statuses</option>
                                                    @foreach($availableStatuses as $statusKey => $statusEl)
                                                        <option value="{{ $statusKey }}" >{{ $statusEl }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-4">
                                                <input type='text' class="form-control" name="delivery_date_range_filter" id="delivery_date_range_filter" readonly placeholder="Select Delivery Date Range" type="text"/>
                                                <input  type="hidden" class="datatable-date-input" value="{{ date('Y-m-d') }}" id="delivery_date_start_filter" name="delivery_date_start_filter" />
                                                <input  type="hidden" class="datatable-date-input" value="{{ date('Y-m-d') }}" id="delivery_date_end_filter" name="delivery_date_end_filter" />
                                            </div>
                                            <div class="col-lg-4">
                                                <select class="form-control datatable-input" id="delivery_slot_filter" name="delivery_slot_filter" >
                                                    <option value="" >All Time Slots</option>
                                                    @foreach($deliveryTimeSlots as $deliveryEl)
                                                        <option value="{{ $deliveryEl }}" >{{ $deliveryEl }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-4 text-right">
                                                <button type="button" id="filter_admin_order_filter_btn" class="btn btn-primary btn-lg mr-2">
                                                    <span><i class="la la-search"></i>Search</span>
                                                </button>
                                                <button type="button" id="filter_admin_order_reset_btn" class="btn btn-primary btn-lg mr-2">
                                                    <span><i class="la la-close"></i>Reset</span>
                                                </button>
                                            </div>
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

                <div class="card card-custom gutter-b">

                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="card-label">Sale Orders List</h3>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col col-12">

                                <div class="table-responsive text-center" id="admin_order_filter_table_area">
                                    <table class="table table-bordered" id="admin_order_filter_table">

                                        <thead>
                                        <tr>
                                            <th># Order Id</th>
                                            <th>Channel</th>
                                            <th>Emirates</th>
                                            <th>Customer Name</th>
                                            <th>Customer Address</th>
                                            <th>Delivery Date</th>
                                            <th>Delivery Schedule Interval</th>
                                            <th>Picker</th>
                                            <th>Picked At</th>
                                            <th>Driver</th>
                                            <th>Delivered At</th>
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

    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            AdminCustomJsBlocks.indexPage('{{ url('/') }}');
        });
    </script>

@endsection
