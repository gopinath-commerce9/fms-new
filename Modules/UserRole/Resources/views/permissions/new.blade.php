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
                <form class="form" id="user_role_new_form" action="{{ url('/userrole/permissions/store') }}" method="post">

                    @csrf

                    <!--begin::Card Body-->
                    <div class="card-body">

                        <div class="form-group row mt-4">
                            <label  class="col-3 col-form-label text-right">Code<span class="text-danger">*</span></label>
                            <div class="col-6">
                                <input type="text" class="form-control" id="permission_code" name="permission_code" value="{{ old('permission_code') }}" required placeholder="Enter User Permission Code"/>
                                <span class="form-text text-muted">Please enter the unique Permission Code</span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label  class="col-3 col-form-label text-right">Name</label>
                            <div class="col-6">
                                <input type="text" class="form-control" id="permission_name" name="permission_name" value="{{ old('permission_name') }}" placeholder="Enter Permission Display Name"/>
                                <span class="form-text text-muted">This will be displayed to show the Permission.</span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label  class="col-3 col-form-label text-right">Description</label>
                            <div class="col-6">
                                <textarea class="form-control" id="permission_desc" name="permission_desc" rows="3">{{ old('permission_desc') }}</textarea>
                                <span class="form-text text-muted">A short description about the Permission.</span>
                            </div>
                        </div>

                        <div class="form-group row mb-1">
                            <label  class="col-3 col-form-label text-right">Active<span class="text-danger">*</span></label>
                            <div class="col-6">
                                <select class="form-control" id="permission_active" name="permission_active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    <!--end::Card Body-->

                    <!--begin::Card Footer-->
                    <div class="card-footer text-right">
                        <button type="submit" id="new_user_permission_submit_btn" class="btn btn-primary font-weight-bold mr-2">
                            <i class="la la-plus"></i>Add Permission
                        </button>
                        <button type="button" id="new_user_permission_cancel_btn" class="btn btn-light-primary font-weight-bold">Cancel</button>
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
            UserPermissionsCustomJsBlocks.newPage('{{ url('/') }}');
        });
    </script>

@endsection
