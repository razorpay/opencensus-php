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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        ApiRequest::addHeader(Headers::X_RAZORPAY_REQUEST_ID, $request->header(Headers::X_RAZORPAY_REQUEST_ID));

        $csrfToken = $request->session()->token();

        $timeStamp = microtime(true);

        $csrfTokenHeader = [
            Headers::CSRF_TOKEN => $csrfToken . ',' . $timeStamp,
        ];

        $response = $next($request);

        if($response instanceof StreamedResponse)
        {
            foreach ($csrfTokenHeader as $key => $value)
            {
                $response->headers->set($key, $value);
            }

            return $response;
        }

        $response->withHeaders($csrfTokenHeader);

        return $response;
	}
}
