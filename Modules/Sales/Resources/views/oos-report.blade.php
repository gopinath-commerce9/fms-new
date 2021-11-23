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

                                <div  class="table-responsive text-center">

                                    <table class="table table-bordered table-checkable" id="oos_report_table">

                                        <thead>

                                            <tr>
                                                <th>Sku</th>
                                                <th>Name</th>
                                                <th>Supplier</th>
                                                <th>BarCode</th>
                                                <th>Out of Stock Since</th>
                                            </tr>

                                        </thead>

                                        <tbody>

                                            @if(!is_null($oosData) && is_array($oosData) && (count($oosData) > 0))
                                                @foreach($oosData as $record)
                                                    <tr>
                                                        <td>{{ $record['sku'] }}</td>
                                                        <td>{{ $record['name'] }}</td>
                                                        <td>{{ $record['supplier'] }}</td>
                                                        <td>{{ $record['barcode'] }}</td>
                                                        <td>{{ $record['outofstocksince'] }}</td>
                                                    </tr>
                                                @endforeach
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
            SalesCustomJsBlocks.oosReportPage('{{ url('/') }}');
        });
    </script>

@endsection
