<?php

namespace RZP\Http\Middleware;

use App;
use Closure;
use RZP\Trace\TraceCode;
use RZP\Models\Base\UniqueIdEntity;

class TemporaryStartSession
{
    const X_RAZORPAY_SESSION_ID = 'X-Razorpay-SessionId';

    public function handle($request, Closure $next)
    {
        $app = App::getFacadeRoot();

        if ($request->hasSession() === true)
        {
            $mode = $app['basicauth']->getMode();

            $key = $mode . '_checkcookie';

            if ($request->session()->get($key) !== '1')
            {
                $temporarySessionId = $request->headers->get(self::X_RAZORPAY_SESSION_ID, null);

                if (($temporarySessionId !== null) and
                    (UniqueIdEntity::verifyUniqueId($temporarySessionId, false) === true))
                {
                    $sessionData = [];

                    try
                    {
                        $sessionData = $app['cache']->get('temp_session:' . $temporarySessionId);
                    }
                    catch (\Throwable $e)
                    {
                        $app['trace']->traceException($e);
                    }

                    $userAgent   = $app['request']->userAgent();
                    $ip          = $app['request']->ip();

                    if ((empty($sessionData) === true) or
                        ($userAgent !== $sessionData['user_agent']) or
                        ($ip !== $sessionData['ip']))
                    {
                        $app['trace']->error(
                            TraceCode::PAYMENT_INVALID_TEMPORARY_SESSION, [
                                'actual' => [
                                    'user_agent' => $sessionData['user_agent'] ?? '',
                                    'ip' => $sessionData['ip'] ?? '',
                                ],
                                'expected' => [
                                    'user_agent' => $userAgent,
                                    'ip' => $ip,
                                ]
                            ]);
                    }
                    else
                    {
                        $request->session()->setId($sessionData['session_id']);

                        $request->session()->start();
                    }
                }
            }
        }

        return $next($request);
    }
}
