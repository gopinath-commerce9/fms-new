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
                            <a href="{{ url('/userrole/permissions') }}" class="btn btn-outline-primary">
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
                                <a class="nav-link active" data-toggle="tab" href="#user_permission_view_info_tab">
                                    <span class="nav-text">Info</span>
                                </a>
                            </li>

                            @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-roles.view'))
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#user_permission_view_roles_tab">
                                    <span class="nav-text">Roles</span>
                                </a>
                            </li>
                            @endif

                            {{--<li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#user_permission_view_users_tab">
                                    <span class="nav-text">Users</span>
                                </a>
                            </li>--}}

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

                        <div class="tab-pane fade show active" id="user_permission_view_info_tab" role="tabpanel" aria-labelledby="user_permission_view_info_tab">

                            <div class="form-group row my-2">

                                <div class="col col-2 text-right">
                                    <span class="label label-xl label-dark font-weight-boldest label-inline mr-2">General Info</span>
                                </div>

                                <div class="col col-10 text-right">
                                    @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-role-permissions.update'))
                                        <a href="{{ url('/userrole/permissions/edit/' . $givenUserPermission->id) }}" class="btn btn-warning mr-2" title="Edit">
                                            <i class="flaticon2-pen"></i> Edit
                                        </a>
                                    @endif
                                    @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-role-permissions.delete'))
                                        @if(!$givenUserPermission->isDefaultPermission())
                                            <a href="{{ url('/userrole/permissions/delete/' . $givenUserPermission->id) }}" class="btn btn-danger mr-2" title="Delete">
                                                <i class="flaticon-delete-1"></i> Delete
                                            </a>
                                        @endif
                                    @endif
                                </div>

                            </div>

                            <div class="form-group row my-2">
                                <label class="col-2 col-form-label font-size-lg-h2 text-right">Code:</label>
                                <div class="col-10">
                                    <span class="form-control-plaintext font-size-lg-h2 font-weight-bolder text-left">{{ $givenUserPermission->code }}</span>
                                </div>
                            </div>

                            <div class="form-group row my-2">
                                <label class="col-2 col-form-label font-size-lg-h2 text-right">Name:</label>
                                <div class="col-10">
                                    <span class="form-control-plaintext font-size-lg-h2 font-weight-bolder text-left">{{ $givenUserPermission->display_name }}</span>
                                </div>
                            </div>

                            <div class="form-group row my-2">
                                <label class="col-2 col-form-label font-size-lg-h2 text-right">Description:</label>
                                <div class="col-10">
                                    <span class="form-control-plaintext font-size-lg-h2 font-weight-bolder text-left">{{ $givenUserPermission->description }}</span>
                                </div>
                            </div>

                            <div class="form-group row my-2">
                                <label class="col-2 col-form-label font-size-lg-h2 text-right">Active:</label>
                                <div class="col-10">
                                    @if($givenUserPermission->is_active == 0)
                                        <span class="label label-lg font-weight-bold label-light-danger label-inline mt-2">No</span>
                                    @elseif($givenUserPermission->is_active == 1)
                                        <span class="label label-lg font-weight-bold label-light-success label-inline mt-2">Yes</span>
                                    @else
                                        {{ $givenUserPermission->is_active }}
                                    @endif
                                </div>
                            </div>

                        </div>

                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-roles.view'))
                        <div class="tab-pane fade" id="user_permission_view_roles_tab" role="tabpanel" aria-labelledby="user_permission_view_roles_tab">

                            <div class="form-group row my-2">

                                <div class="col col-12 text-center">
                                    <span class="label label-xl label-dark font-weight-boldest label-inline mr-2">Role List</span>
                                </div>

                            </div>

                            <div class="form-group row my-2">
                                @if($givenUserPermission->mappedRoles && (count($givenUserPermission->mappedRoles) > 0))

                                    <div class="col col-12 text-center">

                                        <div  class="table-responsive">
                                            <table class="table table-bordered table-checkable" id="permission_role_list_table">

                                                <thead>
                                                    <tr>
                                                        <th rowspan="2">Role ID </th>
                                                        <th rowspan="2">Code</th>
                                                        <th rowspan="2">Name</th>
                                                        <th rowspan="2">Description</th>
                                                        <th rowspan="2">Active</th>
                                                        <th colspan="4">Permission ({{ $givenUserPermission->code }})</th>
                                                    </tr>
                                                    <tr>
                                                        <th>Permitted</th>
                                                        <th>Active</th>
                                                        <th>Created At</th>
                                                        <th>Updated At</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                    @foreach($givenUserPermission->mappedRoles as $userRoleEl)
                                                    <tr>
                                                        <td>{{ $userRoleEl->id }}</td>
                                                        <td>{{ $userRoleEl->code }}</td>
                                                        <td>
                                                            <span class="label label-lg font-weight-bold label-light-primary label-inline">
                                                                {{ $userRoleEl->display_name }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $userRoleEl->description }}</td>
                                                        <td>
                                                            @if($userRoleEl->is_active == 0)
                                                                <span class="label label-lg font-weight-bold label-light-danger label-inline">Inactive</span>
                                                            @elseif($userRoleEl->is_active == 1)
                                                                <span class="label label-lg font-weight-bold label-light-success label-inline">Active</span>
                                                            @else
                                                                {{ $userRoleEl->is_active }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($userRoleEl->pivot->permitted == 0)
                                                                <span class="label label-lg font-weight-bold label-light-danger label-inline">Not Permitted</span>
                                                            @elseif($userRoleEl->pivot->permitted == 1)
                                                                <span class="label label-lg font-weight-bold label-light-success label-inline">Permitted</span>
                                                            @else
                                                                {{ $userRoleEl->pivot->permitted }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($userRoleEl->pivot->is_active == 0)
                                                                <span class="label label-lg font-weight-bold label-light-danger label-inline">Inactive</span>
                                                            @elseif($userRoleEl->pivot->is_active == 1)
                                                                <span class="label label-lg font-weight-bold label-light-success label-inline">Active</span>
                                                            @else
                                                                {{ $userRoleEl->pivot->is_active }}
                                                            @endif
                                                        </td>
                                                        <td>{{ date('Y-m-d H:i:s', strtotime($userRoleEl->pivot->created_at)) }}</td>
                                                        <td>{{ date('Y-m-d H:i:s', strtotime($userRoleEl->pivot->updated_at)) }}</td>
                                                    </tr>
                                                    @endforeach

                                                </tbody>

                                            </table>
                                        </div>

                                    </div>

                                @else
                                    <label class="col-12 col-form-label font-size-lg-h2 text-center">No Users yet!</label>
                                @endif
                            </div>



                        </div>
                        @endif

                        {{--<div class="tab-pane fade" id="user_permission_view_users_tab" role="tabpanel" aria-labelledby="user_permission_view_users_tab">
                            ...
                        </div>--}}

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

                </form>
                <!--end::Form-->

            </div>
            <!--end::Card-->

        </div>
    </div>

@endsection

@section('custom-js-section')

    <script src="{{ asset('js/permission.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            UserPermissionsCustomJsBlocks.viewPage();
        });
    </script>

@endsection
