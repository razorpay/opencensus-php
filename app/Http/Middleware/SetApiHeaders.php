<?php namespace App\Http\Middleware;

use Auth;
use Closure;
use Gate;
use Illuminate\Contracts\Auth\Guard;
use Razorpay\Api\Request as ApiRequest;
use App\Admin\Service as AdminService;

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
        $domain = \Request::server('SERVER_NAME');

        list($error, $org) = (new AdminService)->getOrg($domain);

        ApiRequest::addHeader('X-Org-Id', $org['id']);

        return $next($request);
	}
}
