<?php namespace App\Http\Middleware;

use Auth;
use Gate;
use Session;
use Closure;
use App\Http\ApiUrl;
use App\Http\Headers;
use App\Trace\TraceCode;
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

        ApiRequest::addHeader('X-Dashboard-User-Session-Id', Session::getId());

        ApiRequest::addHeader(Headers::DEV_SERVE_USER,$request->header(Headers::DEV_SERVE_USER));

        $csrfToken = $request->session()->token();

        $timeStamp = microtime(true);

        $csrfTokenHeader = [
            Headers::CSRF_TOKEN => $csrfToken . ',' . $timeStamp,
        ];

        $response = $next($request);

        $response->withHeaders($csrfTokenHeader);

        return $response;
	}
}
