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
                            <h3 class="card-label">Regions</h3>
                        </div>
                        <div class="card-toolbar">
                            <div class="col text-right">

                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <form name="filter_sales_region_form" id="filter_sales_region_form" action="{{ url('/sales/filter-regions') }}" method="POST">
                                    @csrf

                                    <div class="form-group row">
                                        <div class="col-4">

                                        </div>
                                        <div class="col-lg-4">

                                        </div>
                                        <div class="col-lg-4 text-right">
                                            <input type="hidden" name="filter_action" id="filter_action" value="datatable" />
                                            <input type="hidden" name="region_items_selected_values" id="region_items_selected_values" value="" />
                                            <button type="button" id="regions_update_btn" class="btn btn-primary btn-lg mr-2">
                                                <span><i class="flaticon2-refresh-arrow"></i>Update From Server</span>
                                            </button>
                                            <div class="dropdown dropdown-inline mr-2" id="filter_sales_region-kerabiya_enable_dropdown_area">
                                                <button type="button" class="btn btn-light-primary font-weight-bolder dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="la la-check"></i>Enable Kerabiya
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
                                                    <ul class="nav flex-column nav-hover">
                                                        <li class="nav-item">
                                                            <a href="#" class="nav-link" id="filter_sales_region_kerabiya_enable_all_btn">
                                                                <i class="nav-icon la la-check"></i>
                                                                <span class="nav-text">Enable All</span>
                                                            </a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a href="#" class="nav-link" id="filter_sales_region_kerabiya_enable_selected_btn">
                                                                <i class="nav-icon la la-check"></i>
                                                                <span class="nav-text">Enable Selected</span>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="dropdown dropdown-inline mr-2" id="filter_sales_region-kerabiya_disable_dropdown_area">
                                                <button type="button" class="btn btn-light-primary font-weight-bolder dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="la la-times"></i>Disable Kerabiya
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
                                                    <ul class="nav flex-column nav-hover">
                                                        <li class="nav-item">
                                                            <a href="#" class="nav-link" id="filter_sales_region_kerabiya_disable_all_btn">
                                                                <i class="nav-icon la la-times"></i>
                                                                <span class="nav-text">Disable All</span>
                                                            </a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a href="#" class="nav-link" id="filter_sales_region_kerabiya_disable_selected_btn">
                                                                <i class="nav-icon la la-times"></i>
                                                                <span class="nav-text">Disable Selected</span>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </form>

                            </div>

                        </div>

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="regions_list_table_area">
                                    <table class="table table-bordered" id="regions_list_table">

                                        <thead>
                                            <tr>
                                                <th>Select</th>
                                                <th>API Channel</th>
                                                <th>Region Id</th>
                                                <th>Region</th>
                                                <th>Country Id</th>
                                                <th>Kerabiya Logistics</th>
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
            SalesCustomJsBlocks.regionslistPage('{{ url('/') }}');
        });
    </script>

@endsection
