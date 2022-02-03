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
                            <a href="{{ url('/userauth/users') }}" class="btn btn-outline-primary">
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
                                <a class="nav-link active" data-toggle="tab" href="#user_view_info_tab">
                                    <span class="nav-text">Info</span>
                                </a>
                            </li>

                            @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-roles.view'))
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#user_view_role_tab">
                                    <span class="nav-text">Role</span>
                                </a>
                            </li>
                            @endif

                            {{--<li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#user_view_permissions_tab">
                                    <span class="nav-text">Permissions</span>
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

                        <div class="tab-pane fade show active" id="user_view_info_tab" role="tabpanel" aria-labelledby="user_view_info_tab">

                            <div class="form-group row my-2">

                                <div class="col col-2 text-right">
                                    <span class="label label-xl label-dark font-weight-boldest label-inline mr-2">General Info</span>
                                </div>

                                <div class="col col-10 text-right">
                                    @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('users.update'))
                                        <a href="{{ url('/userauth/users/edit/' . $givenUserData->id) }}" class="btn btn-warning mr-2" title="Edit">
                                            <i class="flaticon2-pen"></i> Edit
                                        </a>
                                    @endif
                                    @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('users.delete'))
                                        @if(!$givenUserData->isDefaultUser())
                                            <a href="{{ url('/userauth/users/delete/' . $givenUserData->id) }}" class="btn btn-danger mr-2" title="Delete">
                                                <i class="flaticon-delete-1"></i> Delete
                                            </a>
                                        @endif
                                    @endif
                                </div>

                            </div>

                            <div class="row">

                                <div class="col col-2">

                                </div>

                                <div class="col col-10">

                                    <div class="d-flex">

                                        <div class="flex-shrink-0 mr-7">
                                            <div class="symbol symbol-50 symbol-lg-120">
                                                <?php
                                                $userDisplayName = $givenUserData->name;
                                                $userEmail = $givenUserData->email;
                                                $userContact = $givenUserData->contact_number;
                                                $userInitials = '';
                                                $profilePicUrl = '';
                                                if (!is_null($givenUserData->profile_picture) && ($givenUserData->profile_picture != '')) {
                                                    $dpData = json_decode($givenUserData->profile_picture, true);
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

                                        <div class="flex-grow-1">

                                            <div class="d-flex align-items-center justify-content-between flex-wrap mt-2">
                                                <div class="mr-3">
                                            <span class="d-flex align-items-center text-dark text-hover-primary font-size-h5 font-weight-bold mr-3">
                                                {{ $userDisplayName }}
                                            </span>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center flex-wrap justify-content-between">
                                                <div class="flex-grow-1 font-weight-bold text-dark-50 py-2 py-lg-2 mr-5">
                                                    {{ $userEmail }}
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center flex-wrap justify-content-between">
                                                <div class="flex-grow-1 font-weight-bold text-dark-50 py-2 py-lg-2 mr-5">
                                                    {{ $userContact }}
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-roles.view'))
                        <div class="tab-pane fade" id="user_view_role_tab" role="tabpanel" aria-labelledby="user_view_role_tab">

                            <div class="form-group row my-2">

                                <div class="col col-2 text-right">
                                    <span class="label label-xl label-dark font-weight-boldest label-inline mr-2">Role Info</span>
                                </div>

                            </div>

                            <div class="form-group row my-2">
                                <label class="col-2 col-form-label font-size-lg-h2 text-right">Role:</label>
                                <div class="col-10">
                                    @if($givenUserData->mappedRole && (count($givenUserData->mappedRole) > 0))
                                    <span class="label label-lg font-weight-bold label-light-primary label-inline mt-2">
                                        {{ $givenUserData->mappedRole[0]->display_name }}
                                    </span>
                                    @else
                                    <span class="label label-lg font-weight-bold label-light-primary label-inline mt-2">
                                        Not Assigned
                                    </span>
                                    @endif
                                </div>
                            </div>

                            @if($givenUserData->mappedRole && (count($givenUserData->mappedRole) > 0))

                                <div class="form-group row my-2">
                                    <label class="col-2 col-form-label font-size-lg-h2 text-right">Code:</label>
                                    <div class="col-10">
                                        <span class="form-control-plaintext font-size-lg-h2 font-weight-bolder text-left">
                                            {{ $givenUserData->mappedRole[0]->code }}
                                        </span>
                                    </div>
                                </div>

                                <div class="form-group row my-2">
                                    <label class="col-2 col-form-label font-size-lg-h2 text-right">Description:</label>
                                    <div class="col-10">
                                        <span class="form-control-plaintext font-size-lg-h2 font-weight-bolder text-left">
                                            {{ $givenUserData->mappedRole[0]->description }}
                                        </span>
                                    </div>
                                </div>

                                <div class="form-group row my-2">
                                    <label class="col-2 col-form-label font-size-lg-h2 text-right">Active:</label>
                                    <div class="col-10">
                                        @if($givenUserData->mappedRole[0]->is_active == 0)
                                            <span class="label label-lg font-weight-bold label-light-danger label-inline mt-2">No</span>
                                        @elseif($givenUserData->mappedRole[0]->is_active == 1)
                                            <span class="label label-lg font-weight-bold label-light-success label-inline mt-2">Yes</span>
                                        @else
                                            {{ $givenUserData->mappedRole[0]->is_active }}
                                        @endif
                                    </div>
                                </div>

                            @endif

                        </div>
                        @endif

                        {{--<div class="tab-pane fade" id="user_view_permissions_tab" role="tabpanel" aria-labelledby="user_view_permissions_tab">
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
