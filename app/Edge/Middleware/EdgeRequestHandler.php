<?php namespace App\Edge\Middleware;

use App\Trace\TraceCode;
use Closure;

/**
 * Middleware handler class
 * written for dashboard-backend decomp
 * This middleware is specifically for request handling
 * and should be the first middleware to execute
 */
class EdgeRequestHandler {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $isUnauthenticated = app('edgeTokenValidator')->verifyRevoked();
        if ($isUnauthenticated) {
            app('trace')->info(TraceCode::TOKEN_REVOKED_AT_EDGE_RETURNING_UNAUTHORISED, [
                "revoked" => $isUnauthenticated
            ]);
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
