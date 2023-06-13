<?php


namespace RZP\Http\Controllers;

use Response;
use Illuminate\Http\Request;
use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;
use RZP\Http\RequestHeader;

class WalletProxyController extends EdgeProxyController
{
    public function proxy(Request $request)
    {
        // 1. Gets config for request.
        $routeName = $request->route()->getName();
        $routeCfg = $this->routeConfig[$routeName] ?? [];
        if (empty($routeCfg))
        {
            throw new IntegrationException(null, ErrorCode::SERVER_ERROR_EDGE_PROXY_NO_CONFIG);
        }
        $hostCfg = $this->hostConfig[$routeCfg['host_id']] ?? [];
        if (empty($hostCfg))
        {
            throw new IntegrationException(null, ErrorCode::SERVER_ERROR_EDGE_PROXY_NO_CONFIG);
        }

        $prefixTrim = $hostCfg['path_prefix_to_skip'] ?? "";
        $prefixAdd = $hostCfg['path_prefix_to_add'] ?? "";

        // 2. Prepares proxy request args.
        $host        = $hostCfg['host'];
        $testHost    = $hostCfg['test_host'] ?? $hostCfg['host'];
        $method      = $request->method();
        $path        = $this->getPath($request->path(), $prefixTrim, $prefixAdd);
        $body        = $request->getContent();
        $contentType = $request->getContentType();
        $auth        = $hostCfg['auth'];
        $devServeHeader = $request->header(RequestHeader::DEV_SERVE_USER);

        $query = '';
        if (($request->method() === 'GET') and
            (empty($request->all()) === false) and
            (empty($query) === true))
        {
            $query = http_build_query($request->all());
        }
        // API consumes account_id in authentication middleware, for partner auth. In wallet, we have account_id for all accounts,
        // which causes a name clash, hence dashboard prefixes account_id with issuing keyword.
        // Ref: https://razorpay.slack.com/archives/C34U44N5Q/p1681714187994329
        $query = str_replace('issuing_account_id', 'account_id', $query);
        $body = str_replace('issuing_account_id', 'account_id', $body);

        if (isset($devServeHeader) === false)
        {
            $devServeHeader = '';
        }
        if (isset($contentType) === false)
        {
            $contentType = self::CONTENT_TYPE_JSON;
        }

        $headers     = [
            RequestHeader::DEV_SERVE_USER => $devServeHeader
        ];

        // services having different live/test hosts, should set value test_host key,
        // this snippet overrides host if there is a test_host key in test mode
        if ($this->app['rzp.mode'] == "test") {
            $host = $testHost;
        }

        // 3. Makes request and returns response.
        $response = $this->request($host, $method, $path, $query, $body, $contentType, $auth, $headers);

        $response = Response::make((string) $response->getBody(), $response->getStatusCode());

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

}
