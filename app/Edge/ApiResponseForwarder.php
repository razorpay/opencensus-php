<?php

namespace App\Edge;

use GuzzleHttp\Cookie\SetCookie as CookieParser;
use App\Trace\TraceCode;
/**
 * manages response headers received from API
 * which needs to be forwarded
 */
class ApiResponseForwarder
{

    /**
     * Stores the list of headers to be forwarded
     * from API response back to client against each dashboard route
     * true is a dummy value (even false will make the key whitelisted)
     * or more details please refer working of array_intersect_key here
     * https://www.php.net/manual/en/function.array-intersect-key.php
     * @var array
     */
    const ROUTE_TO_RESPONSE_HEADERS = [
        'POST users/login' => [
            "set-cookie" => true,
        ]
    ];

    const COOKIE_HEADER = "set-cookie";

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
                $apiRoute = strtoupper($method) . ' ' . $path;
                if (!array_key_exists($apiRoute, self::ROUTE_TO_RESPONSE_HEADERS)) {
                    return;
                }

                // array_intersect_key is being used as $allHeaders has both header key and values
                $whitelistedHeaders = array_intersect_key($allHeaders,
                    self::ROUTE_TO_RESPONSE_HEADERS[$apiRoute]
                );

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
    private function setCookies(string $cookieString): void
    {
        try {
            $setCookie = CookieParser::fromString($cookieString);

            $maxAgeInMinutes = $setCookie->getMaxAge() === null ? 0 : (int)($setCookie->getMaxAge()/60);

            $cookie = cookie(
                $setCookie->getName(),
                $setCookie->getValue(),
                $maxAgeInMinutes,
                $setCookie->getPath(),
                $setCookie->getDomain(),
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
