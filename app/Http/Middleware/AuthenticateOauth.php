<?php

namespace App\Http\Middleware;

use App\User\Constants;
use Route;
use Closure;
use Request;

use Razorpay\Edge\Passport\Kid;
use Razorpay\Edge\Passport\Passport;

use App\Http\ApiUrl;
use App\Trace\TraceCode;
use App\Metrics\Constants as MetricConstants;

class AuthenticateOauth
{
    const MOBILE_OAUTH_WHITELIST_ROUTE_NAMES = [
        'graph_oauth',
    ];

    const WEB_OAUTH_WHITELIST_ROUTE_NAMES = [
        'oauth_user',
    ];

    const GRAPHQL_CALLBACK_WHITELIST_ROUTE_NAMES = [
        'oauth_get_org',
        'oauth_user_signin_otp',
        'oauth_user_oauth_signin',
        'oauth_user_signin',
        'oauth_user_signin_otp_verify',
        'oauth_user_verify_user_otp',
        'oauth_user_verify_user_otp_verify',
    ];

    public function handle($request, Closure $next)
    {
        app('request.ctx')->setOauthRequest(true);

        $routeName = Route::currentRouteName();

        $passportHeader = Request::header(Passport::PASSPORT_JWT_V1);

        $oauthPassport = null;

        if (empty($passportHeader) === false)
        {
            $passportJwksUrl = app('config')['app.passport_jwks_url'];

            Passport::init($passportJwksUrl, \storage_path('dashboardPassport'));

            $passport = Passport::fromToken($passportHeader);

            $oauthPassport = $passport->oauth;
        }

        if (empty($oauthPassport) === true)
        {
            // This condition is invoked when user is not authenticated yet for mobile requests
            // This call can come from mobile app or GrpahQL (callback mobile_app -> Dash -> GQL -> Dash(here) -> API)
            // In such a case bypass this middleware
            if ((in_array($routeName, self::MOBILE_OAUTH_WHITELIST_ROUTE_NAMES) === true) or
                (in_array($routeName, self::GRAPHQL_CALLBACK_WHITELIST_ROUTE_NAMES) === true) or
                (in_array($routeName, self::WEB_OAUTH_WHITELIST_ROUTE_NAMES) === true))
            {
                return $next($request);
            }
            else
            {

               app('metrics')->count(MetricConstants::PASSPORT_MISSING_FOR_OAUTH_ROUTE,
                   1,
                   [
                       MetricConstants::LABEL_HTTP_REQUESTS_ROUTE       => $routeName,
                       MetricConstants::PRODUCT                         => ApiUrl::isBankingOriginRequest() ? MetricConstants::BANKING : MetricConstants::PRIMARY,
                       MetricConstants::LABEL_API_BASE_URL              => ApiUrl::getApiHost(),
                   ]);


                app('trace')->error(TraceCode::EDGE_PRE_AUTHENTICATE_ERROR_PASSPORT_MISSING,
                                    ['routeName' => $routeName, 'passport_header' => $passportHeader]);


                return response('Unauthorized.', 401);
            }
        }

        $merchantId = $oauthPassport->ownerId ?? null;

        $userId = $oauthPassport->userId ?? null;

        if ((empty($merchantId) === true) or
            (empty($userId) === true))
        {
            // If this is a MOBILE_OAUTH_WHITELIST_ROUTE_NAMES route, then we don't fail the request
            // and let the request reach GraphRequestAuthCheck middleware
            // because in certain conditions there may be a non oauth passport being returned from edge
            // which doesn't have mid and user_id even for guest routes
            if (in_array($routeName, self::MOBILE_OAUTH_WHITELIST_ROUTE_NAMES) === true)
            {

                app('metrics')->count(MetricConstants::INVALID_PASSPORT_FOR_MOBILE_OAUTH_ROUTE,
                    1,
                    [
                        MetricConstants::LABEL_HTTP_REQUESTS_ROUTE      => $routeName,
                        MetricConstants::PRODUCT                        => ApiUrl::isBankingOriginRequest() ? MetricConstants::BANKING : MetricConstants::PRIMARY,
                        MetricConstants::LABEL_API_BASE_URL             => ApiUrl::getApiHost(),
                    ]);


                app('trace')->error(TraceCode::EDGE_PRE_AUTHENTICATE_INVALID_OAUTH_PASSPORT,
                                [
                                    'routeName' => $routeName,
                                    'passport'  => $passport,
                                ]);

                return $next($request);
            }


           app('metrics')->count(MetricConstants::INVALID_PASSPORT_FOR_OAUTH_ROUTE,
               1,
               [
                   MetricConstants::LABEL_HTTP_REQUESTS_ROUTE    => $routeName,
                   MetricConstants::PRODUCT                      => ApiUrl::isBankingOriginRequest() ? MetricConstants::BANKING : MetricConstants::PRIMARY,
                   MetricConstants::LABEL_API_BASE_URL           => ApiUrl::getApiHost(),
               ]);


            app('trace')->error(TraceCode::EDGE_PRE_AUTHENTICATE_ERROR_INVALID_PASSPORT,
                                [
                                    'routeName'   => $routeName,
                                    'merchant_id' => $merchantId,
                                    'user_id'     => $userId,
                                    'passport'    => $passport,
                                ]);

            return response('Unauthorized.', 401);
        }

        app('request.ctx')->setMerchantId($merchantId);

        app('request.ctx')->setUserId($userId);

        return $next($request);
    }
}
