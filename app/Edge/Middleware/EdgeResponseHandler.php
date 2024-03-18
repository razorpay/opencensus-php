<?php namespace App\Edge\Middleware;

use App\Trace\TraceCode;
use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Middleware handler class
 * written for dashboard-backend deprecation
 * This middleware is specifically for response handling
 * and should be always the last middleware to execute
 */
class EdgeResponseHandler {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            app('edgeMismatchRecorder')->setEdgeVerifiedData($request);
        } catch (\Throwable $e) {
            app('trace')->warning(TraceCode::EDGE_USER_AUTH_MISC_CODE, [
                'trace' => $e->getTrace() ?? "unknown_trace",
                'message' => $e->getMessage() ?? "unknown_message"
            ]);
        }

        $response = $next($request);

        try {

            app('edgeMismatchRecorder')->recordMismatches($request, "login");

            $responseCookies = app('edgeResponseForwarder')->getCookies();

            if ($response instanceof StreamedResponse) {

                foreach ($responseCookies as $cookie) {
                    $response->headers->setCookie($cookie);
                }

                return $response;

            }

            foreach ($responseCookies as $cookie) {
                $response->withCookie($cookie);
            }

        }
        catch (\Throwable $e) {
            app('trace')->warning(TraceCode::EDGE_USER_AUTH_MISC_CODE, [
                'trace' => $e->getTrace() ?? "unknown_trace",
                'message' => $e->getMessage() ?? "unknown_message"
            ]);
        }

        return $response;
    }

}
