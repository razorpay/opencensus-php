<?php namespace App\Http\Middleware;

use Auth;
use Gate;
use Closure;
use App\Http\AppResponse;
use Illuminate\Contracts\Auth\Guard;
use Razorpay\Api\Request as ApiRequest;

class Authenticate {

	/**
	 * The Guard implementation.
	 *
	 * @var Guard
	 */
	protected $auth;

	/**
	 * Create a new filter instance.
	 *
	 * @param  Guard  $auth
	 * @return void
	 */
	public function __construct(Guard $auth)
	{
		$this->auth = $auth;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
        $routeName = $request->route()->getName();

        if (app('request.ctx')->isOauthRequest() === true)
        {
            return $next($request);
        }

        if ($this->auth->guest() === true)
		{
            return AppResponse::unauthorizedResponse('Unauthorized.', $routeName, '/?next='.$request->path());
		}
		else
		{
			$user = Auth::guard('user')->user();

			if ($user)
			{
				ApiRequest::addHeader('X-Dashboard-User-Id', $user->id);
				ApiRequest::addHeader('X-Dashboard-User-Email', $user->email);

        		$currentMerchant = $user->currentMerchant();

				if ($currentMerchant !== null)
				{
					ApiRequest::addHeader('X-Dashboard-User-Role', $currentMerchant->role);
				}

				if (!Gate::has($routeName))
				{
					return $next($request);
				}
				if (Gate::allows($routeName, $user))
				{
		            return $next($request);
		        }
		        else
		        {
		            return response('Unauthorized.', 401);
		        }
			}
		}
	}
}
