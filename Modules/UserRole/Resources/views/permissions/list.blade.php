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
                            <span class="d-block text-muted pt-2 font-size-sm">Total <?= $userPermissionsTotal; ?> Records</span>
                        </h3>
                    </div>
                    <!--end::Card Title-->

                    <!--begin::Card Toolbar-->
                    <div class="card-toolbar">

                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-role-permissions.create'))
                        <!--begin::Button-->
                        <a href="{{ url('/userrole/permissions/new') }}" class="btn btn-primary font-weight-bolder">
                            <i class="la la-plus"></i>New Permission
                        </a>
                        <!--end::Button-->
                        @endif

                    </div>
                    <!--end::Card Toolbar-->

                </div>
                <!--end::Card Header-->

                <!--begin::Card Body-->
                <div class="card-body">

                    <div  class="table-responsive">
                        <table class="table table-bordered table-checkable" id="user_permissions_table">

                            <thead>
                                <tr>
                                    <th> ID </th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Active</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                            @foreach($userPermissionList as $userPermissionEl)

                                <tr>
                                    <td>{{ $userPermissionEl->id }}</td>
                                    <td>{{ $userPermissionEl->code }}</td>
                                    <td>{{ $userPermissionEl->display_name }}</td>
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
                                    <td>{{ date('Y-m-d H:i:s', strtotime($userPermissionEl->created_at)) }}</td>
                                    <td>{{ date('Y-m-d H:i:s', strtotime($userPermissionEl->updated_at)) }}</td>
                                    <td nowrap="nowrap">

                                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-role-permissions.view'))
                                            <a href="{{ url('/userrole/permissions/view/' . $userPermissionEl->id) }}" class="btn btn-sm btn-clean btn-icon mr-2" title="View">
                                                <i class="flaticon2-list-2 text-info"></i>
                                            </a>
                                        @endif

                                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-role-permissions.update'))
                                            <a href="{{ url('/userrole/permissions/edit/' . $userPermissionEl->id) }}" class="btn btn-sm btn-clean btn-icon mr-2" title="Edit">
                                                <i class="flaticon2-pen text-warning"></i>
                                            </a>
                                        @endif

                                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-role-permissions.delete'))
                                            @if(!$userPermissionEl->isDefaultPermission())
                                                <a href="{{ url('/userrole/permissions/delete/' . $userPermissionEl->id) }}" class="btn btn-sm btn-clean btn-icon" title="Delete">
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

    <script src="{{ asset('js/permission.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            UserPermissionsCustomJsBlocks.listPage();
        });
    </script>

@endsection

