<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Session\Middleware\StartSession as BaseStartSession;
use Illuminate\Support\Str;
use Predis\PredisException;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class StartSession extends BaseStartSession
{
    public function handle($request, Closure $next)
    {
        try
        {
            return parent::handle($request, $next);
        }
        catch (PredisException $e) // Catching PredisException thrown by predis.
        {
            app('trace')->traceException($e, Trace::ERROR, TraceCode::SESSION_CREATE_ERROR_FROM_CACHE, []);

            return $next($request);
        }
    }

    /**
     * @inheritDoc
     */
    protected function saveSession($request): void
    {
        $userAgent = $request->userAgent();
        $route = optional($request->route())->getName() ?? '';

        if (($route === 'merchant_checkout_preferences') &&
            Str::startsWith($userAgent, 'Razorpay/v1 PHPSDK/')
        ) {
            app('trace')->info(TraceCode::SDK_CALL_TO_PREFERENCES_ENDPOINT, [
                'key_id'     => $request->input('key_id'),
                'user_agent' => $userAgent,
                'message'    => 'Not storing session in cache',
            ]);

            // Do not store a session in cache if the request is coming from
            // PHP SDK to /v1/preferences endpoint
            return;
        }

        parent::saveSession($request);
    }
}
