<?php


namespace RZP\Models\CapitalVirtualCards;

use Request;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use Psr\Http\Message\RequestInterface;
use RZP\Services\Mozart as MozartBase;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;


class Core extends Base\Core
{

    const MOZART_NAMESPACE = 'capital';
    const MOZART_M2P_GATEWAY = 'capital_m2p';

    public function getMozartResponse($url, $request)
    {
        $this->app['rzp.mode'] = $this->app['request']->session()->get('mode');
        $mozartResponse = (new MozartBase($this->app))->sendMozartRequest(self::MOZART_NAMESPACE,
            self::MOZART_M2P_GATEWAY,
            $url,
            $request,
            MozartBase::DEFAULT_MOZART_VERSION, false, MozartBase::TIMEOUT,
            MozartBase::CONNECT_TIMEOUT,
            false, false);
        $loggableResponse = $mozartResponse;
        if (array_key_exists('success', $mozartResponse) && $mozartResponse['success']) {
            $loggableResponse = $mozartResponse;
            unset($loggableResponse['data']);
        }
        $this->trace->debug(TraceCode::CAPITAL_VIRTUAL_CARDS_RESPONSE, [
            'mozartResponse' => $loggableResponse
        ]);
        return $mozartResponse;
    }

    public function sendCardsRequestAndParseResponse(string $url, array $body = [],
                                                     array $headers = [],
                                                     string $method,
                                                     array $options = [])
    {
        $config = config('applications.capital_cards');
        $baseUrl = $config['url'];
        $username = $config['username'];
        $password = $config['secret'];
        $timeout = $config['timeout'];
        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['X-Task-Id'] = $this->app['request']->getTaskId();
        $headers['x-merchant-id'] = $this->app['request']->session()->get('merchantId') ?? '';
        $headers['x-user-id'] = $this->app['request']->session()->get('userId') ?? '';
        $headers['X-User-Role'] = $this->app['request']->session()->get('userRole') ?? '';
        $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
        $headers['X-Auth-Type'] = 'proxy';
        $headers['x-dashboard-user-session-id'] = $this->app['request']->session()->get('sessionId') ?? '';
        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))) {
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }
        return $this->sendRequest($headers, $baseUrl . $url, $method, empty($body) ? '' : json_encode($body));
    }


    private function newRequest(array $headers, string $url, string $method, string $reqBody, string $contentType): RequestInterface
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
        $this->trace->debug(TraceCode::CAPITAL_VIRTUAL_CARDS_REQUEST, [
            'url' => $url,
            'method' => $method
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

        $traceData = [
            'status_code' => $resp->getStatusCode(),
        ];

        if ($resp->getStatusCode() >= 400) {
            $traceData['body'] = $resp->getBody();
        }

        $this->trace->info(TraceCode::CAPITAL_VIRTUAL_CARDS_RESPONSE, $traceData);

        $span->addAttribute('http.status_code', $resp->getStatusCode());
        if ($resp->getStatusCode() >= 400) {
            $span->addAttribute('error', 'true');
        }

        $scope->close();

        return $this->parseResponse($resp->getStatusCode(), $resp->getBody());
    }

    protected function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        return [$code, $body];
    }
}
