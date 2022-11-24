<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Config;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Illuminate\Support\Facades\Mail;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use Psr\Http\Message\RequestInterface;
use Request;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Mail\CapitalCards\Base;
use RZP\Models\Admin\Permission\Category as PermissionCategory;
use RZP\Models\D2cBureauReport\Provider;
use RZP\Services\Mozart;
use RZP\Services\Mozart as MozartBase;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;


class CapitalCardsController extends Controller
{
    const GET      = 'GET';
    const POST     = 'POST';
    const PUT      = 'PUT';
    const PATCH    = 'PATCH';
    const DELETE   = 'DELETE';
    const MERCHANT = 'MERCHANT';

    const MAIL_ERROR_REGEX = '/View \[emails.capital_cards.(?:\w+)?\] not found./';

    protected function handlePhysicalCardRequest(){
        return $this->handleProxyRequests('v1/carddelivery');
    }

    protected function handleProxyRequests($path = null)
    {

        $sessionId = $headers['x-dashboard-user-session-id'][0] ?? '';
        $url     = $path;
        $request = Request::instance();
        $body    = $request->all();
        $this->trace->debug(TraceCode::CAPITAL_CARDS_PROXY_REQUEST, [
            'request' => $url,
        ]);
        $headers = [
            'x-merchant-id'    => optional($this->ba->getMerchant())->getId() ?? '',
            'X-Merchant-Email' => optional($this->ba->getMerchant())->getEmail() ?? '',
            'x-user-id'        => optional($this->ba->getUser())->getId() ?? '',
            'X-User-Role'      => $this->ba->getUserRole() ?? '',
            'X-Auth-Type'      => 'proxy',
            'x-otp'            => $body['otp'] ?? '',
            'x-dashboard-user-session-id'        => $sessionId,
        ];

        if (($request->method() === 'GET') and
            (empty($body) === false))
        {
            $url .= '?' . http_build_query($body);
        }

        $response = $this->sendRequestAndParseResponse($url, $body, $headers, $request->method());

        return $response;
    }

    protected function handleWebhook($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::CAPITAL_CARDS_WEBHOOK_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Auth-Type'   => 'internal',
            'X-Service-Name' => 'api'
        ];

        return $this->sendRequestAndParseResponse($url, $body, $headers, $request->method());
    }

    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->debug(TraceCode::CAPITAL_CARDS_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin',
            'X-Admin-Permissions' => $this->getCapitalPermissionsStringForAdmin(),
        ];

        if (isset($body['merchant_id']) === true)
        {
            $headers['x-merchant-id'] = $body['merchant_id'];
        }

        if (isset($body['user_id']) === true)
        {
            $headers['x-user-id'] = $body['user_id'];
        }

        if ($request->getQueryString() !== null)
        {
            $url .= '?' . $request->getQueryString();
        }
        else if (($request->method() === 'GET') and
                (empty($body) === false))
        {
            $url .= '?' . http_build_query($body);
        }

        $response = $this->sendRequestAndParseResponse($url, $body, $headers, $request->method());

        return $response;
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        string $method,
        array $options = [])
    {
        $config                  = config('applications.capital_cards');
        $baseUrl                 = $config['url'];
        $username                = $config['username'];
        $password                = $config['secret'];
        $timeout                 = $config['timeout'];
        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['X-Task-Id']    = $this->app['request']->getTaskId();
        $headers['Authorization'] = 'Basic '. base64_encode($username . ':' . $password);
        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }
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
        $this->trace->debug(TraceCode::CAPITAL_CARDS_PROXY_REQUEST, [
            'url'     => $url,
            'method'  => $method,
        ]);

        $span = Tracer::startSpan(Requests::getRequestSpanOptions($url));
        $scope = Tracer::withSpan($span);
        $span->addAttribute('http.method', $method);

        $arrHeaders = new ArrayHeaders($headers);
        Tracer::injectContext($arrHeaders);
        $headers = $arrHeaders->toArray();

        $req = $this->newRequest($headers, $url, $method, $body , 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        $traceData = [
            'status_code'   => $resp->getStatusCode(),
        ];

        if ($resp->getStatusCode() >= 400)
        {
            $traceData['body'] = $resp->getBody();
        }

        $this->trace->info(TraceCode::CAPITAL_CARDS_PROXY_RESPONSE, $traceData);

        $span->addAttribute('http.status_code', $resp->getStatusCode());
        if ($resp->getStatusCode() >= 400)
        {
            $span->addAttribute('error', 'true');
        }

        $scope->close();

        return $this->parseResponse($resp->getStatusCode(), $resp->getBody());
    }

    protected function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        return ApiResponse::json($body, $code);
    }

    protected function sendMail()
    {
        $request = Request::instance();
        $data = $request->all();
        if ((isset($data['to']) === false) or
            (isset($data['merchant_id']) === false) or
            (isset($data['template']) === false) or
            (isset($data['subject']) === false)) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }
        if (isset($data['attachment']) === true && strlen($data['attachment'])!=0)
        {
            $ufhService = $this->app['ufh.service'];
            $signedUrlResponse = $ufhService->getSignedUrl($data['attachment'],[],$data["merchant_id"]);
            $data['file'] = $signedUrlResponse;
        }
        try {
            $mail = new Base($data);
            Mail::queue($mail);
        } catch (\Throwable $e) {
            if (preg_match(self::MAIL_ERROR_REGEX, $e->getMessage(), $matches) === 1) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, $data, $e->getMessage());
            } else {
                throw $e;
            }
        }

        return ApiResponse::json(['success' => true]);

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

