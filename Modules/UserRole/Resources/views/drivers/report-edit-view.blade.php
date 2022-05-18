@extends('base::layouts.mt-main')

@section('page-title') <?= $pageTitle; ?> @endsection
@section('page-sub-title') <?= $pageSubTitle; ?> @endsection

@section('content')

    <div class="row">
        <div class="col-md-12">

            <div class="card card-custom gutter-b">

                <div class="card-header flex-wrap border-0 pt-6 pb-0">
                    <div class="card-title">
                        <h3 class="card-label">{{ $pageSubTitle }}</h3>
                        <span class="d-block text-muted pt-2 font-size-sm"></span>
                    </div>
                    <div class="card-toolbar">
                        <div class="col text-right">
                            <a href="{{ url()->previous() }}" class="btn btn-outline-primary">
                                <i class="flaticon2-back"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <form class="form" id="driver_report_single_order_edit_form" action="{{ url('/userrole/driver-collection-edit-save/' . $saleOrderObj->id) }}" method="POST">

                    @csrf

                    <div class="card-body">

                        <div class="form-group row mt-4">
                            <label  class="col-3 col-form-label text-right">Order #</label>
                            <label  class="col-6 col-form-label text-left">{{ $saleOrderObj->increment_id }}</label>
                        </div>

                        <div class="form-group row mt-4">
                            <label  class="col-3 col-form-label text-right">Order Status</label>
                            <label  class="col-6 col-form-label text-left">{{ $allAvailableStatuses[$saleOrderObj->order_status] }}</label>
                        </div>

                        <div class="form-group row mt-4">
                            <label  class="col-3 col-form-label text-right">Payment Method</label>
                            <label  class="col-6 col-form-label text-left">{{ $paymentMethodTitle }}</label>
                        </div>

                        <div class="form-group row mt-4">
                            <label  class="col-3 col-form-label text-right">Payment Status</label>
                            <label  class="col-6 col-form-label text-left">{{ ucwords($paymentStatus) }}</label>
                        </div>

                        <div class="form-group row mt-4">
                            <label  class="col-3 col-form-label text-right">Order Amount</label>
                            <label  class="col-6 col-form-label text-left">{{ $totalOrderValue . ' ' . $saleOrderObj->order_currency }}</label>
                        </div>

                        <div class="form-group row mt-4">
                            <label  class="col-3 col-form-label text-right">Order Amount Paid</label>
                            <label  class="col-6 col-form-label text-left">{{ $totalCollectedAmount . ' ' . $saleOrderObj->order_currency }}</label>
                        </div>

                        <div class="form-group row mt-4">
                            <label  class="col-3 col-form-label text-right">Initial Pay</label>
                            <label  class="col-6 col-form-label text-left">{{ $initialPaidValue . ' ' . $saleOrderObj->order_currency }}</label>
                        </div>

                        @foreach($collectionMethodList as $methodEl)
                            <div class="form-group row">
                                <label  class="col-3 col-form-label text-right">{{ ucwords($methodEl) . ' Collected' }}</label>
                                <div class="col-6">
                                    <input type="text" class="form-control amount_collected_input" id="amount_collected_{{ $methodEl }}" name="collections[{{ $methodEl }}]" placeholder="Enter Amount" value="{{ $amountCollectionData[$methodEl] }}"/>
                                    <span class="form-text text-muted">Please enter the amount collected using '{{ ucwords($methodEl) }}' method.</span>
                                </div>
                            </div>
                        @endforeach

                    </div>

                    <div class="card-footer text-right">
                        <button type="submit" id="driver_report_single_order_edit_save_btn" class="btn btn-primary font-weight-bold mr-2">
                            <i class="la la-save"></i>Save Collection
                        </button>
                        <button type="button" id="driver_report_single_order_edit_cancel_btn" class="btn btn-light-primary font-weight-bold">Cancel</button>
                    </div>

                </form>

            </div>

        </div>
    </div>

@endsection

@section('custom-js-section')

    <script src="{{ asset('js/role-drivers.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            RoleDriversCustomJsBlocks.reportEditViewPage('{{ url('/') }}');
        });
    </script>

@endsection
