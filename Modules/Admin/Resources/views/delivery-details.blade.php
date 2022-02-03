@extends('base::layouts.mt-main')

@section('page-title') <?= $pageTitle; ?> @endsection
@section('page-sub-title') <?= $pageSubTitle; ?> @endsection

@section('content')


    <div class="row">
        <div class="col-md-12">
            <form action="{{ url('/admin/export-orderwise-items') }}" method="post" id="delivery_details_actions_form">
                @csrf
                <!--begin::Card-->
                <div class="card card-custom gutter-b">

                    <div class="card-header flex-wrap py-3">
                        <div class="card-title">
                            <h3 class="card-label">Order List
                                <span class="d-block text-muted pt-2 font-size-sm">Total <?php echo $totalRows; ?> Records</span>
                            </h3>
                        </div>
                        <div class="card-toolbar">
                            <!--begin::Dropdown-->
                            <div class="dropdown dropdown-inline mr-2">
                                <button type="submit" class="btn btn-light-primary font-weight-bolder">
                                    <span class="svg-icon svg-icon-md">
                                        <!--begin::Svg Icon | path:assets/media/svg/icons/Design/PenAndRuller.svg-->
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <rect x="0" y="0" width="24" height="24" />
                                                <path d="M3,16 L5,16 C5.55228475,16 6,15.5522847 6,15 C6,14.4477153 5.55228475,14 5,14 L3,14 L3,12 L5,12 C5.55228475,12 6,11.5522847 6,11 C6,10.4477153 5.55228475,10 5,10 L3,10 L3,8 L5,8 C5.55228475,8 6,7.55228475 6,7 C6,6.44771525 5.55228475,6 5,6 L3,6 L3,4 C3,3.44771525 3.44771525,3 4,3 L10,3 C10.5522847,3 11,3.44771525 11,4 L11,19 C11,19.5522847 10.5522847,20 10,20 L4,20 C3.44771525,20 3,19.5522847 3,19 L3,16 Z" fill="#000000" opacity="0.3" />
                                                <path d="M16,3 L19,3 C20.1045695,3 21,3.8954305 21,5 L21,15.2485298 C21,15.7329761 20.8241635,16.200956 20.5051534,16.565539 L17.8762883,19.5699562 C17.6944473,19.7777745 17.378566,19.7988332 17.1707477,19.6169922 C17.1540423,19.602375 17.1383289,19.5866616 17.1237117,19.5699562 L14.4948466,16.565539 C14.1758365,16.200956 14,15.7329761 14,15.2485298 L14,5 C14,3.8954305 14.8954305,3 16,3 Z" fill="#000000" />
                                            </g>
                                        </svg>
                                        <!--end::Svg Icon-->
                                    </span>Export
                                </button>
                            </div>
                            <!--end::Dropdown-->

                        </div>
                    </div>
                    <div class="card-body">
                        <!--begin: Datatable-->

                        <div  class="table-responsive">
                            <table class="table table-bordered table-checkable text-center">
                                <tr>
                                    <th></th>
                                    <th>Channel</th>
                                    <th>Order ID </th>
                                    <th>Customer Name</th>
                                    <th>Zone</th>
                                    <th>Area</th>
                                    <th>Customer Group</th>
                                    <th>Date</th>
                                    <th>Order Total</th>
                                    <th>Delivery Date</th>
                                    <th>Delivery Time Slot</th>
                                    <th>Status</th>
                                    <th>Vendor Status</th>
                                    <th>Action</th>
                                </tr>

                                <?php

                                foreach ($orderData as $item)
                                {

                                $orderId = $item['id'];
                                $saleOrderId = $item['order_id'];
                                $orderIds[] = $orderId;
                                $apiChannelCode = $item['channel'];
                                $apiChannel = '';
                                foreach ($availableApiChannels as $apiChannelKey => $apiChannelEl) {
                                    if ($apiChannelEl['id'] == $apiChannelCode) {
                                        $apiChannel = $apiChannelEl['name'];
                                    }
                                }
                                $orderIncrementId = $item['increment_id'];
                                $isGuest = $item['is_guest'];
                                if($isGuest){
                                    $customerName = "Guest";
                                } else {
                                    $customerName = $item['customer_firstname'] . " " . $item['customer_lastname'];
                                }

                                $dateOfOrder = $serviceHelper->getFormattedTime($item['order_created_at'], 'F d, Y, h:i:s A');
                                $area = $item['city'];
                                $zone = $item['zone_id'];

                                $amount = number_format($item['order_total'], 2) . " " . $item['order_currency'];
                                if(isset($item['delivery_date'])){
                                    $delivery_date = $item['delivery_date'];
                                    $delivery_date = date("m/d/Y", strtotime($delivery_date));
                                } else {
                                    $delivery_date = "";
                                }
                                if(isset($item['delivery_time_slot'])){
                                    $delivery_time_slot = $item['delivery_time_slot'];
                                } else {
                                    $delivery_time_slot = "";
                                }
                                $status = $item['order_status'];
                                if(array_key_exists($status, $orderStatuses)) {
                                    $status = $orderStatuses[$status];
                                }

                                $viewLink = url('/admin/order-view/' . $orderId);
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="order[]" value="{{ $orderId }}" /></td>
                                    <td>{{ $apiChannel }}</td>
                                    <td># {{ $orderIncrementId }}</td>
                                    <td>{{ $customerName }}</td>
                                    <td>{{ $zone }}</td>
                                    <td>{{ $area }}</td>
                                    <td>{{ $customerGroups[$item['customer_group_id']] }}</td>
                                    <td>{{ $dateOfOrder }}</td>
                                    <td>{{ $amount }}</td>
                                    <td>{{ $delivery_date }}</td>
                                    <td>{{ $delivery_time_slot }}</td>
                                    <td>
                                        <span class="label label-lg font-weight-bold label-light-primary label-inline">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td id="vendor_{{ $orderId }}"></td>
                                    <td><a href="{{ $viewLink }}" target="_blank">View Order</a></td>
                                </tr>

                                <?php
                                }
                                ?>
                            </table>

                        </div>
                        <div class="row">

                            <div class="col-sm-12 col-md-7 dataTables_pager">

                                <div class="dataTables_paginate paging_simple_numbers" id="kt_datatable_paginate">
                                    <ul class="pagination">
                                        <?php if( $pageNo == 1 ){ ?>
                                        <li class="paginate_button page-item previous disabled" id="kt_datatable_previous">
                                            <a href="#" aria-controls="kt_datatable" data-dt-idx="0" tabindex="0" class="page-link">
                                                <i class="ki ki-arrow-back"></i></a></li>
                                        <?php }  else { ?>
                                        <li class="paginate_button page-item previous disabled" id="kt_datatable_previous">
                                            <a href="{{ url('/admin/delivery-details') }}?region=<?php echo $region;?>&interval=<?php echo $interval;?>&date=<?php echo $date;?>&countrows=10&pageno=<?php echo $backwardPageNo = $pageNo - 1; ?>" aria-controls="kt_datatable" data-dt-idx="0" tabindex="0" class="page-link">
                                                <i class="ki ki-arrow-back"></i></a></li>
                                        <?php } ?>
                                        <?php for ($x = $startPageLink; $x <= $endPageLink; $x++)  { ?>
                                        <?php if($pageNo == $x){ ?>
                                        <li class="paginate_button page-item active">
                                            <a href="#" tabindex="0" class="page-link"><?php echo $x; ?></a>
                                        </li>
                                        <?php }  else { ?>
                                        <li class="paginate_button page-item "><a href="{{ url('/admin/delivery-details') }}?region=<?php echo $region;?>&interval=<?php echo $interval;?>&date=<?php echo $date;?>&countrows=10&pageno=<?php echo $x; ?>"><?php echo $x; ?></a></li>
                                        <?php }
                                        } ?>
                                        <?php if( $pageNo == $totalPages){ ?>
                                        <li class="paginate_button page-item next" id="kt_datatable_next">
                                            <a href="#" aria-controls="kt_datatable" data-dt-idx="6" tabindex="0" class="page-link"><i class="ki ki-arrow-next"></i></a>
                                        </li>
                                        <?php } else { ?>
                                        <li class="paginate_button page-item next" id="kt_datatable_next">
                                            <a href="{{ url('/admin/delivery-details') }}?region=<?php echo $region;?>&interval=<?php echo $interval;?>&date=<?php echo $date;?>&countrows=10&pageno=<?php echo $forwardPageNo = $pageNo + 1; ?>" aria-controls="kt_datatable" data-dt-idx="6" tabindex="0" class="page-link"><i class="ki ki-arrow-next"></i></a>
                                        </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        </div>


                        <!--end: Datatable-->
                    </div>
                </div>
                <!--end::Card-->
            </form>
        </div>
    </div>

@endsection

@section('custom-js-section')

    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            AdminCustomJsBlocks.deliveryDetailsPage('{{ url('/') }}', '{{ implode(',', $orderIds) }}', '{{ csrf_token() }}');
        });
    </script>

@endsection
