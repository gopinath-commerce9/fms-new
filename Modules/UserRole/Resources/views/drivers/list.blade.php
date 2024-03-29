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
                            <h3 class="card-label">Drivers</h3>
                            <span class="d-block text-muted pt-2 font-size-sm">Total <?php echo count($drivers->mappedUsers); ?> Driver(s).</span>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>

                    <div class="card-body p-0 mb-7">

                        <div class="row border-bottom mb-7">

                            <div class="col-md-12">

                                <div class="table-responsive text-center" id="role_driver_list_table_area">
                                    <table class="table table-bordered" id="role_driver_list_table">

                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Avatar</th>
                                                <th>Name</th>
                                                <th>EMail</th>
                                                <th>Contact</th>
                                                <th>Active</th>
                                                <th>Feeder</th>
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
                                                        @if($userEl->pivot->is_active === \Modules\UserRole\Entities\UserRole::ROLE_USER_ACTIVE_NO)
                                                            <span class="label label-lg font-weight-bold label-light-danger label-inline mt-2">No</span>
                                                        @elseif($userEl->pivot->is_active === \Modules\UserRole\Entities\UserRole::ROLE_USER_ACTIVE_YES)
                                                            <span class="label label-lg font-weight-bold label-light-success label-inline mt-2">Yes</span>
                                                        @else
                                                            {{ $userEl->pivot->is_active }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($userEl->pivot->is_feeder_driver == 0)
                                                            <span class="label label-lg font-weight-bold label-light-danger label-inline mt-2">No</span>
                                                        @elseif($userEl->pivot->is_feeder_driver == 1)
                                                            <span class="label label-lg font-weight-bold label-light-success label-inline mt-2">Yes</span>
                                                        @else
                                                            {{ $userEl->pivot->is_feeder_driver }}
                                                        @endif
                                                    </td>
                                                    <td nowrap="nowrap">
                                                        <a href="{{ url('/userrole/drivers/view/' . $userEl->id) }}" class="btn btn-primary btn-clean mr-2" title="View Driver">
                                                            <span>View</span>
                                                        </a>
                                                    </td>
                                                </tr>

                                            @endforeach
                                        @else
                                            <tr><td colspan="7">No Drivers found!</td></tr>
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
            RoleDriversCustomJsBlocks.listPage('{{ url('/') }}');
        });
    </script>

@endsection
