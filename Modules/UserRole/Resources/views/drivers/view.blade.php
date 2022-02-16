@extends('base::layouts.mt-main')

@section('page-title') <?= $pageTitle; ?> @endsection
@section('page-sub-title') <?= $pageSubTitle; ?> @endsection

@section('content')

    <div class="row">
        <div class="col-md-12">

            <!--begin::Card-->
            <div class="card card-custom gutter-b">

                <!--begin::Card Header-->
                <div class="card-header flex-wrap py-3">

                    <!--begin::Card Toolbar-->
                    <div class="card-toolbar">
                        <div class="col text-left">
                            <a href="{{ url()->previous() }}" class="btn btn-outline-primary">
                                <i class="flaticon2-back"></i> Back
                            </a>
                        </div>
                    </div>
                    <!--end::Card Toolbar-->

                    <!--begin::Card Toolbar-->
                    <div class="card-toolbar">

                        <!--begin::Card Tabs-->
                        <ul class="nav nav-light-info nav-bold nav-pills">

                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#driver_view_info_tab">
                                    <span class="nav-text">Info</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#driver_view_orders_tab">
                                    <span class="nav-text">Sale Orders</span>
                                </a>
                            </li>

                        </ul>
                        <!--end::Card Tabs-->

                    </div>
                    <!--end::Card Toolbar-->

                </div>
                <!--end::Card Header-->

                <!--begin::Card Body-->
                <div class="card-body">

                    <!--begin::Card Tab Content-->
                    <div class="tab-content">

                        <div class="tab-pane fade show active" id="driver_view_info_tab" role="tabpanel" aria-labelledby="driver_view_info_tab">

                            <div class="form-group row my-2">

                                <div class="col col-2 text-right">
                                    <span class="label label-xl label-dark font-weight-boldest label-inline mr-2">General Info</span>
                                </div>

                                <div class="col col-10 text-right">
                                    @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('users.update'))
                                        <a href="{{ url('/userauth/users/edit/' . $givenUserData->id) }}" class="btn btn-warning mr-2" title="Edit">
                                            <i class="flaticon2-pen"></i> Edit
                                        </a>
                                    @endif
                                    @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('users.delete'))
                                        @if(!$givenUserData->isDefaultUser())
                                            <a href="{{ url('/userauth/users/delete/' . $givenUserData->id) }}" class="btn btn-danger mr-2" title="Delete">
                                                <i class="flaticon-delete-1"></i> Delete
                                            </a>
                                        @endif
                                    @endif
                                </div>

                            </div>

                            <div class="row">

                                <div class="col col-2">

                                </div>

                                <div class="col col-10">

                                    <div class="d-flex">

                                        <div class="flex-shrink-0 mr-7">
                                            <div class="symbol symbol-50 symbol-lg-120">
                                                <?php
                                                $userDisplayName = $givenUserData->name;
                                                $userEmail = $givenUserData->email;
                                                $userContact = $givenUserData->contact_number;
                                                $userInitials = '';
                                                $profilePicUrl = '';
                                                if (!is_null($givenUserData->profile_picture) && ($givenUserData->profile_picture != '')) {
                                                    $dpData = json_decode($givenUserData->profile_picture, true);
                                                    $profilePicUrlPath = $dpData['path'];
                                                    $profilePicUrl = $serviceHelper->getUserImageUrl($profilePicUrlPath);
                                                }
                                                $userDisplayNameSplitter = explode(' ', $userDisplayName);
                                                foreach ($userDisplayNameSplitter as $userNameWord) {
                                                    $userInitials .= substr($userNameWord, 0, 1);
                                                }
                                                ?>
                                                @if ($profilePicUrl != '')
                                                    <img class="" src="{{ $profilePicUrl }}" alt="{{ $userDisplayName }}">
                                                @else
                                                    <span class="symbol-label font-size-h4 font-weight-bold">{{ strtoupper($userInitials) }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex-grow-1">

                                            <div class="d-flex align-items-center justify-content-between flex-wrap mt-2">
                                                <div class="mr-3">
                                            <span class="d-flex align-items-center text-dark text-hover-primary font-size-h5 font-weight-bold mr-3">
                                                {{ $userDisplayName }}
                                            </span>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center flex-wrap justify-content-between">
                                                <div class="flex-grow-1 font-weight-bold text-dark-50 py-2 py-lg-2 mr-5">
                                                    {{ $userEmail }}
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center flex-wrap justify-content-between">
                                                <div class="flex-grow-1 font-weight-bold text-dark-50 py-2 py-lg-2 mr-5">
                                                    {{ $userContact }}
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="tab-pane fade" id="driver_view_orders_tab" role="tabpanel" aria-labelledby="driver_view_orders_tab">

                            <div class="form-group row my-2">

                                <div class="col col-12 text-center">
                                    <span class="label label-xl label-dark font-weight-boldest label-inline mr-2">Sale Order List</span>
                                </div>

                            </div>

                            <div class="form-group row my-2">
                                @if($givenUserData->saleOrderProcessHistory && (count($givenUserData->saleOrderProcessHistory) > 0))

                                    <?php
                                    $saleOrders = [];
                                    foreach($givenUserData->saleOrderProcessHistory as $processHistory) {
                                        $saleOrders[$processHistory->saleOrder->id] = $processHistory->saleOrder;
                                    }
                                    ?>

                                    <div class="col col-12 text-center">

                                        <div  class="table-responsive">
                                            <table class="table table-bordered table-checkable" id="driver_view_orders_table">

                                                <thead>
                                                <tr>
                                                    <th># Order Id</th>
                                                    <th>Channel</th>
                                                    <th>Emirates</th>
                                                    <th>Customer Name</th>
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

                                                <tbody>

                                                @foreach($saleOrders as $orderEl)
                                                    <?php
                                                    $apiChannelId = $orderEl->channel;
                                                    $emirateId = $orderEl->region_id;
                                                    $shipAddress = $orderEl->shippingAddress;
                                                    $orderStatusId = $orderEl->order_status;
                                                    $pickerName = '';
                                                    $orderPickerId = 0;
                                                    $pickedAt = '';
                                                    $driverName = '';
                                                    $orderDriverId = 0;
                                                    $deliveredAt = '';
                                                    $deliveryPickerData = $orderEl->pickupData;
                                                    $deliveryDriverData = $orderEl->deliveryData;
                                                    if ($deliveryPickerData && (count($deliveryPickerData) > 0)) {
                                                        $pickerDetail = $deliveryPickerData[(count($deliveryPickerData) - 1)];
                                                        $pickedAt = $serviceHelper->getFormattedTime($pickerDetail->done_at, 'F d, Y, h:i:s A');
                                                        if ($pickerDetail->actionDoer) {
                                                            $orderPickerId = $pickerDetail->actionDoer->id;
                                                            $pickerName = $pickerDetail->actionDoer->name;
                                                        }
                                                    }
                                                    if ($deliveryDriverData && (count($deliveryDriverData) > 0)) {
                                                        $driverDetail = $deliveryDriverData[(count($deliveryDriverData) - 1)];
                                                        $deliveredAt = $serviceHelper->getFormattedTime($driverDetail->done_at, 'F d, Y, h:i:s A');
                                                        if ($driverDetail->actionDoer) {
                                                            $orderDriverId = $driverDetail->actionDoer->id;
                                                            $driverName = $driverDetail->actionDoer->name;
                                                        }
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td>{{ $orderEl->increment_id }}</td>
                                                        <td>{{ $availableApiChannels[$apiChannelId]['name'] }}</td>
                                                        <td>{{ $emirates[$emirateId] }}</td>
                                                        <td>{{ $shipAddress->first_name . ' ' . $shipAddress->last_name }}</td>
                                                        <td>{{ $orderEl->delivery_date }}</td>
                                                        <td>{{ $orderEl->delivery_time_slot }}</td>
                                                        <td>
                                                            @if(($orderPickerId == 0) || ($orderPickerId == $givenUserData->id))
                                                                {{ $pickerName }}
                                                            @else
                                                                <a href="{{ url('/userrole/pickers/view/' . $orderPickerId) }}" class="btn btn-primary btn-clean mr-2" title="View Picker">
                                                                    <span>{{ $pickerName }}</span>
                                                                </a>
                                                            @endif
                                                        </td>
                                                        <td>{{ $pickedAt }}</td>
                                                        <td>
                                                            @if(($orderDriverId == 0) || ($orderDriverId == $givenUserData->id))
                                                                {{ $driverName }}
                                                            @else
                                                                <a href="{{ url('/userrole/drivers/view/' . $orderDriverId) }}" class="btn btn-primary btn-clean mr-2" title="View Driver">
                                                                    <span>{{ $driverName }}</span>
                                                                </a>
                                                            @endif
                                                        </td>
                                                        <td>{{ $deliveredAt }}</td>
                                                        <td>
                                                            <span class="label label-lg font-weight-bold label-light-primary label-inline">
                                                                {{ $availableStatuses[$orderStatusId] }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if(!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_ADMIN))
                                                                <a href="{{ url('/admin/order-view/' . $orderEl->id) }}" target="_blank">View Order</a>
                                                            @elseif(!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_SUPERVISOR))
                                                                <a href="{{ url('/supervisor/order-view/' . $orderEl->id) }}" target="_blank">View Order</a>
                                                            @elseif(!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_PICKER))
                                                                <a href="{{ url('/picker/order-view/' . $orderEl->id) }}" target="_blank">View Order</a>
                                                            @elseif(!is_null($currentRole) && ($currentRole === \Modules\UserRole\Entities\UserRole::USER_ROLE_DRIVER))
                                                                <a href="{{ url('/driver/order-view/' . $orderEl->id) }}" target="_blank">View Order</a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach

                                                </tbody>

                                            </table>
                                        </div>

                                    </div>

                                @else
                                    <label class="col-12 col-form-label font-size-lg-h2 text-center">No Sale Orders yet!</label>
                                @endif
                            </div>

                        </div>

                    </div>
                    <!--end::Card Tab Content-->

                </div>
                <!--end::Card Body-->

                <!--begin::Card Footer-->
                <div class="card-footer text-right">
                    <div class="row">

                    </div>
                </div>
                <!--end::Card Footer-->

            </div>
            <!--end::Card-->

        </div>
    </div>

@endsection

@section('custom-js-section')

    <script src="{{ asset('js/supervisor.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            SupervisorCustomJsBlocks.driverViewPage('{{ url('/') }}');
        });
    </script>

@endsection
