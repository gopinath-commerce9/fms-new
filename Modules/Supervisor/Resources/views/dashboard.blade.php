@extends('base::layouts.mt-main')

@section('page-title') <?= $pageTitle; ?> @endsection
@section('page-sub-title') <?= $pageSubTitle; ?> @endsection

@section('content')

    <div class="card card-custom">
        <div class="row border-bottom mb-7">

            <div class="col-md-6">
                <div class="card card-custom">

                    <div class="card-header flex-wrap border-0 pt-6 pb-0">
                        <div class="card-title">
                            <h3 class="card-label">Pickers</h3>
                            <span class="d-block text-muted pt-2 font-size-sm">Total <?php echo count($pickers->mappedUsers); ?> Picker(s).</span>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="supervisor_picker_list_table_area">
                                    <table class="table table-bordered" id="supervisor_picker_list_table">

                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Avatar</th>
                                                <th>Name</th>
                                                <th>EMail</th>
                                                <th>Contact</th>
                                                <th>Assigned Order Count</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>

                                        <tbody>

                                            @if(count($pickers->mappedUsers) > 0)
                                                @foreach($pickers->mappedUsers as $userEl)

                                                    <tr>
                                                        <td>{{ $userEl->id }}</td>
                                                        <td>
                                                            <span style="width: 50px;">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="symbol symbol-50 symbol-sm symbol-light-info flex-shrink-0" style="padding-left: 5%; padding-right: 5%;">
                                                                        <?php
                                                                        $userDisplayName = $userEl->name;
                                                                        $userInitials = '';
                                                                        $profilePicUrl = '';
                                                                        if (!is_null($userEl->profile_picture) && ($userEl->profile_picture != '')) {
                                                                            $dpData = json_decode($userEl->profile_picture, true);
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
                                                            </span>
                                                        </td>
                                                        <td>{{ $userDisplayName }}</td>
                                                        <td>{{ $userEl->email }}</td>
                                                        <td>{{ $userEl->contact_number }}</td>
                                                        <td>
                                                            @if($userEl->saleOrderProcessHistory && (count($userEl->saleOrderProcessHistory) > 0))
                                                                <?php $pickerOrderCount = 0; ?>
                                                                @foreach ($userEl->saleOrderProcessHistory as $processHistory)
                                                                    @if ($processHistory->action == \Modules\Sales\Entities\SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP)
                                                                        @if (
                                                                            ($processHistory->saleOrder)
                                                                            && ($processHistory->saleOrder->order_status == \Modules\Sales\Entities\SaleOrder::SALE_ORDER_STATUS_BEING_PREPARED)
                                                                        )
                                                                            <?php $pickerOrderCount++; ?>
                                                                        @endif
                                                                    @endif
                                                                @endforeach
                                                                <span class="label label-lg font-weight-bold label-light-primary label-inline">
                                                                    {{ ($pickerOrderCount > 0) ? $pickerOrderCount . ' Order(s)' : 'No Orders' }}
                                                                </span>
                                                            @else
                                                                <span class="label label-lg font-weight-bold label-light-primary label-inline">No Orders</span>
                                                            @endif
                                                        </td>
                                                        <td nowrap="nowrap">
                                                            <a href="{{ url('/supervisor/picker-view/' . $userEl->id) }}" class="btn btn-primary btn-clean mr-2" title="View Picker">
                                                                <span>View</span>
                                                            </a>
                                                        </td>
                                                    </tr>

                                                @endforeach
                                            @else
                                                <tr><td colspan="7">No Pickers found!</td></tr>
                                            @endif

                                        </tbody>

                                    </table>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom">

                    <div class="card-header flex-wrap border-0 pt-6 pb-0">
                        <div class="card-title">
                            <h3 class="card-label">Drivers</h3>
                            <span class="d-block text-muted pt-2 font-size-sm">Total <?php echo count($drivers->mappedUsers); ?> Driver(s).</span>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="supervisor_driver_list_table_area">
                                    <table class="table table-bordered" id="supervisor_driver_list_table">

                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Avatar</th>
                                                <th>Name</th>
                                                <th>EMail</th>
                                                <th>Contact</th>
                                                <th>Order Assigned</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>

                                        <tbody>

                                            @if(count($drivers->mappedUsers) > 0)
                                                @foreach($drivers->mappedUsers as $userEl)

                                                    <tr>
                                                        <td>{{ $userEl->id }}</td>
                                                        <td>
                                                            <span style="width: 50px;">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="symbol symbol-50 symbol-sm symbol-light-info flex-shrink-0" style="padding-left: 5%; padding-right: 5%;">
                                                                        <?php
                                                                        $userDisplayName = $userEl->name;
                                                                        $userInitials = '';
                                                                        $profilePicUrl = '';
                                                                        if (!is_null($userEl->profile_picture) && ($userEl->profile_picture != '')) {
                                                                            $dpData = json_decode($userEl->profile_picture, true);
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
                                                            </span>
                                                        </td>
                                                        <td>{{ $userDisplayName }}</td>
                                                        <td>{{ $userEl->email }}</td>
                                                        <td>{{ $userEl->contact_number }}</td>
                                                        <td>
                                                            @if($userEl->saleOrderProcessHistory && (count($userEl->saleOrderProcessHistory) > 0))
                                                                <?php $driverOrderCount = 0; ?>
                                                                @foreach ($userEl->saleOrderProcessHistory as $processHistory)
                                                                    @if ($processHistory->action == \Modules\Sales\Entities\SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY)
                                                                        @if (
                                                                            ($processHistory->saleOrder)
                                                                            && (
                                                                                ($processHistory->saleOrder->order_status == \Modules\Sales\Entities\SaleOrder::SALE_ORDER_STATUS_READY_TO_DISPATCH)
                                                                                || ($processHistory->saleOrder->order_status == \Modules\Sales\Entities\SaleOrder::SALE_ORDER_STATUS_OUT_FOR_DELIVERY)
                                                                            )
                                                                        )
                                                                            <?php $driverOrderCount++; ?>
                                                                        @endif
                                                                    @endif
                                                                @endforeach
                                                                <span class="label label-lg font-weight-bold label-light-primary label-inline">
                                                                    {{ ($driverOrderCount > 0) ? $driverOrderCount . ' Order(s)' : 'No Orders' }}
                                                                </span>
                                                            @else
                                                                <span class="label label-lg font-weight-bold label-light-primary label-inline">No Orders</span>
                                                            @endif
                                                        </td>
                                                        <td nowrap="nowrap">
                                                            <a href="{{ url('/supervisor/driver-view/' . $userEl->id) }}" class="btn btn-primary btn-clean mr-2" title="View Driver">
                                                                <span>View</span>
                                                            </a>
                                                        </td>
                                                    </tr>

                                                @endforeach
                                            @else
                                                <tr><td colspan="7">No Pickers found!</td></tr>
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
                                            <select class="form-control datatable-input" id="order_status_filter" name="order_status_filter" >
                                                <option value="" >Select an Order Status</option>
                                                @foreach($availableStatuses as $statusKey => $statusEl)
                                                    <option value="{{ $statusKey }}" >{{ $statusEl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-lg-4">
                                            <input type="text" class="form-control datatable-input" id="delivery_date_filter" name="delivery_date_filter" readonly placeholder="Select Delivery Date"/>
                                        </div>
                                        <div class="col-lg-4">
                                            <select class="form-control datatable-input" id="delivery_slot_filter" name="delivery_slot_filter" >
                                                <option value="" >Select a Time Slot</option>
                                                @foreach($deliveryTimeSlots as $deliveryEl)
                                                    <option value="{{ $deliveryEl }}" >{{ $deliveryEl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4 text-right">
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

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="supervisor_order_filter_table_area">
                                    <table class="table table-bordered" id="supervisor_order_filter_table">

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
            SupervisorCustomJsBlocks.dashboardPage('{{ url('/') }}');
        });
    </script>

@endsection
