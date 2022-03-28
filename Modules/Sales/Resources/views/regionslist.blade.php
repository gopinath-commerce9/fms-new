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
                            <span class="d-block text-muted pt-2 font-size-sm">Total <?php echo count($emirates); ?> Regions(s).</span>
                        </div>
                        <div class="card-toolbar">
                            <div class="col text-right">
                                <a href="{{ url('/sales/regions-update') }}" id="regions_update_btn" class="btn btn-primary">
                                    <i class="flaticon2-refresh-arrow"></i> Update From Server
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="regions_list_table_area">
                                    <table class="table table-bordered" id="regions_list_table">

                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>API Channel</th>
                                                <th>Region Id</th>
                                                <th>Country Id</th>
                                                <th>Region</th>
                                            </tr>
                                        </thead>

                                        <tbody>

                                        @if(count($emirates) > 0)
                                            @foreach($emirates as $regionEl)

                                                <tr>
                                                    <td>{{ $regionEl['id'] }}</td>
                                                    <td>{{ $availableApiChannels[$regionEl['channel']]['name'] }}</td>
                                                    <td>{{ $regionEl['region_id'] }}</td>
                                                    <td>{{ $regionEl['country_id'] }}</td>
                                                    <td>{{ $regionEl['name'] }}</td>
                                                </tr>

                                            @endforeach
                                        @else
                                            <tr><td colspan="5">No Regions found!</td></tr>
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

    <script src="{{ asset('js/sales.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            SalesCustomJsBlocks.regionslistPage('{{ url('/') }}');
        });
    </script>

@endsection
