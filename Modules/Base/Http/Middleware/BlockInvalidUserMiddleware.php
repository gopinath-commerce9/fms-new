<?php

namespace Modules\Base\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;

class BlockInvalidUserMiddleware
{

    protected $redirectFail = '/userauth';

    protected $redirectLoopArray = [
        'userauth',
        'userauth/index',
        'userauth/login',
        'dashboard/start',
    ];

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
                if (!Auth::guard($guard)->check()) {
                    $currentUri = $request->route()->uri();
                    if (!in_array($currentUri, $this->redirectLoopArray)) {
                        return redirect($this->redirectFail);
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
