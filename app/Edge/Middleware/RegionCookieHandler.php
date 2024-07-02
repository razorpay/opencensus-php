<?php

namespace App\Edge\Middleware;

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
        $response = $next($request);

        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        $region = (new Merchant\Service)->getCurrentMerchantRegion();
        $config = config('session');

        // set the cookie only if region is available
        if (! empty($region)) {
            // same as user session cookie
            $response->headers->setCookie(new Cookie(
                self::RZP_USER_MERCHANT_REGION, $region, $this->getCookieExpirationDate($config),
                $config['path'], null, $config['secure'] ?? false,
                $config['http_only'] ?? true, false, $config['same_site'] ?? null
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
