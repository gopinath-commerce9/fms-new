<!DOCTYPE html>
<html lang="en">

    <head>

        <meta charset="utf-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>

        <title>@yield('page-title') - {{ config('app.name') }}</title>

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

        @yield('custom-css-section')

        @yield('initialize-js-section')

    </head>

    <body class="header-fixed header-mobile-fixed subheader-enabled page-loading">

        @yield('content')

        {{-- Laravel Mix - JS File --}}
        {{-- <script src="{{ mix('js/base.js') }}"></script> --}}

        @yield('custom-js-section')

    </body>

</html>
