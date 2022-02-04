<!DOCTYPE html>
<html lang="en">

    <head>

        <meta charset="utf-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
        <meta name="description" content="FMS - Fulfillment Center" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

        <title>@yield('page-title') - {{ config('app.name') }}</title>

        <link rel="canonical" href="https://keenthemes.com/metronic" />
        <!--begin::Fonts-->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
        <!--end::Fonts-->
        <!--begin::Page Custom Styles(used by this page)-->
        <link href="{{ asset('ktmt/css/pages/wizard/wizard-3.css') }}" rel="stylesheet" type="text/css" />

        <link href="{{ asset('ktmt/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />

        <!--end::Page Custom Styles-->
        <!--begin::Global Theme Styles(used by all pages)-->
        <link href="{{ asset('ktmt/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('ktmt/plugins/custom/prismjs/prismjs.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('ktmt/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
        <!--end::Global Theme Styles-->
        <!--begin::Layout Themes(used by all pages)-->
        <!--end::Layout Themes-->

        {{-- Laravel Mix - CSS File --}}
         <link rel="stylesheet" href="{{ asset('css/base.css') }}">

        @yield('custom-css-section')

        <link rel="shortcut icon" href="{{ asset('ktmt/media/logos/aanacart-favicon-final.png') }}" />

        @yield('initialize-js-section')

    </head>

    <body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled page-loading">

        <!--begin::Main-->

        <!--begin::Header Mobile-->

        @include('base::layouts.partials.mt-mobile-header')

        <!--end::Header Mobile-->

        <div class="d-flex flex-column flex-root">

            <!--begin::Page-->
            <div class="d-flex flex-row flex-column-fluid page">

                <!--begin::Aside-->
                @include('base::layouts.partials.mt-sidebar')
                <!--end::Aside-->

                <!--begin::Wrapper-->
                <div class="d-flex flex-column flex-row-fluid wrapper" id="kt_wrapper">

                    <!--begin::Header-->
                    @include('base::layouts.partials.mt-header')
                    <!--end::Header-->

                    <!--begin::Content-->
                    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">

                        <!--begin::Subheader-->
                        @include('base::layouts.partials.mt-subheader')
                        <!--end::Subheader-->

                        <!--begin::Entry-->
                        <div class="d-flex flex-column-fluid">

                            <div class="container">

                                @if(session()->has('success'))
                                    <div class="alert alert-custom alert-success alert-light-success fade show" role="alert">
                                        <div class="alert-icon"><i class="flaticon2-check-mark"></i></div>
                                        <div class="alert-text">{{ session()->get('success') }}</div>
                                        <div class="alert-close">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true"><i class="ki ki-close"></i></span>
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                @if(session()->has('message'))
                                    <div class="alert alert-custom alert-dark alert-light-dark fade show" role="alert">
                                        <div class="alert-icon"><i class="flaticon-information"></i></div>
                                        <div class="alert-text">{{ session()->get('message') }}</div>
                                        <div class="alert-close">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true"><i class="ki ki-close"></i></span>
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                @if(session()->has('error'))
                                    <div class="alert alert-custom alert-danger alert-light-danger fade show" role="alert">
                                        <div class="alert-icon"><i class="flaticon2-warning"></i></div>
                                        <div class="alert-text">{{ session()->get('error') }}</div>
                                        <div class="alert-close">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true"><i class="ki ki-close"></i></span>
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                @if($errors->any())
                                    <div class="alert alert-custom alert-danger alert-light-danger fade show" role="alert">
                                        <div class="alert-icon"><i class="flaticon2-warning"></i></div>
                                        <div class="alert-text">
                                            <ul class="list-unstyled">
                                                {!! implode('', $errors->all('<li><span>:message</span></li>')) !!}
                                            </ul>
                                        </div>
                                        <div class="alert-close">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true"><i class="ki ki-close"></i></span>
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                <div class="custom_alert_trigger_messages_area">

                                </div>

                                @yield('content')

                            </div>

                        </div>
                        <!--end::Entry-->

                    </div>
                    <!--end::Content-->

                    @yield('content-before-footer')

                    <!--begin::Footer-->
                    @include('base::layouts.partials.mt-footer')
                    <!--end::Footer-->

                </div>
                <!--end::Wrapper-->

            </div>
            <!--end::Page-->

        </div>
        <!--end::Main-->

        <script>var HOST_URL = "{{ url('/') }}";</script>

        <!--begin::Global Config(global config for global JS scripts)-->
        <script>var KTAppSettings = { "breakpoints": { "sm": 576, "md": 768, "lg": 992, "xl": 1200, "xxl": 1200 }, "colors": { "theme": { "base": { "white": "#ffffff", "primary": "#8950FC", "secondary": "#E5EAEE", "success": "#1BC5BD", "info": "#8950FC", "warning": "#FFA800", "danger": "#F64E60", "light": "#F3F6F9", "dark": "#212121" }, "light": { "white": "#ffffff", "primary": "#E1E9FF", "secondary": "#ECF0F3", "success": "#C9F7F5", "info": "#EEE5FF", "warning": "#FFF4DE", "danger": "#FFE2E5", "light": "#F3F6F9", "dark": "#D6D6E0" }, "inverse": { "white": "#ffffff", "primary": "#ffffff", "secondary": "#212121", "success": "#ffffff", "info": "#ffffff", "warning": "#ffffff", "danger": "#ffffff", "light": "#464E5F", "dark": "#ffffff" } }, "gray": { "gray-100": "#F3F6F9", "gray-200": "#ECF0F3", "gray-300": "#E5EAEE", "gray-400": "#D6D6E0", "gray-500": "#B5B5C3", "gray-600": "#80808F", "gray-700": "#464E5F", "gray-800": "#1B283F", "gray-900": "#212121" } }, "font-family": "Poppins" };</script>
        <!--end::Global Config-->

        <!--begin::Global Theme Bundle(used by all pages)-->
        <script src="{{ asset('ktmt/plugins/global/plugins.bundle.js') }}"></script>
        <script src="{{ asset('ktmt/plugins/custom/prismjs/prismjs.bundle.js') }}"></script>
        <script src="{{ asset('ktmt/js/scripts.bundle.js') }}"></script>
        <!--end::Global Theme Bundle-->

        <!--begin::Page Vendors(used by this page)-->
        <script src="{{ asset('ktmt/plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <!--end::Page Vendors-->

        <!--begin::Page Scripts(used by this page)-->
        <script src="{{ asset('ktmt/js/pages/crud/datatables/basic/basic.js') }}"></script>

        {{-- Laravel Mix - JS File --}}
         <script src="{{ asset('js/base.js') }}"></script>

        @yield('custom-js-section')
        <!--end::Page Scripts-->

    </body>

</html>
