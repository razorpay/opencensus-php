<?php namespace App\Http\Middleware;

use Auth;
use Gate;
use Closure;
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
		if ($this->auth->guest() === true)
		{
			if ($request->ajax() === true)
			{
				return response('Unauthorized.', 401);
			}
			else
			{
				return redirect()->guest('/');
			}
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

				$routeName = $request->route()->getName();

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
