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
                            <h3 class="card-label">{{ $pageSubTitle }}</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <form name="update_stock_qty_form" id="update_stock_qty_form" action="{{ url('/sales/update-product-stock-qty') }}" method="POST">
                                    @csrf

                                    <div class="card card-custom">

                                        <div class="card-body">

                                            <div class="form-group">
                                                <label>SKU:</label>
                                                <input type="text" class="form-control" placeholder="Enter SKU" required id="product_sku" name="product_sku" />
                                                <span class="form-text text-muted">Please enter SKU</span>
                                            </div>

                                        </div>

                                        <div class="card-footer">
                                            <button type="submit" class="btn btn-primary mr-2">Set As Out of Stock</button>
                                            {{--<button type="reset" class="btn btn-secondary">Cancel</button>--}}
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

@endsection

@section('custom-js-section')

    <script src="{{ asset('js/sales.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            SalesCustomJsBlocks.updateStockPage('{{ url('/') }}');
        });
    </script>

@endsection
