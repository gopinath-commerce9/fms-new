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
                <form class="form" id="user_edit_form" action="{{ url('/userauth/users/update/' . $givenUserData->id) }}" method="POST" enctype="multipart/form-data">

                    @csrf

                    <!--begin::Card Body-->
                    <div class="card-body">

                        <div class="form-group row mt-4">
                            <label  class="col-3 col-form-label text-right">E-Mail</label>
                            <label  class="col-6 col-form-label text-left">{{ $givenUserData->email }}</label>
                        </div>

                        <div class="form-group row">
                            <label  class="col-3 col-form-label text-right">Name<span class="text-danger">*</span></label>
                            <div class="col-6">
                                <input type="text" class="form-control" id="user_name" name="user_name" placeholder="Enter User Name" value="{{ $givenUserData->name }}"/>
                                <span class="form-text text-muted">Please enter the name of the user.</span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label  class="col-3 col-form-label text-right">Contact</label>
                            <div class="col-6">
                                <input type="text" class="form-control" id="user_contact" name="user_contact" placeholder="Enter User Contact Number" value="{{ $givenUserData->contact_number }}"/>
                                <span class="form-text text-muted">Please enter the contact number of the user.</span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-form-label col-lg-3 col-sm-12 text-lg-right">Avatar</label>
                            <div class="col-lg-9 col-xl-6">

                                <div class="image-input image-input-outline" id="profile_avatar_area" style="background-image: url({{ $serviceHelper->getUserImageUrl('blank.png') }})">
                                    <?php
                                        $profilePicUrl = 'users/blank.png';
                                        if (!is_null($givenUserData->profile_picture) && ($givenUserData->profile_picture != '')) {
                                            $dpData = json_decode($givenUserData->profile_picture, true);
                                            $profilePicUrl = $dpData['path'];
                                        }
                                    ?>
                                    @if ($profilePicUrl != '')
                                        <div class="image-input-wrapper" style="background-image: url({{ $serviceHelper->getUserImageUrl($profilePicUrl) }})"></div>
                                    @endif
                                    <label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
                                        <i class="fa fa-pen icon-sm text-muted"></i>
                                        <input type="file" name="profile_avatar" id="profile_avatar" accept=".png, .jpg, .jpeg" />
                                        <input type="hidden" name="profile_avatar_remove" />
                                    </label>
                                    <span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="Cancel avatar">
                                        <i class="ki ki-bold-close icon-xs text-muted"></i>
                                    </span>
                                    <span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="remove" data-toggle="tooltip" title="Remove avatar">
                                        <i class="ki ki-bold-close icon-xs text-muted"></i>
                                    </span>
                                </div>
                                <span class="form-text text-muted">Allowed file types: png, jpg, jpeg. Allowed size: not more than 200KB.</span>
                            </div>
                        </div>

                        @if(\Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted('user-roles.assign'))
                            @if(!$givenUserData->isDefaultUser())
                            <div class="form-group row">
                                <label  class="col-3 col-form-label text-right">Role</label>
                                <?php
                                    $mappedUserRole = null;
                                    if ($givenUserData->mappedRole && (count($givenUserData->mappedRole) > 0)) {
                                        $mappedUserRole = $givenUserData->mappedRole[0];
                                    }
                                ?>
                                <div class="col-6">
                                    <select class="form-control" id="user_role" name="user_role" data-feeder-target="{{ (is_null($driverRole)) ? '' : $driverRole->id }}" >
                                        <option value="" {{ (is_null($mappedUserRole)) ? "selected" : "" }}>Not Assigned</option>
                                        @foreach($userRoles as $userRoleEl)
                                            <option value="{{ $userRoleEl->id }}" {{ (!is_null($mappedUserRole) && ($mappedUserRole->id == $userRoleEl->id)) ? "selected" : "" }}>
                                                {{ $userRoleEl->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row" id="edit_user_feeder_driver_check_row" style="display:none;">
                                <label  class="col-3 col-form-label text-right">Feeder Driver</label>
                                <div class="col-6">
                                    <select class="form-control" id="user_feeder_driver" name="user_feeder_driver" >
                                        <option value="0" {{ (is_null($mappedUserRole) || ((int)$mappedUserRole->pivot->is_feeder_driver === 0)) ? "selected" : "" }}>No</option>
                                        <option value="1" {{ (!is_null($mappedUserRole) && ((int)$mappedUserRole->pivot->is_feeder_driver === 1)) ? "selected" : "" }}>Yes</option>
                                    </select>
                                </div>
                            </div>
                            @endif
                        @endif

                        <div class="form-group row">
                            <label  class="col-3 col-form-label text-right">Reset Password</label>
                            <div class="col-6">
                                <div class="input-group show-hide-password-group" id="user_password_form_group">
                                    <input type="password" class="form-control" id="user_password" name="user_password" value="{{ old('user_password') }}" placeholder="Enter Password"/>
                                    <div class="input-group-append">
                                        <span class="input-group-text show-hide-password"><i class="fa fa-eye-slash" aria-hidden="true"></i></span>
                                    </div>
                                </div>
                                <span class="form-text text-muted">
                                    Please enter the password. The password must be more than 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character.
                                </span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label  class="col-3 col-form-label text-right">Confirm Password</label>
                            <div class="col-6">
                                <div class="input-group show-hide-password-group" id="user_password_conf_form_group">
                                    <input type="password" class="form-control" id="user_password_confirmation" name="user_password_confirmation"
                                           value="{{ old('user_password_confirmation') }}" placeholder="Confirm Password"/>
                                    <div class="input-group-append">
                                        <span class="input-group-text show-hide-password"><i class="fa fa-eye-slash" aria-hidden="true"></i></span>
                                    </div>
                                </div>
                                <span class="form-text text-muted">Please confirm the password.</span>
                            </div>
                        </div>

                    </div>
                    <!--end::Card Body-->

                    <!--begin::Card Footer-->
                    <div class="card-footer text-right">
                        <button type="submit" id="edit_user_submit_btn" class="btn btn-primary font-weight-bold mr-2">
                            <i class="la la-save"></i>Save User
                        </button>
                        <button type="button" id="edit_user_cancel_btn" class="btn btn-light-primary font-weight-bold">Cancel</button>
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

    <script src="{{ asset('js/users.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            UsersCustomJsBlocks.editPage('{{ url('/') }}');
        });
    </script>

@endsection
