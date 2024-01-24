<?php

namespace App\Edge;

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
            'set-cookie' => true
        ]
    ];


    /**
     * Headers to be forwarded back to client from API
     * @var array
     */
    protected $headers = array(
    );

    /**
     * sets all whitelisted headers to be forwarded in response
     * @param string $path request path for request to API backend
     * @param string $method HTTP verb for request to API backend
     * @param array $allHeaders all response headers from API backend
     * @return void
     */
    public function setHeaders(string $path, string $method, array $allHeaders): void
    {
        $apiRoute = strtoupper($method) . ' ' . $path;
        if (!array_key_exists($apiRoute, self::ROUTE_TO_RESPONSE_HEADERS)) {
            return;
        }

        // array_intersect_key is being used as $allHeaders has both header key and values
        $whitelistedHeaders = array_intersect_key($allHeaders,
            self::ROUTE_TO_RESPONSE_HEADERS[$apiRoute]
        );
        $this->headers = $whitelistedHeaders;
    }

    /**
     * Returns all headers attached so far
     * @return array headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

}
