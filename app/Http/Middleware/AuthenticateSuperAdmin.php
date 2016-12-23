<?php namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Admin;

class AuthenticateSuperAdmin {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $admin = Auth::guard('api')->user();

        list($error, $data) = (new Admin\Service)->getAdminData($admin);

        if (empty($error))
        {
            $roles = $data['roles'];

            if (in_array('SuperAdmin', $roles, true))
            {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'errors' => [
                'Unauthorised'
            ]
        ]);
    }
}
