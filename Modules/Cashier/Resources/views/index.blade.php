@extends('base::layouts.mt-main')

@section('page-title') <?= $pageTitle; ?> @endsection
@section('page-sub-title') <?= $pageSubTitle; ?> @endsection

@section('content')

    <div class="card card-custom">
        <div class="row border-bottom mb-7">

            <div class="col-md-6">
                <div class="card card-custom">
                    <form id="scan-order-number-form" name="scan-order-number-form" action="{{ url('/cashier/find-order') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group mb-8">
                                <div class="form-group row">
                                    <label  class="col-2 col-form-label">Scan Order Number</label>
                                    <div class="col-8">
                                        <input class="form-control" type="text" value="" placeholder="Order Number" id="order_number" name="order_number" />
                                    </div>
                                    <div class="col-2">
                                        <button type="submit" class="btn btn-primary mr-2" name="scan-order-number-form-submit-btn" id="scan-order-number-form-submit-btn">Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom">
                    <form id="scan-order-item-form" name="scan-order-item-form" action="{{ url('/cashier/find-order-item') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group mb-8">
                                <div class="form-group row">
                                    <label  class="col-2 col-form-label">Scan Order Item</label>
                                    <div class="col-8">
                                        <input class="form-control" type="hidden" value="" id="item_order_id" name="item_order_id" />
                                        <input class="form-control" type="hidden" value="0" id="order_item_rescan" name="order_item_rescan" />
                                        <input class="form-control" type="text" value="" placeholder="Order Item Barcode" id="order_item_barcode" name="order_item_barcode" />
                                    </div>
                                    <div class="col-2">
                                        <button type="submit" class="btn btn-primary mr-2" name="scan-order-item-form-submit-btn" id="scan-order-item-form-submit-btn">Search</button>
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
                            <h3 class="card-label">Sale Order Details</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7" id="sale-order-details-main-area">

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
                            <h3 class="card-label">Sale Order Items</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7" id="sale-order-items-main-area">

                    </div>

                </div>

            </div>
        </div>

    </div>

@endsection

@section('custom-js-section')

    <script src="{{ asset('js/cashier.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            CashierCustomJsBlocks.dashboardPage('{{ url('/') }}', '{{ csrf_token() }}');
        });
    </script>

@endsection
