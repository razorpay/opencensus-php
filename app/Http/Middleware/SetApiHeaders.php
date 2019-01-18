<?php namespace App\Http\Middleware;

use Auth;
use Gate;
use Closure;
use App\Http\ApiUrl;
use Illuminate\Contracts\Auth\Guard;
use Razorpay\Api\Request as ApiRequest;

class SetApiHeaders {

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
        $originDomain = ApiUrl::getRequestOriginUrl();

        $domain = \Request::server('SERVER_NAME');

        ApiRequest::addHeader('X-Org-Hostname', $domain);

        ApiRequest::addHeader('X-Request-Origin', $originDomain);

        return $next($request);
	}
}
