<?php

namespace App\Edge\Middleware;

use Auth;
use Closure;
use App\Merchant;
use App\Trace\TraceCode;
use App\Constants\Constants;
use Illuminate\Http\Request;
use App\Utils\RegionUtils\RegionUtils;
use Illuminate\Contracts\Support\Responsable;

class RegionCookieHandler
{
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
        $cookieRegion = $request->cookie(Constants::RZP_USER_MERCHANT_REGION);

        if (!empty($region) && !empty($cookieRegion) && $region !== $cookieRegion) {
            $merchantId = (new Merchant\Service)->getCurrentMerchantId();
            app('trace')->info(TraceCode::USER_MERCHANT_REGION_MISMATCH, [
                'message' => "Merchant Region Mismatch for Merchant $merchantId Cookie Region $cookieRegion Actual Region $region",
            ]);
            return response('Unauthorized', 401);
        }

        $response = $next($request);

        $region = (new Merchant\Service)->getCurrentMerchantRegion();

        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        if (empty($region) && ($this->isInvitationFlow($request->getUri()) === true)) {
            $region = $this->extractCountryCodeFromUri((string) $request->getUri());
        }

        RegionUtils::setRegionCookies($response, $region);

        return $response;
    }

    /**
     * Extracts the country_code query parameter from a given URI.
     *
     * @param string $uri The URI to extract the country_code from.
     * @return string|null The country_code if found, null otherwise.
     */
    function extractCountryCodeFromUri($uri) {
        // Parse the URI and get its query component
        $parsedUrl = parse_url($uri);
        if (!isset($parsedUrl['query'])) {
            return null;
        }

        // Parse the query string into an associative array
        parse_str($parsedUrl['query'], $queryParams);

        // Return the country_code if it exists, otherwise return null
        return $queryParams['country_code'] ?? null;
    }

    function isInvitationFlow($uri): bool
    {
        $parsedUrl = parse_url($uri);
        if (!isset($parsedUrl['query'])) {
            return false;
        }

        // Parse the query string into an associative array
        parse_str($parsedUrl['query'], $queryParams);

        return isset($queryParams["merchant_invitation"]);
    }
}
