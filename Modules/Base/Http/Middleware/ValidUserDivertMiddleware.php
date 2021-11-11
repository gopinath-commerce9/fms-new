<?php

namespace Modules\Base\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Route;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;

class ValidUserDivertMiddleware
{

    /**
     * Where to redirect admins after login.
     *
     * @var string
     */
    protected $redirectPass = '/dashboard';

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {

        $response = $next($request);

        switch($guard){
            case 'auth-user':
                if (Auth::guard($guard)->check()) {

                    $currentRoleId = null;
                    $currentRole = null;
                    $guardUserData = Auth::guard($guard)->user();
                    if ($guardUserData) {
                        $roleMapData = UserRoleMap::firstWhere('user_id', $guardUserData->id);
                        if ($roleMapData && $roleMapData->is_active) {
                            $mappedRoleId = $roleMapData->role_id;
                            $roleData = UserRole::find($mappedRoleId);
                            if ($roleData  && $roleData->is_active) {
                                $currentRoleId = $roleData->id;
                                $currentRole = $roleData->code;
                            }
                        }
                    }

                    if (Route::has($currentRole . '.index')) {
                        return redirect()->route($currentRole . '.index');
                    } else {
                        return redirect($this->redirectPass);
                    }

                }
                break;
            default:
                return $response;
                break;
        }

        return $response;

    }
}
