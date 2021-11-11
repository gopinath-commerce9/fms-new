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
                            <a href="{{ url('/userrole/roles') }}" class="btn btn-outline-primary">
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
                                <a class="nav-link active" data-toggle="tab" href="#user_role_view_info_tab">
                                    <span class="nav-text">Info</span>
                                </a>
                            </li>

                            @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('users.view'))
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#user_role_view_users_tab">
                                    <span class="nav-text">Users</span>
                                </a>
                            </li>
                            @endif

                            @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-role-permissions.view'))
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#user_role_view_permissions_tab">
                                    <span class="nav-text">Permissions</span>
                                </a>
                            </li>
                            @endif

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

                        <div class="tab-pane fade show active" id="user_role_view_info_tab" role="tabpanel" aria-labelledby="user_role_view_info_tab">

                            <div class="form-group row my-2">

                                <div class="col col-2 text-right">
                                    <span class="label label-xl label-dark font-weight-boldest label-inline mr-2">General Info</span>
                                </div>

                                <div class="col col-10 text-right">
                                    @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-roles.update'))
                                        <a href="{{ url('/userrole/roles/edit/' . $givenUserRole->id) }}" class="btn btn-warning mr-2" title="Edit">
                                            <i class="flaticon2-pen"></i> Edit
                                        </a>
                                    @endif
                                    @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-roles.delete'))
                                        @if(!$givenUserRole->isAdmin())
                                            <a href="{{ url('/userrole/roles/delete/' . $givenUserRole->id) }}" class="btn btn-danger mr-2" title="Delete">
                                                <i class="flaticon-delete-1"></i> Delete
                                            </a>
                                        @endif
                                    @endif
                                </div>

                            </div>

                            <div class="form-group row my-2">
                                <label class="col-2 col-form-label font-size-lg-h2 text-right">Code:</label>
                                <div class="col-10">
                                    <span class="form-control-plaintext font-size-lg-h2 font-weight-bolder text-left">{{ $givenUserRole->code }}</span>
                                </div>
                            </div>

                            <div class="form-group row my-2">
                                <label class="col-2 col-form-label font-size-lg-h2 text-right">Name:</label>
                                <div class="col-10">
                                    <span class="form-control-plaintext font-size-lg-h2 font-weight-bolder text-left">{{ $givenUserRole->display_name }}</span>
                                </div>
                            </div>

                            <div class="form-group row my-2">
                                <label class="col-2 col-form-label font-size-lg-h2 text-right">Description:</label>
                                <div class="col-10">
                                    <span class="form-control-plaintext font-size-lg-h2 font-weight-bolder text-left">{{ $givenUserRole->description }}</span>
                                </div>
                            </div>

                            <div class="form-group row my-2">
                                <label class="col-2 col-form-label font-size-lg-h2 text-right">Active:</label>
                                <div class="col-10">
                                    @if($givenUserRole->is_active == 0)
                                        <span class="label label-lg font-weight-bold label-light-danger label-inline mt-2">No</span>
                                    @elseif($givenUserRole->is_active == 1)
                                        <span class="label label-lg font-weight-bold label-light-success label-inline mt-2">Yes</span>
                                    @else
                                        {{ $givenUserRole->is_active }}
                                    @endif
                                </div>
                            </div>

                        </div>

                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('users.view'))
                        <div class="tab-pane fade" id="user_role_view_users_tab" role="tabpanel" aria-labelledby="user_role_view_users_tab">

                            <div class="form-group row my-2">

                                <div class="col col-12 text-center">
                                    <span class="label label-xl label-dark font-weight-boldest label-inline mr-2">Users List</span>
                                </div>

                            </div>

                            <div class="form-group row my-2">
                                @if($givenUserRole->mappedUsers && (count($givenUserRole->mappedUsers) > 0))

                                    <div class="col col-12 text-center">

                                        <div  class="table-responsive">
                                            <table class="table table-bordered table-checkable" id="role_user_list_table">

                                                <thead>
                                                    <tr>
                                                        <th rowspan="2"> ID </th>
                                                        <th rowspan="2">Name</th>
                                                        <th rowspan="2">EMail</th>
                                                        <th colspan="4">Role ({{ $givenUserRole->code }})</th>
                                                    </tr>
                                                    <tr>
                                                        <th>Active</th>
                                                        <th>Created At</th>
                                                        <th>Updated At</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                    @foreach($givenUserRole->mappedUsers as $userEl)
                                                    <tr>
                                                        <td>{{ $userEl->id }}</td>
                                                        <td>{{ $userEl->name }}</td>
                                                        <td>{{ $userEl->email }}</td>
                                                        <td>
                                                            @if($userEl->pivot->is_active == 0)
                                                                <span class="label label-lg font-weight-bold label-light-danger label-inline">Inactive</span>
                                                            @elseif($userEl->pivot->is_active == 1)
                                                                <span class="label label-lg font-weight-bold label-light-success label-inline">Active</span>
                                                            @else
                                                                {{ $userEl->pivot->is_active }}
                                                            @endif
                                                        </td>
                                                        <td>{{ date('Y-m-d H:i:s', strtotime($userEl->pivot->created_at)) }}</td>
                                                        <td>{{ date('Y-m-d H:i:s', strtotime($userEl->pivot->updated_at)) }}</td>
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

                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-role-permissions.view'))
                        <div class="tab-pane fade" id="user_role_view_permissions_tab" role="tabpanel" aria-labelledby="user_role_view_permissions_tab">

                            <div class="form-group row my-2">

                                <div class="col col-12 text-center">
                                    <span class="label label-xl label-dark font-weight-boldest label-inline mr-2">Permission List</span>
                                </div>

                            </div>

                            <div class="form-group row my-2">
                                @if($givenUserRole->mappedPermissions && (count($givenUserRole->mappedPermissions) > 0))

                                    <div class="col col-12 text-center">

                                        <div  class="table-responsive">
                                            <table class="table table-bordered table-checkable" id="role_permission_list_table">

                                                <thead>
                                                    <tr>
                                                        <th rowspan="2"> ID </th>
                                                        <th rowspan="2">Code</th>
                                                        <th rowspan="2">Name</th>
                                                        <th rowspan="2">Description</th>
                                                        <th rowspan="2">Active</th>
                                                        <th colspan="4">Role ({{ $givenUserRole->code }})</th>
                                                    </tr>
                                                    <tr>
                                                        <th>Permitted</th>
                                                        <th>Active</th>
                                                        <th>Created At</th>
                                                        <th>Updated At</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                    @foreach($givenUserRole->mappedPermissions as $userPermissionEl)
                                                    <tr>
                                                        <td>{{ $userPermissionEl->id }}</td>
                                                        <td>{{ $userPermissionEl->code }}</td>
                                                        <td>
                                                            <span class="label label-lg font-weight-bold label-light-primary label-inline">
                                                                {{ $userPermissionEl->display_name }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $userPermissionEl->description }}</td>
                                                        <td>
                                                            @if($userPermissionEl->is_active == 0)
                                                                <span class="label label-lg font-weight-bold label-light-danger label-inline">Inactive</span>
                                                            @elseif($userPermissionEl->is_active == 1)
                                                                <span class="label label-lg font-weight-bold label-light-success label-inline">Active</span>
                                                            @else
                                                                {{ $userPermissionEl->is_active }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($userPermissionEl->pivot->permitted == 0)
                                                                <span class="label label-lg font-weight-bold label-light-danger label-inline">Not Permitted</span>
                                                            @elseif($userPermissionEl->pivot->permitted == 1)
                                                                <span class="label label-lg font-weight-bold label-light-success label-inline">Permitted</span>
                                                            @else
                                                                {{ $userPermissionEl->pivot->permitted }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($userPermissionEl->pivot->is_active == 0)
                                                                <span class="label label-lg font-weight-bold label-light-danger label-inline">Inactive</span>
                                                            @elseif($userPermissionEl->pivot->is_active == 1)
                                                                <span class="label label-lg font-weight-bold label-light-success label-inline">Active</span>
                                                            @else
                                                                {{ $userPermissionEl->pivot->is_active }}
                                                            @endif
                                                        </td>
                                                        <td>{{ date('Y-m-d H:i:s', strtotime($userPermissionEl->pivot->created_at)) }}</td>
                                                        <td>{{ date('Y-m-d H:i:s', strtotime($userPermissionEl->pivot->updated_at)) }}</td>
                                                    </tr>
                                                    @endforeach

                                                </tbody>

                                            </table>
                                        </div>

                                    </div>

                                @else
                                    <label class="col-12 col-form-label font-size-lg-h2 text-center">No Permissions yet!</label>
                                @endif
                            </div>

                        </div>
                        @endif

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

    <script src="{{ asset('js/userrole.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            UserRolesCustomJsBlocks.viewPage();
        });
    </script>

@endsection
