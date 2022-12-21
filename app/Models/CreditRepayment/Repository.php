<?php

namespace RZP\Models\CreditRepayment;


use Request;
use ApiResponse;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use Psr\Http\Message\RequestInterface;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\Request\Requests;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;
use RZP\Constants\Entity as E;

class Repository extends Base\Repository
{
    protected $entity = E::CREDIT_REPAYMENT;
    protected const CREDIT_REPAYMENT_COLLECTIONS_URL = 'v1/credit_repayments';
    protected $headers;
    protected $collectionsConfig;
    protected $collectionsBaseUrl;
    /**
     * Credit Repayment Repo constructor.
     *
     */
    public function __construct() {
        parent::__construct();

        $this->collectionsConfig          = config('applications.capital_collections');
        $this->collectionsBaseUrl         = $this->collectionsConfig['url'];

        $this->headers = [
            'X-Service-Name' => 'api',
            'X-Auth-Type' => 'internal',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function findManyWithRelations($ids, $relations, $columns = array('*'), $useWarehouse = false)
    {
        $url = self::CREDIT_REPAYMENT_COLLECTIONS_URL;
        $method = Requests::GET;
        $request = Request::instance();
        $body    = $request->all();
        $queryParams = array("ids" => join(",", $ids));
        $url .= '?'.http_build_query($queryParams);
        try
        {
            $this->headers['X-Task-Id']    = $this->app['request']->getTaskId();
            $this->headers['Authorization'] = 'Basic '. base64_encode($this->collectionsConfig['username'] . ':' . $this->collectionsConfig['secret']);

            $resp = $this->sendRequest($this->headers, $this->collectionsBaseUrl . $url, $method, empty($body) ? '' : json_encode($body));
            $creditRepayments = $resp->original['credit_repayments'];
            $creditRepaymentEntities = array();
            foreach ($creditRepayments as $cr) {
                $creditRepaymentEntities[] = Entity::create($cr);
            }
            return $creditRepaymentEntities;
        }
        catch (\Throwable $e)
        {
            unset($this->headers['Authorization']);

            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::CREDIT_REPAYMENT_SETTLEMENT_RECON_FAILURE,
                [
                    'body'       => $body,
                    'headers'    => $this->headers,
                    "full_url" => $this->collectionsBaseUrl . $url,
                ]);

            return [];
        }

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

    private function sendRequest($headers, $url, $method, $body)
    {
        $this->trace->debug(TraceCode::CREDIT_REPAYMENT_SETTLEMENT_RECON_REQUEST, [
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

        if ($resp->getStatusCode() != 200)
        {
            $traceData['body'] = $resp->getBody();
        }

        $this->trace->info(TraceCode::CREDIT_REPAYMENT_SETTLEMENT_RECON_RESPONSE, ['resp' => $traceData]);

        $span->addAttribute('http.status_code', $resp->getStatusCode());
        if ($resp->getStatusCode() != 200)
        {
            $span->addAttribute('error', 'true');
        }

        $scope->close();

        return $this->parseResponse($resp->getStatusCode(), $resp->getBody());
    }

    private function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        return ApiResponse::json($body, $code);
    }

}
