<!DOCTYPE html>
<html lang="en">

    <head>

        <meta charset="utf-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>

        <title>{{ $pageSubTitle }} - {{ config('app.name') }}</title>

        <!--begin::Fonts-->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
        <!--end::Fonts-->

        <!--begin::Page Custom Styles(used by this page)-->
        <link href="{{ asset('ktmt/css/pages/login/classic/login-4.css') }}" rel="stylesheet" type="text/css" />
        <!--end::Page Custom Styles-->

        <!--begin::Global Theme Styles(used by all pages)-->
        <link href="{{ asset('ktmt/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('ktmt/plugins/custom/prismjs/prismjs.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('ktmt/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link rel="shortcut icon" href="{{ asset('ktmt/media/logos/aanacart-favicon-final.png') }}" />
        <!--end::Global Theme Styles(used by all pages)-->

        {{-- Laravel Mix - CSS File --}}
        {{-- <link rel="stylesheet" href="{{ mix('css/base.css') }}"> --}}

    </head>

    <body class="header-fixed header-mobile-fixed subheader-enabled page-loading">

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
                                <div class="text-muted font-weight-bold">Hello World</div>
                            </div>

                            <a href="{{ url('/userauth/login') }}" class="btn btn-sm btn-clean btn-icon mr-2" title="Login">
                                <i class="flaticon2-user-outline-symbol text-info"></i>
                            </a>

                        </div>

                    </div>
                </div>
            </div>
            <!--end::Login-->
        </div>

    </body>

</html>
