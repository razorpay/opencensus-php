<?php namespace App\Http\Middleware;

use Auth;
use Closure;
use Gate;
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
		if ($this->auth->guest())
		{
			if ($request->ajax())
			{
				return response('Unauthorized.', 401);
			}
			else
			{
				return redirect()->guest('auth/login');
			}
		}
		else
		{
			$user = Auth::guard('user')->user();
			ApiRequest::addHeader('X-Dashboard-Merchant', $user->email);

			if ($user)
			{
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
