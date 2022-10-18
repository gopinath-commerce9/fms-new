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
                            <h3 class="card-label">Picker Detailed Report</h3>
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

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="role_picker_report_view_table_area">
                                    <table class="table table-bordered" id="role_picker_report_view_table">

                                        <thead>
                                            <tr>
                                                <th>Picker Id</th>
                                                <th>Picker Name</th>
                                                <th>Order Number</th>
                                                <th>Order Delivery Date</th>
                                                <th>Order Assigned Date</th>
                                                <th>Order Assigned At</th>
                                                <th>Order Assigned By</th>
                                                <th>Emirates</th>
                                                <th>Name</th>
                                                <th>Contact</th>
                                                <th>Order Status</th>
                                                <th>Address</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>

                                        <tbody>

                                        @if(count($filteredOrderStats) > 0)
                                            @foreach($filteredOrderStats as $statEl)
                                                <?php

                                                    $roleUrlFragment = '';
                                                    if (!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_ADMIN)) {
                                                        $roleUrlFragment = 'admin';
                                                    } elseif (!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_SUPERVISOR)) {
                                                        $roleUrlFragment = 'supervisor';
                                                    } elseif (!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_PICKER)) {
                                                        $roleUrlFragment = 'picker';
                                                    } elseif (!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_DRIVER)) {
                                                        $roleUrlFragment = 'driver';
                                                    }

                                                ?>

                                                <tr>
                                                    <td class="text-wrap">{{ $statEl['pickerId'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['picker'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['orderNumber'] }}</td>
                                                    <td class="text-wrap">{{ $serviceHelper->getFormattedTime($statEl['orderDeliveryDate'], 'd-m-Y') }}</td>
                                                    <td class="text-wrap">{{ $serviceHelper->getFormattedTime($statEl['pickerAssignedDate'], 'd-m-Y') }}</td>
                                                    <td class="text-wrap">{{ $serviceHelper->getFormattedTime($statEl['pickerAssignedAt'], 'F d, Y, h:i:s A') }}</td>
                                                    <td class="text-wrap">{{ $statEl['pickerAssignerName'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['emirates'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['customerName'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['customerContact'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['orderStatus'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['shippingAddress'] }}</td>
                                                    <td nowrap="nowrap">
                                                        <a href="{{ url('/' . $roleUrlFragment . '/order-view/' . $statEl['orderRecordId']) }}" target="_blank" class="btn btn-sm btn-primary mr-2 driver-report-single-order-view-btn" data-order-id="{{ $statEl['orderRecordId'] }}" data-order-number="{{ $statEl['orderNumber'] }}" title="View Order">
                                                            View
                                                        </a>
                                                    </td>
                                                </tr>

                                            @endforeach
                                        @else
                                            <tr><td colspan="13">No Picker Activity/Assignments data found!</td></tr>
                                        @endif

                                        </tbody>

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

    <script src="{{ asset('js/role-pickers.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            RolePickersCustomJsBlocks.reportViewPage('{{ url('/') }}', '{{ csrf_token() }}');
        });
    </script>

@endsection
