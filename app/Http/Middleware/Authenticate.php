<?php namespace App\Http\Middleware;

use Auth;
use Gate;
use Closure;
use App\Http\AppResponse;
use Illuminate\Contracts\Auth\Guard;
use App\Trace\TraceCode;
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

                try {
                    // Sets the dashboard verified data to record mismatches (if any) in merchant and user verification.
                    app('edgeMismatchRecorder')->setDashboardVerifiedData($user->id, $currentMerchant->id ?? null);
                    app('edgeMismatchRecorder')->recordMismatches($request, "post_login");
                } catch (\Throwable $e) {
                    app('trace')->warning(TraceCode::EDGE_USER_AUTH_MISC_CODE, [
                        'trace' => $e->getTrace() ?? "unknown_trace",
                        'message' => $e->getMessage() ?? "unknown_message"
                    ]);
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
            else
            {
                try {
                    app('edgeMismatchRecorder')->recordMismatches($request, "post_login");
                } catch (\Throwable $e) {
                    app('trace')->warning(TraceCode::EDGE_USER_AUTH_MISC_CODE, [
                        'trace' => $e->getTrace() ?? "unknown_trace",
                        'message' => $e->getMessage() ?? "unknown_message"
                    ]);
                }
            }
		}
	}
}
