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
                            <h3 class="card-label">Order Filter</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <form name="filter_item_picklist_form" id="filter_item_picklist_form" action="{{ url('/sales/filter-picklist') }}" method="POST">
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
                                            <select class="form-control datatable-input" id="order_status_filter" name="order_status_filter" multiple>
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
                                                <option value="" >Select a Time Slot</option>
                                                @foreach($deliveryTimeSlots as $deliveryEl)
                                                    @if(trim($deliveryEl) != '')
                                                        <option value="{{ $deliveryEl }}" >{{ $deliveryEl }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4 text-right">
                                            <select class="form-control datatable-input" id="product_category_filter" name="product_category_filter" multiple>
                                                @foreach($productCategories as $categoryKey => $categoryEl)
                                                    <option value="{{ $categoryKey }}" >{{ $categoryEl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-4">

                                        </div>
                                        <div class="col-lg-4">

                                        </div>
                                        <div class="col-lg-4 text-right">
                                            <input type="hidden" name="filter_action" id="filter_action" value="datatable" />
                                            <input type="hidden" name="order_status_values" id="order_status_values" value="" />
                                            <input type="hidden" name="product_category_values" id="product_category_values" value="" />
                                            <button type="button" id="filter_item_picklist_filter_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="la la-search"></i>Search</span>
                                            </button>
                                            <button type="button" id="filter_item_picklist_reset_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="la la-close"></i>Reset</span>
                                            </button>
                                            <button type="button" id="filter_item_picklist_pdf_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="la la-print"></i>Print</span>
                                            </button>
                                        </div>
                                    </div>

                                </form>

                            </div>

                        </div>

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="item_picklist_filter_table_area">
                                    <table class="table table-bordered" id="item_picklist_filter_table">

                                        <thead>
                                            <tr>
                                                <th>Delivery Date</th>
                                                <th>Delivery TimeSlot</th>
                                                <th>Order Id</th>
                                                <th>Product Type</th>
                                                <th>Product SKU</th>
                                                <th>Product Name</th>
                                                <th>Quantity</th>
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

    <script src="{{ asset('js/sales.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            SalesCustomJsBlocks.picklistPage('{{ url('/') }}');
        });
    </script>

@endsection