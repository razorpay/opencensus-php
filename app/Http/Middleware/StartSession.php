<?php

namespace RZP\Http\Middleware;

use Illuminate\Session\Middleware\StartSession as BaseStartSession;
use Illuminate\Support\Str;
use RZP\Trace\TraceCode;

class StartSession extends BaseStartSession
{
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
