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
                            <h3 class="card-label">Driver Detailed Report</h3>
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

                                <div class="table-responsive text-center" id="role_driver_report_view_table_area">
                                    <table class="table table-bordered" id="role_driver_report_view_table">

                                        <thead>
                                            <tr>
                                                {{--<th>Driver Id</th>--}}
                                                <th>Driver Name</th>
                                                <th>Order Delivery Date</th>
                                                <th>Driver Assigned Date</th>
                                                <th>Driver Delivery Date</th>
                                                <th>Order Number</th>
                                                {{--<th>Emirates</th>
                                                <th>Name</th>
                                                <th>Contact</th>--}}
                                                <th>Order Status</th>
                                                <th>Payment Method</th>
                                                <th>Collection Verified</th>
                                                <th>Initial Pay</th>
                                                @foreach($collectionMethods as $methodEl)
                                                    <th>{{ ucwords($methodEl) . ' Collected' }}</th>
                                                @endforeach
                                                <th>Amount Collected</th>
                                                <th>Total Paid</th>
                                                <th>Order Total</th>
                                                <th>Payment Status</th>
                                                <th>Collection Verified At</th>
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

                                                    $amountCollectable = false;
                                                    $amountCollectionEditable = false;
                                                    $amountCollectionVerified = false;
                                                    $fixTotalDueArray = ['cashondelivery', 'banktransfer'];
                                                    if (in_array($statEl['paymentMethodCode'], $fixTotalDueArray)) {
                                                        $amountCollectable = true;
                                                        if ($statEl['collectionVerified'] == '0') {
                                                            $amountCollectionEditable = true;
                                                        } elseif ($statEl['collectionVerified'] == '1') {
                                                            $amountCollectionVerified = true;
                                                        }
                                                    }
                                                    if ($amountCollectable === false) {
                                                        if ($statEl['collectionVerified'] == \Modules\Sales\Entities\SaleOrder::COLLECTION_VERIFIED_YES) {
                                                            $amountCollectionVerified = true;
                                                        }
                                                    }

                                                ?>

                                                <tr>
                                                    {{--<td class="text-wrap">{{ $statEl['driverId'] }}</td>--}}
                                                    <td class="text-wrap">{{ $statEl['driver'] }}</td>
                                                    <td class="text-wrap">{{ $serviceHelper->getFormattedTime($statEl['orderDeliveryDate'], 'd-m-Y') }}</td>
                                                    <td class="text-wrap">{{ $serviceHelper->getFormattedTime($statEl['driverAssignedDate'], 'd-m-Y') }}</td>
                                                    <td class="text-wrap">{{ $serviceHelper->getFormattedTime($statEl['driverDeliveryDate'], 'd-m-Y') }}</td>
                                                    <td class="text-wrap">{{ $statEl['orderNumber'] }}</td>
                                                    {{--<td class="text-wrap">{{ $statEl['emirates'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['customerName'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['customerContact'] }}</td>--}}
                                                    <td class="text-wrap">{{ $statEl['orderStatus'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['paymentMethod'] }}</td>
                                                    <td class="text-wrap">
                                                        @if($amountCollectionVerified)
                                                            <i class="flaticon2-check-mark text-success"></i>
                                                        @else
                                                            {{ '-' }}
                                                        @endif
                                                    </td>
                                                    <td class="text-wrap">{{ $statEl['initialPay'] }}</td>
                                                    @foreach($collectionMethods as $methodEl)
                                                        <td class="text-wrap">{{ $statEl[$methodEl] }}</td>
                                                    @endforeach
                                                    <td class="text-wrap">{{ $statEl['collectedAmount'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['totalPaid'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['orderTotal'] }}</td>
                                                    <td class="text-wrap">{{ ucwords($statEl['paymentStatus']) }}</td>
                                                    <td class="text-wrap">
                                                        {{ (!is_null($statEl['collectionVerifiedAt'])) ? $serviceHelper->getFormattedTime($statEl['collectionVerifiedAt'], 'F d, Y, h:i:s A') : '-' }}
                                                    </td>
                                                    <td class="text-wrap">{{ $statEl['shippingAddress'] }}</td>
                                                    <td nowrap="nowrap">
                                                        <a href="{{ url('/' . $roleUrlFragment . '/order-view/' . $statEl['orderRecordId']) }}" target="_blank" class="btn btn-sm btn-primary mr-2 driver-report-single-order-view-btn" data-order-id="{{ $statEl['orderRecordId'] }}" data-order-number="{{ $statEl['orderNumber'] }}" title="View Order">
                                                            View
                                                        </a>
                                                        @if($amountCollectionEditable === true)
                                                            <a href="{{ url('/userrole/driver-collection-edit/' . $statEl['orderRecordId']) }}" target="_blank" class="btn btn-sm btn-primary mr-2 driver-report-single-order-edit-btn" data-order-id="{{ $statEl['orderRecordId'] }}" data-order-number="{{ $statEl['orderNumber'] }}" title="Edit Order Amount Collection">
                                                                Edit
                                                            </a>
                                                        @endif
                                                        @if($amountCollectionVerified === false)
                                                            <a href="{{ url('/userrole/driver-collection-verify/' . $statEl['orderRecordId']) }}" class="btn btn-sm btn-clean btn-primary driver-report-single-order-verify-btn" data-order-id="{{ $statEl['orderRecordId'] }}" data-order-number="{{ $statEl['orderNumber'] }}" title="Verify Order Amount Collection">
                                                                Verify
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>

                                            @endforeach
                                        @else
                                            <tr><td colspan="{{ (count($collectionMethods) + 18) }}">No Driver Activity/Assignments data found!</td></tr>
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

    <script src="{{ asset('js/role-drivers.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            RoleDriversCustomJsBlocks.reportViewPage('{{ url('/') }}', '{{ csrf_token() }}');
        });
    </script>

@endsection
