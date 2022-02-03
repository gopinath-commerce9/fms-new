<div id="kt_header" class="header bg-white header-fixed">

    <!--begin::Container-->
    <div class="container-fluid d-flex align-items-stretch justify-content-between">

        <!--begin::Left-->
        <div class="d-flex align-items-stretch mr-2">
            <!--begin::Page Title-->
            <h3 class="d-none text-dark d-lg-flex align-items-center mr-10 mb-0">@yield('page-title')</h3>
            <!--end::Page Title-->
        </div>
        <!--end::Left-->

        <!--begin::Topbar-->
        <div class="topbar">

            <!--begin::User-->
            <div class="topbar-item">
                <?php
                    $loggedHeaderUserName = '';
                    $loggedHeaderUserRole = '';
                    $loggedHeaderUserInitials = '';
                    $loggedHeaderUserPicUrl = '';
                    if (session()->has('authUserData')) {
                        $sessionUser = session('authUserData');
                        $loggedHeaderUserName = ucwords($sessionUser['name']);
                        $loggedHeaderUserRole = ucwords($sessionUser['roleName']);
                        if (array_key_exists('userImage', $sessionUser) && !is_null($sessionUser['userImage']) && ($sessionUser['userImage'] != '')) {
                            $profilePicUrlPath = $sessionUser['userImage'];
                            $loggedHeaderUserPicUrl = (new \Modules\Base\Entities\BaseServiceHelper())->getFileUrl('media/images/users/' . $profilePicUrlPath);
                        }
                        $userDisplayNameSplitter = explode(' ', $loggedHeaderUserName);
                        foreach ($userDisplayNameSplitter as $userNameWord) {
                            $loggedHeaderUserInitials .= substr($userNameWord, 0, 1);
                        }
                    }
                ?>
                <div class="d-flex flex-column text-right pr-3">
                    <span class="text-muted font-weight-bold font-size-base d-none d-md-inline">{{ $loggedHeaderUserName }}</span>
                    <span class="text-dark-75 font-weight-bolder font-size-base d-none d-md-inline">{{ $loggedHeaderUserRole }}</span>
                </div>
                <div class="d-flex flex-column text-right pr-3">
                    <div class="symbol symbol-40 symbol-sm symbol-light-info flex-shrink-0">
                        @if ($loggedHeaderUserPicUrl != '')
                            <img class="" src="{{ $loggedHeaderUserPicUrl }}" alt="photo">
                        @else
                            <span class="symbol-label font-size-h4 font-weight-bold">{{ strtoupper($loggedHeaderUserInitials) }}</span>
                        @endif
                    </div>
                </div>
                <div class="dropdown dropdown-inline mr-2 show">
                    <button type="button" class="btn btn-light-primary font-weight-bolder dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <?= 'Hi, ' . $loggedHeaderUserName; ?>
                    </button>
                    <!--begin::Dropdown Menu-->
                    <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right show" x-placement="top-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(-62px, -165px, 0px);">
                        <!--begin::Navigation-->
                        <ul class="navi flex-column navi-hover py-2">

                            <li class="navi-item">
                                <a href="{{ route('users.profileView') }}" class="navi-link">
                                    <span class="navi-icon">
                                        <i class="fas fa-user-circle text-success mr-5"></i>
                                    </span>
                                    <span class="navi-text">My Profile</span>
                                </a>
                            </li>

                            <li class="navi-item">
                                <a href="{{ route('users.changePasswordView') }}" class="navi-link">
                                    <span class="navi-icon">
                                        <i class="fas fa-unlock text-warning mr-5"></i>
                                    </span>
                                    <span class="navi-text">Change Password</span>
                                </a>
                            </li>

                            <li class="navi-item">
                                <a href="{{ route('userauth.logout') }}" class="navi-link">
                                    <span class="navi-icon">
                                        <i class="fas fa-sign-out-alt text-danger mr-5"></i>
                                    </span>
                                    <span class="navi-text">Logout</span>
                                </a>
                            </li>

                        </ul>
                        <!--end::Navigation-->
                    </div>
                    <!--end::Dropdown Menu-->
                </div>
            </div>
            <!--end::User-->
        </div>
        <!--end::Topbar-->

    </div>
    <!--end::Container-->

</div>
