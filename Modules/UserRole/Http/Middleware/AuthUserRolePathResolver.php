<?php

namespace Modules\UserRole\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Route;
use Modules\UserRole\Entities\UserRole;
use App\Models\User;
use Modules\UserRole\Entities\UserRoleMap;

class AuthUserRolePathResolver
{

    protected $redirectFail = '/userauth';

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

                $currentUri = $request->route()->uri();
                $uriSplitter = explode('/', $currentUri);
                $roleUri = $uriSplitter[0];
                $roleUriClean = strtolower(str_replace(' ', '_', trim(urldecode($roleUri))));

                $isUriARole = false;
                $roleListFilter = UserRole::firstWhere('code', $roleUriClean);
                if ($roleListFilter) {
                    $isUriARole = true;
                }

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

                    if ($isUriARole && (is_null($currentRole) || (!is_null($currentRole) && ($roleUriClean !== $currentRole)))) {
                        if (!is_null($currentRole) && Route::has($currentRole . '.index')) {
                            return redirect()->route($currentRole . '.index')->with('message', 'The user does not have access to the page!');
                        } else {
                            return redirect($this->redirectPass)->with('message', 'The user does not have access to the page!');
                        }
                    } else {
                        return $response;
                    }

                } else {
                    if ($isUriARole) {
                        return redirect($this->redirectFail)->with('message', 'The user does not have access to the page!');
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
