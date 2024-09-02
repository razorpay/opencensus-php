<?php

namespace App\Edge\Middleware;

use Auth;
use App\Trace\TraceCode;
use App\Merchant;
use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Cookie;

class RegionCookieHandler
{
    const RZP_USER_MERCHANT_REGION = 'rzp_user_merchant_region';

    /**
     * Handle an incoming request and set region cookie.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $region = (new Merchant\Service)->getCurrentMerchantRegion();
        // We check the region in the cookie if it matches with the logged in merchant region,
        // if it doesn't then we throw Bad Request Error and consider User has manually edited the Cookie
        // and should result in a Client Error so the request would be Forbidden
        $cookieRegion = $request->cookie(self::RZP_USER_MERCHANT_REGION);
        if (!empty($region) && !empty($cookieRegion) && $region !== $cookieRegion) {
            $merchantId = (new Merchant\Service)->getCurrentMerchantId();
            app('trace')->info(TraceCode::USER_MERCHANT_REGION_MISMATCH, [
                'message' => "Merchant Region Mismatch for Merchant $merchantId Cookie Region $cookieRegion Actual Region $region",
            ]);
            return response('Unauthorized', 401);
        }

        $response = $next($request);

        $region = (new Merchant\Service)->getCurrentMerchantRegion();
        $config = config('session');

        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        // set the cookie only if region is available
        if (! empty($region)) {
            // same as user session cookie
            $response->headers->setCookie(new Cookie(
                self::RZP_USER_MERCHANT_REGION, $region, $this->getCookieExpirationDate($config),
                $config['path'], null, $config['secure'] ?? false,
                $config['http_only'] ?? false, false, $config['same_site'] ?? null
            ));
        }

        return $response;
    }

    /**
     * Get the cookie lifetime in seconds.
     *
     * @return \DateTimeInterface|int
     */
    protected function getCookieExpirationDate($config)
    {
        // same as user session cookie
        return Date::instance(Carbon::now()->addRealMinutes($config['lifetime']));
    }
}
