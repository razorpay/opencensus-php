<?php

namespace RZP\Http\Controllers;

use View;
use Config;
use Request;
use ApiResponse;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Psr\Http\Message\RequestInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use RZP\Models\Admin\Permission\Category as PermissionCategory;

class CapitalLenderController extends Controller
{
    const GET      = 'GET';
    const POST     = 'POST';
    const PUT      = 'PUT';
    const PATCH    = 'PATCH';
    const DELETE   = 'DELETE';

    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url = $path;
        $body = $request->all();

        $this->trace->info(TraceCode::CAPITAL_LENDER_ADMIN_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Admin-Id'          => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email'       => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Admin-Permissions' => $this->getCapitalPermissionsStringForAdmin(),
            'X-Auth-Type'         => 'admin'
        ];

        if ($request->getQueryString() !== null) {
            $url .= '?' . $request->getQueryString();
        } else if (($request->method() === 'GET') and
            (empty($body) === false)) {
            $url .= '?' . http_build_query($body);
        }

        $response = $this->sendRequestAndParseResponse($url, $request->method(), $body, $headers);

        return $response;
    }

    protected function handleDevAdminRequests($path = null)
    {
        $request = Request::instance();
        $url = $path;
        $body = $request->all();

        $this->trace->info(TraceCode::CAPITAL_LENDER_DEV_ADMIN_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

        return $this->sendRequestAndParseResponse($url, $request->method(), $body, $headers);
    }

    protected function sendRequestAndParseResponse(
        string $url,
        string $method,
        array  $body = [],
        array  $headers = [])
    {
        $config = config('applications.capital_lender');
        $baseUrl = $config['url'];
        $username = $config['username'];
        $password = $config['secret'];
        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['X-Task-Id'] = $this->app['request']->getTaskId();
        $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);

        return $this->sendRequest($headers, $baseUrl . $url, $method, empty($body) ? '' : json_encode($body));
    }

    private function newRequest(array $headers, string $url, string $method, string $reqBody, string $contentType):
    RequestInterface
    {
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();

        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        $body = $streamFactory->createStream($reqBody);

        $req = $requestFactory->createRequest($method, $url);

        foreach ($headers as $key => $value) {
            $req = $req->withHeader($key, $value);
        }

        return $req
            ->withBody($body)
            ->withHeader('Accept', $contentType)
            ->withHeader('Content-Type', $contentType);
    }

    protected function sendRequest($headers, $url, $method, $body)
    {
        $this->trace->debug(TraceCode::CAPITAL_LENDER_REQUEST, [
            'url' => $url,
            'method' => $method,
        ]);

        $span = Tracer::startSpan(Requests::getRequestSpanOptions($url));
        $scope = Tracer::withSpan($span);
        $span->addAttribute('http.method', $method);

        $arrHeaders = new ArrayHeaders($headers);
        Tracer::injectContext($arrHeaders);
        $headers = $arrHeaders->toArray();

        $req = $this->newRequest($headers, $url, $method, $body, 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        $span->addAttribute('http.status_code', $resp->getStatusCode());

        $traceData = [
            'status_code' => $resp->getStatusCode(),
        ];

        if ($resp->getStatusCode() >= 400) {
            $traceData['body'] = $resp->getBody();
        }

        $this->trace->info(TraceCode::CAPITAL_LENDER_RESPONSE, $traceData);

        $scope->close();

        return $this->parseResponse($resp->getStatusCode(), $resp->getBody());
    }

    protected function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        if ($code >= 400) {
            $error = array();
            if ($body["details"] != null && is_array($body["details"]) && !empty($body["details"]) && $body["details"][0]["description"] != null) {
                $error["description"] = $body["details"][0]["description"];
            } else if ($body["message"] != null) {
                $error["description"] = $body["message"];
            } else {
                $error["description"] = "error occurred";
            }
            $body["error"]= $error;
        }

        return ApiResponse::json($body, $code);
    }

    protected function getCapitalPermissionsStringForAdmin()
    {
        $permissions = $this->ba->getAdmin()->getPermissionsList();
        $permissionsString = "";
        $permissionCategories = Config::get('heimdall.permissions');
        $capitalPermissions = $permissionCategories[PermissionCategory::RAZORPAY_CAPITAL];
        foreach ($permissions as $permission) {
            if (isset($capitalPermissions[$permission])) {
                $permissionsString .= $permission . ":";
            }
        }
        return substr($permissionsString, 0, -1);
    }
}
