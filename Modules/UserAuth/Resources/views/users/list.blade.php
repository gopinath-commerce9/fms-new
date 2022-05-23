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

                    <!--begin::Card Title-->
                    <div class="card-title">
                        <h3 class="card-label">
                            <?= $pageSubTitle; ?>
                            <span class="d-block text-muted pt-2 font-size-sm">Total <?= $usersTotal; ?> Records</span>
                        </h3>
                    </div>
                    <!--end::Card Title-->

                    <!--begin::Card Toolbar-->
                    <div class="card-toolbar">

                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('users.create'))
                            <!--begin::Button-->
                            <a href="{{ url('/userauth/users/new') }}" class="btn btn-primary font-weight-bolder">
                                <i class="la la-plus"></i>New User
                            </a>
                            <!--end::Button-->
                        @endif

                    </div>
                    <!--end::Card Toolbar-->

                </div>
                <!--end::Card Header-->

                <!--begin::Card Body-->
                <div class="card-body">

                    <div  class="table-responsive text-center">
                        <table class="table table-bordered table-checkable" id="user_list_table">

                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Avatar</th>
                                <th>Name</th>
                                <th>EMail</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Action</th>
                            </tr>
                            </thead>

                            <tbody>

                            @foreach($userList as $userEl)

                                <?php $userEl->mappedRole ?>

                                <tr>
                                    <td>{{ $userEl->id }}</td>
                                    <td>
                                        <span style="width: 100px;">
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-100 symbol-sm symbol-light-info flex-shrink-0" style="padding-left: 20%; padding-right: 20%;">
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
                                                        <img class="" src="{{ $profilePicUrl }}" alt="photo">
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
                                        @if($userEl->mappedRole && (count($userEl->mappedRole) > 0))
                                            <span class="label label-lg font-weight-bold label-light-primary label-inline">
                                                {{ $userEl->mappedRole[0]->display_name . (((int)$userEl->mappedRole[0]->pivot->is_feeder_driver === 1) ? " [Feeder]" : "") }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ date('Y-m-d H:i:s', strtotime($userEl->created_at)) }}</td>
                                    <td>{{ date('Y-m-d H:i:s', strtotime($userEl->updated_at)) }}</td>
                                    <td nowrap="nowrap">

                                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('users.view'))
                                            <a href="{{ url('/userauth/users/view/' . $userEl->id) }}" class="btn btn-sm btn-clean btn-icon mr-2" title="View">
                                                <i class="flaticon2-list-2 text-info"></i>
                                            </a>
                                        @endif

                                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('users.update'))
                                            <a href="{{ url('/userauth/users/edit/' . $userEl->id) }}" class="btn btn-sm btn-clean btn-icon mr-2" title="Edit">
                                                <i class="flaticon2-pen text-warning"></i>
                                            </a>
                                        @endif

                                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('users.delete'))
                                            @if(!$userEl->isDefaultUser())
                                                <a href="{{ url('/userauth/users/delete/' . $userEl->id) }}" class="btn btn-sm btn-clean btn-icon" title="Delete">
                                                    <i class="flaticon-delete-1 text-danger"></i>
                                                </a>
                                            @endif
                                        @endif

                                    </td>
                                </tr>

                            @endforeach

                            </tbody>

                        </table>
                    </div>

                </div>
                <!--end::Card Body-->

            </div>
            <!--end::Card-->

        </div>
    </div>

@endsection

@section('custom-js-section')

    <script src="{{ asset('js/users.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            UsersCustomJsBlocks.listPage();
        });
    </script>

@endsection

