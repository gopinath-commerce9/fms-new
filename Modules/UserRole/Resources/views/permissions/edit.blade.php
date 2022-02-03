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
                        </h3>
                    </div>
                    <!--end::Card Title-->

                    <!--begin::Card Toolbar-->
                    <div class="card-toolbar">


                    </div>
                    <!--end::Card Toolbar-->

                </div>
                <!--end::Card Header-->

                <!--begin::Form-->
                <form class="form" id="user_permission_edit_form" action="{{ url('/userrole/permissions/update/' . $givenUserPermission->id) }}" method="post">

                    @csrf

                    <!--begin::Card Body-->
                    <div class="card-body">

                        <div class="form-group row mt-4">
                            <label  class="col-3 col-form-label text-right">Code</label>
                            <label  class="col-6 col-form-label text-left">{{ $givenUserPermission->code }}</label>
                        </div>

                        <div class="form-group row">
                            <label  class="col-3 col-form-label text-right">Name</label>
                            <div class="col-6">
                                <input type="text" class="form-control" id="permission_name" name="permission_name" placeholder="Enter Permission Display Name" value="{{ $givenUserPermission->display_name }}"/>
                                <span class="form-text text-muted">This will be displayed to show the Permission.</span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label  class="col-3 col-form-label text-right">Description</label>
                            <div class="col-6">
                                <textarea class="form-control" id="permission_desc" name="permission_desc" rows="3">{{ $givenUserPermission->description }}</textarea>
                                <span class="form-text text-muted">A short description about the Permission.</span>
                            </div>
                        </div>

                        <div class="form-group row mb-1">
                            <label  class="col-3 col-form-label text-right">Active<span class="text-danger">*</span></label>
                            <div class="col-6">
                                <select class="form-control" id="permission_active" name="permission_active" >
                                    <option value="1" {{ ($givenUserPermission->is_active == 1) ? "selected" : "" }}>Active</option>
                                    <option value="0" {{ ($givenUserPermission->is_active == 0) ? "selected" : "" }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-role-permissions.grant'))
                        <div class="form-group row mb-1 mt-5">
                            <label  class="col-3 col-form-label text-right">Roles</label>
                            <?php
                            $mappedUserRoles = null;
                            $mappedUserRolesArray = null;
                            if ($givenUserPermission->mappedRoles && (count($givenUserPermission->mappedRoles) > 0)) {
                                $mappedUserRoles = $givenUserPermission->mappedRoles;
                                $mappedUserRolesArray = $mappedUserRoles->toArray();
                            }
                            ?>
                            <div class="col-6">

                                <div  class="table-responsive">
                                    <table class="table table-bordered table-checkable text-center" id="permission_role_map_table">

                                        <thead>
                                            <tr>
                                                <th>Id</th>
                                                <th>Code</th>
                                                <th>Name</th>
                                                <th>Active</th>
                                                <th>Permission Active</th>
                                                <th>Permitted</th>
                                            </tr>
                                        </thead>

                                        <tbody>

                                            @foreach($givenRoleList as $userRoleEl)

                                                <?php
                                                    $filteredUserRole = null;
                                                    if ($mappedUserRolesArray) {
                                                        $filteredRoles = array_filter($mappedUserRolesArray, function($value) use ($userRoleEl) {
                                                            return $value['id'] === $userRoleEl->id;
                                                        });
                                                        if (count($filteredRoles) > 0) {
                                                            $filteredUserRole = array_values($filteredRoles)[0];
                                                        }
                                                    }
                                                ?>

                                                <tr>
                                                    <td>{{ $userRoleEl->id }}</td>
                                                    <td>{{ $userRoleEl->code }}</td>
                                                    <td>{{ $userRoleEl->display_name }}</td>
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
                                                        @if($userRoleEl->isAdmin() && $givenUserPermission->isDefaultPermission())
                                                            @if(!$filteredUserRole || ($filteredUserRole && ($filteredUserRole['pivot']['is_active'] == 0)))
                                                                <span class="label label-lg font-weight-bold label-light-danger label-inline">Inactive</span>
                                                            @elseif($filteredUserRole && ($filteredUserRole['pivot']['is_active'] == 1))
                                                                <span class="label label-lg font-weight-bold label-light-success label-inline">Active</span>
                                                            @endif
                                                        @else
                                                            <select class="form-control"
                                                                    id="permission_map_active_{{ $givenUserPermission->id }}_{{ $userRoleEl->id }}"
                                                                    name="permission_map[{{ $userRoleEl->id }}][active]" >
                                                                <option value="1" {{ (!$filteredUserRole || ($filteredUserRole && ($filteredUserRole['pivot']['is_active'] == 1))) ? "selected" : "" }}>Active</option>
                                                                <option value="0" {{ ($filteredUserRole && ($filteredUserRole['pivot']['is_active'] == 0)) ? "selected" : "" }}>Inactive</option>
                                                            </select>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($userRoleEl->isAdmin() && $givenUserPermission->isDefaultPermission())
                                                            @if(!$filteredUserRole || ($filteredUserRole && ($filteredUserRole['pivot']['permitted'] == 0)))
                                                                <span class="label label-lg font-weight-bold label-light-danger label-inline">Not Permitted</span>
                                                            @elseif($filteredUserRole && ($filteredUserRole['pivot']['permitted'] == 1))
                                                                <span class="label label-lg font-weight-bold label-light-success label-inline">Permitted</span>
                                                            @endif
                                                        @else
                                                            <select class="form-control"
                                                                    id="permission_map_permitted_{{ $givenUserPermission->id }}_{{ $userRoleEl->id }}"
                                                                    name="permission_map[{{ $userRoleEl->id }}][permitted]" >
                                                                <option value="0" {{ (!$filteredUserRole || ($filteredUserRole && ($filteredUserRole['pivot']['permitted'] == 0))) ? "selected" : "" }}>Not Permitted</option>
                                                                <option value="1" {{ ($filteredUserRole && ($filteredUserRole['pivot']['permitted'] == 1)) ? "selected" : "" }}>Permitted</option>
                                                            </select>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach

                                        </tbody>

                                    </table>
                                </div>

                            </div>
                        </div>
                        @endif

                    </div>
                    <!--end::Card Body-->

                    <!--begin::Card Footer-->
                    <div class="card-footer text-right">
                        <button type="submit" id="edit_user_permission_submit_btn" class="btn btn-primary font-weight-bold mr-2">
                            <i class="la la-save"></i>Save Permission
                        </button>
                        <button type="button" id="edit_user_permission_cancel_btn" class="btn btn-light-primary font-weight-bold">Cancel</button>
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
            UserPermissionsCustomJsBlocks.editPage('{{ url('/') }}');
        });
    </script>

@endsection
