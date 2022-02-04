<div class="aside aside-left d-flex flex-column" id="kt_aside">

    <?php
        $currentMenuUrl = '/' . Request::path();
        $dashboardUrl = '/dashboard';
        $currentRole = null;
        if (session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $currentRole = $sessionUser['roleCode'];
        }
        if (!is_null($currentRole) && \Illuminate\Support\Facades\Route::has($currentRole . '.dashboard')) {
            $dashboardUrl = '/' . $currentRole . '/dashboard';
        }
    ?>

    <!--begin::Brand-->
    <div class="aside-brand d-flex flex-column align-items-center flex-column-auto py-4 py-lg-8">
        <!--begin::Logo-->
        <a href="{{ url($dashboardUrl)  }}">
            <object data="{{ asset('ktmt/media/logos/aanacart-logo.svg') }}" type="image/svg+xml" class="max-h-50px" width="100%" height="100"></object>
        </a>
        <!--end::Logo-->
    </div>
    <!--end::Brand-->

    <!--begin::Nav Wrapper-->
    <div class="aside-nav d-flex flex-column align-items-center flex-column-fluid pt-7">

        <!--begin::Nav-->
        <ul class="nav flex-column">

            <li class="nav-item mb-5" data-toggle="tooltip" data-placement="right" data-container="body" data-boundary="window" title="Dashboard">
                <a href="{{ url($dashboardUrl) }}" class="nav-link btn btn-icon btn-clean btn-icon-white btn-lg <?php if($currentMenuUrl == $dashboardUrl){?> active <?php } ?>">
                    <i class="flaticon2-protection icon-lg"></i>
                </a>
            </li>

            <?php
                $menuConfig = config('menuitems');
                if (isset($menuConfig) && !is_null($menuConfig) && is_array($menuConfig) && (count($menuConfig) > 0) && array_key_exists('items', $menuConfig)) {
                    $menuList = $menuConfig['items'];
                    if (is_array($menuList) && (count($menuList) > 0)) {
                        foreach ($menuList as $menuItemKey => $menuItemData) {

            ?>

            @if(
                ($menuItemData['active'])
                && (
                    is_null($menuItemData['permission_type'])
                    ||
                    (
                        (
                            (trim($menuItemData['permission_type']) == 'permission')
                            && (
                                !is_null($menuItemData['permission'])
                                && is_string($menuItemData['permission'])
                                && (trim($menuItemData['permission']) != '')
                                && \Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted(trim($menuItemData['permission']))
                            )
                        )
                        || (
                            (trim($menuItemData['permission_type']) == 'role')
                            && (
                                !is_null($menuItemData['permission'])
                                && !is_null($currentRole)
                                && is_string($currentRole)
                                && is_array($menuItemData['permission'])
                                && (count($menuItemData['permission']) > 0)
                                && in_array(strtolower(str_replace(' ', '_', trim($currentRole))), $menuItemData['permission'])
                            )
                        )
                    )
                )
            )

                @if((!is_null($menuItemData['children'])) && is_array($menuItemData['children']) && (count($menuItemData['children']) > 0))

                    <li class="nav-item dropdown">

                        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" id="main-item-{{ $menuItemKey }}">
                            @if($menuItemData['customIcon'])
                                <img src="{{ asset($menuItemData['icon']) }}"/>
                            @else
                                <i class="{{ $menuItemData['icon'] }} icon-lg"></i>
                            @endif
                        </a>

                        <ul class="dropdown-menu text-center" aria-labelledby="main-item-{{ $menuItemKey }}">

                            @foreach($menuItemData['children'] as $menuChildKey => $menuChildData)

                                @if(
                                    ($menuChildData['active'])
                                    && (
                                        is_null($menuChildData['permission_type'])
                                        ||
                                        (
                                            (
                                                (trim($menuChildData['permission_type']) == 'permission')
                                                && (
                                                    !is_null($menuChildData['permission'])
                                                    && is_string($menuChildData['permission'])
                                                    && (trim($menuChildData['permission']) != '')
                                                    && \Modules\UserRole\Http\Middleware\AuthUserPermissionResolver::permitted(trim($menuChildData['permission']))
                                                )
                                            )
                                            || (
                                                (trim($menuChildData['permission_type']) == 'role')
                                                && (
                                                    !is_null($menuChildData['permission'])
                                                    && !is_null($currentRole)
                                                    && is_string($currentRole)
                                                    && is_array($menuChildData['permission'])
                                                    && (count($menuChildData['permission']) > 0)
                                                    && in_array(strtolower(str_replace(' ', '_', trim($currentRole))), $menuChildData['permission'])
                                                )
                                            )
                                        )
                                    )
                                )

                                    <li data-toggle="tooltip" data-placement="right" data-container="body" data-boundary="window" title="{{ $menuChildData['toolTip'] }}">
                                        <a href="{{ url($menuChildData['path']) }}" class="dropdown-item btn btn-icon btn-clean btn-icon-white <?php if($currentMenuUrl == $menuChildData['path']){?> active <?php } ?>">
                                            @if($menuChildData['customIcon'])
                                                <img src="{{ asset($menuChildData['icon']) }}"/>
                                            @else
                                                <i class="{{ $menuChildData['icon'] }} icon-lg"></i>
                                            @endif
                                        </a>
                                    </li>

                                @endif

                            @endforeach

                        </ul>
                    </li>

                @else

                    <li class="nav-item mb-5" data-toggle="tooltip" data-placement="right" data-container="body" data-boundary="window" title="{{ $menuItemData['toolTip'] }}">
                        <a href="{{ url($menuItemData['path']) }}" class="nav-link btn btn-icon btn-clean btn-icon-white btn-lg <?php if($currentMenuUrl == $menuItemData['path']){?> active <?php } ?>">
                            @if($menuItemData['customIcon'])
                                <img src="{{ asset($menuItemData['icon']) }}"/>
                            @else
                                <i class="{{ $menuItemData['icon'] }} icon-lg"></i>
                            @endif
                        </a>
                    </li>

                @endif

            @endif

            <?php

                        }
                    }
                }
            ?>

        </ul>
        <!--end::Nav-->

    </div>
    <!--end::Nav Wrapper-->

</div>
