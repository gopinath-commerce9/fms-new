@extends('base::layouts.mt-login-layout')

@section('page-title') <?= $pageTitle; ?> @endsection

@section('content')

    <div class="d-flex flex-column flex-root" id="kt_body">
        <!--begin::Login-->
        <div class="login login-4 login-signin-on d-flex flex-row-fluid" id="kt_login">
            <div class="d-flex flex-center flex-row-fluid bgi-size-cover bgi-position-top bgi-no-repeat" style="background-image: url({{ asset('ktmt/media/bg/bg-3.jpg') }});">
                <div class="login-form text-center p-7 position-relative overflow-hidden">
                    <!--begin::Login Header-->
                    <div class="d-flex flex-center mb-15">
                        <a href="#">
                            <object data="{{ asset('ktmt/media/logos/aanacart-logo.svg') }}" type="image/svg+xml" class="max-h-75px" width="100%" height="100"></object>
                        </a>
                    </div>
                    <!--end::Login Header-->
                    <!--begin::Login Sign in form-->
                    <div class="login-signin">

                        <div class="mb-20">
                            <h3>Fulfillment Center</h3>
                            <div class="text-muted font-weight-bold">Enter your details to login to your account:</div>
                        </div>

                        <form class="form" id="kt_login_signin_form" action="{{ route('userauth.authenticate') }}" method="post">

                            @csrf

                            <div style="color:red;text-align: center">{{ session()->get('message') }}</div>
                            <div style="color:red;text-align: center">{{ session()->get('error') }}</div>
                            <div style="color:red;text-align: center">{!! implode('', $errors->all('<li><span>:message</span></li>')) !!}</div>

                            <div class="form-group mb-5">
                                <input type="text" placeholder="Enter Username" name="username" required class="form-control h-auto form-control-solid py-4 px-8">
                            </div>

                            <div class="form-group mb-5">
                                <input type="password" placeholder="Enter Password" name="password" required class="form-control h-auto form-control-solid py-4 px-8">
                            </div>

                            <!--<button type="submit">Login</button>-->
                            <button type="submit" id="kt_login_signin_submit" class="btn btn-primary font-weight-bold px-9 py-4 my-3 mx-4">Sign In</button>

                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Login-->
    </div>

@endsection
