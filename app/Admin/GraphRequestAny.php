<?php

namespace App\Admin;

use Auth;
use Config;
use Request;
use Session;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\RequestException;

use App\Http\ApiUrl;

class GraphRequestAny
{
    const COOKIE_HEADER = 'Set-Cookie';

    // true is a dummy value
    // presence any value even false will make the key whitelisted
    // For more details please refer working of array_intersect_key herew
    // https://www.php.net/manual/en/function.array-intersect-key.php
    const WHITELISTED_HEADERS_FOR_RESPONSE = [
        'Set-Cookie'                => true,
        'X-Csrf-Token'              => true,
        'X-Dashboard-Merchant-Id'   => true,
        'X-Dashboard-User-Id'       => true,
        'X-App-Mode'                => true,
        'X-Org-Id'                  => true,
    ];

    protected $request;

    protected $data;

    protected $headers;

    private static function getWhitelistedHeaders(array $allHeaders): array
    {
        return array_intersect_key(
            $allHeaders,
            self::WHITELISTED_HEADERS_FOR_RESPONSE);
    }

    function __construct(array $data)
    {
        $graphQlServerUrl = Config::get('razorpay.graphql.server_url');

        $this->request = new Guzzle([
            'base_url'  => $graphQlServerUrl,
        ]);

        $this->data = $data;

        $this->processHeaders();
    }

    public function send()
    {
        $options = [
            'headers'       => $this->headers,
            'json'          => $this->data,
        ];

        try
        {
            $response = $this->request
                             ->post(null, $options);

            $headersToBeAppended = $this->getWhitelistedHeaders($response->getheaders());

            return [$response->json(), $headersToBeAppended];

        }
        catch(RequestException $exception)
        {
            $errors = [$exception->getMessage()];

            return [$errors, null];
        }
    }

    private function processHeaders()
    {
        $this->headers = [];

        $this->addDefaultHeaders();

        $this->addProxyAuthHeadersIfUserLoggedIn();

    }

    private function addDefaultHeaders()
    {
        $domain = \Request::server('SERVER_NAME');

        $originDomain = ApiUrl::getRequestOriginUrl();

        $requestedClientIPS = \Request::ips();

        $clientIp = end($requestedClientIPS);

        $ipAddress = Request::ip();

        $userAgent = Request::header('User-Agent');

        $csrfToken = Request::header('X-Csrf-Token');

        $cookies = Request::header('Cookie');

        $orgId = Request::header('X-Org-Id');

        $apolloClientName = Request::header('apollographql-client-name');

        $apolloClientVersion = Request::header('apollographql-client-version');

        $appMode = Request::header('x-app-mode');

        $defaultHeaders =  [
            'X-Dashboard'                           => 'true',
            'X-Org-Hostname'                        => $domain,
            'X-Request-Origin'                      => $originDomain,
            'X-Dashboard-Ip'                        => $clientIp,
            'X-IP-Address'                          => $ipAddress,
            'X-User-Agent'                          => $userAgent,
            'X-Csrf-Token'                          => $csrfToken,
            'Cookie'                                => $cookies,
            'X-Org-Id'                              => $orgId,
            'apollographql-client-name'             => $apolloClientName,
            'apollographql-client-version'          => $apolloClientVersion,
            'X-App-Mode'                            => $appMode,
        ];

        $this->headers = array_merge($defaultHeaders, $this->headers);
    }

    private function addProxyAuthHeadersIfUserLoggedIn()
    {
        $user = Auth::guard('user')->user();

        if ($user)
        {

            $proxyAuthheaders = [
                'X-Dashboard-User-Id'           => $user->id,
                'X-Dashboard-User-Email'        => $user->email,
                'X-Dashboard-User-Session-Id'   => Session::getId(),
            ];

            $currentMerchant = $user->currentMerchant();

            if ($currentMerchant)
            {
                $proxyAuthheaders['X-Dashboard-User-Role']      = $currentMerchant->role;
                $proxyAuthheaders['X-Dashboard-Merchant-Id']    = $currentMerchant->id;
            }

            $this->headers = array_merge($proxyAuthheaders, $this->headers);
        }
    }


}
