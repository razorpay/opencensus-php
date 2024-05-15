<?php

namespace App\Edge;

use App\Constants\Constants;
use GuzzleHttp\Cookie\SetCookie as CookieParser;
use App\Trace\TraceCode;
/**
 * manages response headers received from API
 * which needs to be forwarded
 */
class ApiResponseForwarder
{
    const COOKIE_HEADER = "set-cookie";

    /**
     * Stores the list of headers to be that needs to be present
     * in API response, if yes then forward otherwise return. (Not dependent on Route)
     * true is a dummy value (even false will make the key whitelisted)
     * or more details please refer working of array_intersect_key here
     * https://www.php.net/manual/en/function.array-intersect-key.php
     * @var array
     */
    const RESPONSE_HEADERS = [
        self::COOKIE_HEADER => true
    ];

    /**
     * Headers to be forwarded back to client from API
     * @var array
     */
    protected $headers = array(
    );

    protected $cookies = array(
    );

    /**
     * sets all whitelisted headers and cookies to be forwarded in response
     * @param string $path request path for request to API backend
     * @param string $method HTTP verb for request to API backend
     * @param array $allHeaders all response headers from API backend
     * @return void
     */
    public function setHeaders(string $path, string $method, array $allHeaders): void
    {
        try {
                // array_intersect_key is being used as $allHeaders has both header key and values
                $whitelistedHeaders = array_intersect_key($allHeaders,
                    self::RESPONSE_HEADERS
                );
                // Return if the interesect doesn't have any value
                if ( empty($whitelistedHeaders) ) {
                    return;
                }

                foreach ($whitelistedHeaders as $key => $value) {
                    if ($key === self::COOKIE_HEADER) {
                        if (is_array($value)) {
                            foreach ($value as $cookie) {
                                $this->setCookies($cookie);
                            }
                        } else {
                            $this->setCookies($value);
                        }

                    } else {
                        $this->headers[] = [$key => $value];
                    }
                }

        } catch (\Exception $e) {
            app('trace')->error(TraceCode::EDGE_SETTING_HEADERS_FROM_API_FAILED, [
                "path"    => $path,
                "method"  => $method,
                "error" => $e->getMessage() ?? "unknown error",
            ]);
        }

    }

    /**
     * sets all whitelisted cookies to be forwarded in response as cookies after parsing.
     * @param string $path
     * @param string $method
     * @param array $allHeaders
     * @return void
     */
    public function setCookies(string $cookieString): void
    {
        try {
            $setCookie = CookieParser::fromString($cookieString);

            $maxAgeInMinutes = $setCookie->getMaxAge() === null ? 0 : (int)($setCookie->getMaxAge()/60);

            $cookie = cookie(
                $setCookie->getName(),
                $setCookie->getValue(),
                $maxAgeInMinutes,
                // we are explicitly setting the cookie domain as null and path as '/'
                // similar to how it's being set for rzp_usr_session.
                Constants::ROOT_PATH,
                null,
                $setCookie->getSecure(),
                $setCookie->getHttpOnly()
            );
            $this->cookies[] = $cookie;

        } catch (\Exception $e) {
            app('trace')->error(TraceCode::EDGE_COOKIE_PARSING_FAILED, [
                "cookie"    => $cookieString ?? "unknown cookie",
                "error" => $e->getMessage() ?? "unknown error",
            ]);
        }
    }

    /**
     * Returns all headers attached so far
     * @return array headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

}
