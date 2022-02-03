<!DOCTYPE html>
<html lang="en">

    <head>

        <meta charset="utf-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>

        <title>@yield('title') - {{ config('app.name') }}</title>

        <link rel="stylesheet" type="text/css" href="{{ asset('backend/css/main.css') }}"/>
        <link rel="stylesheet" type="text/css"
              href="{{ asset('backend/css/font-awesome/4.7.0/css/font-awesome.min.css') }}"/>

        {{-- Laravel Mix - CSS File --}}
        {{-- <link rel="stylesheet" href="{{ mix('css/base.css') }}"> --}}

        @yield('custom-css-section')

        @yield('initialize-js-section')

    </head>

    <body class="app sidebar-mini rtl">

        @include('base::layouts.partials.header')

        @include('base::layouts.partials.sidebar')

        <main class="app-content">
            @yield('content')
        </main>

        <script src="{{ asset('backend/js/jquery-3.2.1.min.js') }}"></script>
        <script src="{{ asset('backend/js/popper.min.js') }}"></script>
        <script src="{{ asset('backend/js/bootstrap.min.js') }}"></script>
        <script src="{{ asset('backend/js/main.js') }}"></script>
        <script src="{{ asset('backend/js/plugins/pace.min.js') }}"></script>

        {{-- Laravel Mix - JS File --}}
        {{-- <script src="{{ mix('js/base.js') }}"></script> --}}

        @yield('custom-js-section')

    </body>

</html>
