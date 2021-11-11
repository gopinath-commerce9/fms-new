<?php

namespace Modules\UserRole\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use App\Models\User;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;
use Modules\UserRole\Entities\Permission;
use Modules\UserRole\Entities\PermissionMap;

class AuthUserPermissionResolver
{

    /**
     * The URLs which are not to be skipped from checking.
     * @var array
     */
    private static $skippedUrls = [];

    /**
     * The Permissions which are not to be skipped from checking.
     * @var array
     */
    private static $mandatoryPermissions = [];

    /**
     * The Permissions which are to be skipped from checking.
     * @var array
     */
    private static $skippedPermissions = [];

    /**
     * The User Roles which are blocked from accessing any routes entirely.
     * @var array
     */
    private static $blacklistedRoles = [];

    /**
     * The User Roles which have a free-hand in accessing all the routes.
     * @var array
     */
    private static $superRoles = [];

    /**
     * The Users who are blocked from accessing any routes entirely.
     * @var array
     */
    private static $blacklistedUsers = [];

    /**
     * The Users who are free to access all the routes.
     * @var array
     */
    private static $superUsers = [];


    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param mixed|null $permissionCode
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permissionCode = null)
    {
        $isPermitted = self::permitted($permissionCode);
        if ($isPermitted) {
            return $next($request);
        } else {
            return back()
                ->with('message', 'The user does not have access to the page!');
        }
    }

    /**
     * Check whether the User is permitted to access to the Permission Code.
     *
     * @param mixed|null $permissionCode
     * @param UserRole|null $role
     * @param User|null $user
     *
     * @return bool
     */
    public static function permitted($permissionCode = null, UserRole $role = null, User $user = null) {

        $currentRoleId = null;
        $currentRole = null;
        $currentUserId = null;
        $currentUserName = null;

        if (!is_null($role) && $role->is_active) {
            $currentRoleId = $role->id;
            $currentRole = $role->code;
        }

        if (!is_null($user)) {
            $currentUserId = $user->id;
            $currentUserName = $user->email;
        }

        if (is_null($currentRoleId) && !is_null($currentUserId)) {
            $roleMapData = UserRoleMap::firstWhere('user_id', $currentUserId);
            if ($roleMapData && $roleMapData->is_active) {
                $mappedRoleId = $roleMapData->role_id;
                $roleData = UserRole::find($mappedRoleId);
                if ($roleData && $roleData->is_active) {
                    $currentRoleId = $roleData->id;
                    $currentRole = $roleData->code;
                }
            }
        }

        if (is_null($currentRoleId) && is_null($currentUserId) && session()->has('authUserData')) {
            $sessionUser = session('authUserData');
            $currentUserId = $sessionUser['id'];
            $currentUserName = $sessionUser['email'];
            $currentRoleId = $sessionUser['roleId'];
            $currentRole = $sessionUser['roleCode'];
        }

        if (is_null($currentRoleId) && is_null($currentUserId)) {
            $authUserData = Auth::guard('auth-user')->user();
            if ($authUserData) {
                $userDataArray = $authUserData->toArray();
                $currentUserId = $userDataArray['id'];
                $currentUserName = $userDataArray['email'];
                $roleMapData = UserRoleMap::firstWhere('user_id', $currentUserId);
                if ($roleMapData && $roleMapData->is_active) {
                    $mappedRoleId = $roleMapData->role_id;
                    $roleData = UserRole::find($mappedRoleId);
                    if ($roleData  && $roleData->is_active) {
                        $currentRoleId = $roleData->id;
                        $currentRole = $roleData->code;
                    }
                }
            }

        }

        if (is_null($currentRoleId)) {
            return false;
        }

        $checkingPermissions = self::$mandatoryPermissions;
        if (isset($permissionCode)) {
            if (is_string($permissionCode)) {
                $checkingPermissions[] = strtolower(str_replace(' ', '.', trim($permissionCode)));
            } elseif (is_array($permissionCode) && (count($permissionCode) > 0)) {
                foreach ($permissionCode as $permEl) {
                    if (is_string($permEl)) {
                        $cleanPermissionKey = strtolower(str_replace(' ', '.', trim($permEl)));
                        $checkingPermissions[] = $cleanPermissionKey;
                    }
                }
            }
        }

        if (count(self::$skippedPermissions) > 0) {
            $permissionSkipper = [];
            foreach ($checkingPermissions as $permEl) {
                if (!in_array($permEl, self::$skippedPermissions) || in_array($permEl, self::$mandatoryPermissions)) {
                    $permissionSkipper[] = $permEl;
                }
            }
            $checkingPermissions = $permissionSkipper;
        }

        if (count($checkingPermissions) == 0) {
            return true;
        }

        $permissionDataArray = Permission::whereIn('code', $checkingPermissions)
            ->where('is_active', '1')
            ->get();
        $permissionIds = [];
        if (!is_null($permissionDataArray) && (count($permissionDataArray) > 0)) {
            foreach ($permissionDataArray as $permEl) {
                $permissionIds[] = $permEl->id;
            }
        }

        if (count($permissionIds) > 0) {

            $permittedMapArray = PermissionMap::whereIn('permission_id', $permissionIds)
                ->where('role_id', $currentRoleId)
                ->get();

            if (is_null($permittedMapArray) || (count($permittedMapArray) == 0)) {
                return false;
            }

            foreach ($permittedMapArray as $permittedMapEl) {
                if (($permittedMapEl->is_active == '1') && ($permittedMapEl->permitted == '0')) {
                    return false;
                }
            }

            return true;

        }

        return true;
    }

    /**
     * @return array
     */
    public static function getAllMandatoryPermissions()
    {
        return self::$mandatoryPermissions;
    }

    /**
     * @return array
     */
    public static function getAllSkippedPermissions()
    {
        return self::$skippedPermissions;
    }

    /**
     * @return array
     */
    public static function getAllBlacklistedRoles()
    {
        return self::$blacklistedRoles;
    }

    /**
     * @return array
     */
    public static function getAllSuperRoles()
    {
        return self::$superRoles;
    }

    /**
     * @return array
     */
    public static function getAllBlacklistedUsers()
    {
        return self::$blacklistedUsers;
    }

    /**
     * @return array
     */
    public static function getAllSuperUsers()
    {
        return self::$superUsers;
    }

}
