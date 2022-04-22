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
                                                <th>Driver Id</th>
                                                <th>Driver Name</th>
                                                <th>Order Delivery Date</th>
                                                <th>Driver Delivery Date</th>
                                                <th>Order Number</th>
                                                <th>Emirates</th>
                                                <th>Address</th>
                                                <th>Order Status</th>
                                                <th>Payment Method</th>
                                                <th>Initial Pay</th>
                                                @foreach($collectionMethods as $methodEl)
                                                    <th>{{ ucwords($methodEl) . ' Collected' }}</th>
                                                @endforeach
                                                <th>Amount Collected</th>
                                                <th>Total Paid</th>
                                                <th>Order Total</th>
                                                <th>Payment Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>

                                        <tbody>

                                        @if(count($filteredOrderStats) > 0)
                                            @foreach($filteredOrderStats as $statEl)

                                                <tr>
                                                    <td class="text-wrap">{{ $statEl['driverId'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['driver'] }}</td>
                                                    <td class="text-wrap">{{ date('d-m-Y', strtotime($statEl['orderDeliveryDate'])) }}</td>
                                                    <td class="text-wrap">{{ date('d-m-Y', strtotime($statEl['driverDeliveryDate'])) }}</td>
                                                    <td class="text-wrap">{{ $statEl['orderNumber'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['emirates'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['shippingAddress'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['orderStatus'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['paymentMethod'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['initialPay'] }}</td>
                                                    @foreach($collectionMethods as $methodEl)
                                                        <td class="text-wrap">{{ $statEl[$methodEl] }}</td>
                                                    @endforeach
                                                    <td class="text-wrap">{{ $statEl['collectedAmount'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['totalPaid'] }}</td>
                                                    <td class="text-wrap">{{ $statEl['orderTotal'] }}</td>
                                                    <td class="text-wrap">{{ ucwords($statEl['paymentStatus']) }}</td>
                                                    <td nowrap="nowrap">
                                                        @if(!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_ADMIN))
                                                            <a href="{{ url('/admin/order-view/' . $statEl['orderRecordId']) }}" target="_blank">View Order</a>
                                                        @elseif(!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_SUPERVISOR))
                                                            <a href="{{ url('/supervisor/order-view/' . $statEl['orderRecordId']) }}" target="_blank">View Order</a>
                                                        @elseif(!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_PICKER))
                                                            <a href="{{ url('/picker/order-view/' . $statEl['orderRecordId']) }}" target="_blank">View Order</a>
                                                        @elseif(!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_DRIVER))
                                                            <a href="{{ url('/driver/order-view/' . $statEl['orderRecordId']) }}" target="_blank">View Order</a>
                                                        @endif
                                                    </td>
                                                </tr>

                                            @endforeach
                                        @else
                                            <tr><td colspan="{{ (count($collectionMethods) + 15) }}">No Driver Activity data found!</td></tr>
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
            RoleDriversCustomJsBlocks.reportViewPage('{{ url('/') }}');
        });
    </script>

@endsection
