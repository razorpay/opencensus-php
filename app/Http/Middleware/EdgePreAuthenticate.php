<?php

namespace App\Http\Middleware;

use Auth;
use Trace;
use Route;
use Config;
use Session;
use Closure;
use Request;

use App\Trace\TraceCode;

use Razorpay\Edge\Passport\Kid;
use Razorpay\Edge\Passport\Passport;

class EdgePreAuthenticate
{
    const WHITELISTED_ROUTES = [
        'get_org',
        'user_register',
        'user_verify_user_otp_verify',
        'user_verify_user_otp',
        'user_2fa_otp_resned',
        'user_signin',
        'user_signin_otp_verify',
        'user_oauth_signin',
        'user_signin_otp'
    ];

    public function handle($request, Closure $next)
    {
        app('request.ctx')->setOauthRequest(true);

        $passportHeader = Request::header(Passport::PASSPORT_JWT_V1);

        if (empty($passportHeader) === true)
        {
            $routeName = Route::currentRouteName();

            if (in_array($routeName, self::WHITELISTED_ROUTES) === false)
            {
                app('trace')->warning(TraceCode::EDGE_PRE_AUTHENTICATE_ERROR, ['routeName', $routeName]);

                return response('Unauthorized.', 401);
            }
        }

        $publicKey = app('config')['app.passport_public_key'];

        Passport::init(new Kid("edgev1", $publicKey));

        $passport = Passport::fromToken($passportHeader);

        app('request.ctx')->setMerchantId($passport->oauth->ownerId ?? null);

        app('request.ctx')->setUserId($passport->oauth->userId ?? null);

        return $next($request);
    }
}
