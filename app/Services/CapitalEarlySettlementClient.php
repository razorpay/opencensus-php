<?php

namespace RZP\Services;

use App;

use RZP\Exception\ServerErrorException;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Psr\Http\Message\RequestInterface;
use RZP\Exception\BadRequestException;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use RZP\Models\Settlement\Ondemand\Entity as OndemandEntity;

class CapitalEarlySettlementClient
{
    const ONDEMAND_STATUS_UPDATE_ENDPOINT = 'early_settlements/ondemand_triggers';

    protected $app;

    protected $trace;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config'];
    }

    public function pushSettlementOndemandStatusUpdate($settlementOndemandTriggerId, $event, $amount)
    {

        return $this->sendRequestAndParseResponse(self::ONDEMAND_STATUS_UPDATE_ENDPOINT,
            $this->getOndemandTriggerUpdateData($settlementOndemandTriggerId, $event, $amount),
            ['X-Auth-Type'    => 'direct',
             'X-Service-Name' => 'api'
            ],
            'PATCH'
        );
    }

    protected function getOndemandTriggerUpdateData($settlementOndemand, $event, $amount) : array
    {
        return [
            'settlement_ondemand_id'         => $settlementOndemand->getId(),
            'settlement_ondemand_trigger_id' => $settlementOndemand->getSettlementOndemandTriggerId(),
            'settlement_ondemand_status'     => $settlementOndemand->getStatus(),
            'event'                          => $event,
            'amount'                         => $amount
        ];
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        string $method,
        array $options = [])
    {
        $config                  = config('applications.capital_es');
        $baseUrl                 = $config['url'];
        $username                = $config['username'];
        $password                = $config['secret'];

        $defaultHeaders = $headers + [
                'Accept'            => 'application/json',
                'Content-Type'      => 'application/json',
                'X-Task-Id'         => $this->app['request']->getTaskId(),
                'Authorization'     => 'Basic '. base64_encode($username . ':' . $password),
            ];

        return $this->sendRequest($defaultHeaders, $baseUrl . $url, $method, empty($body) ? '' : json_encode($body));
    }

    protected function sendRequest($headers, $url, $method, $body)
    {
        $this->trace->debug(TraceCode::CAPITAL_ES_SERVICE_REQUEST, [
            'url'     => $url,
            'method'  => $method,
            'body'    => $body,
        ]);

        $req = $this->newRequest($headers, $url, $method, $body , 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        if($resp->getStatusCode() != 200)
        {
            $this->trace->warning(TraceCode::CAPITAL_ES_SERVICE_RESPONSE, [
                'status_code'   => $resp->getStatusCode(),
                'body'          => $resp->getBody(),
            ]);
        }

        if ($resp->getStatusCode() >= 500)
        {
            throw new ServerErrorException('could not complete request', ErrorCode::SERVER_ERROR);
        }
        else if($resp->getStatusCode() >= 400)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,null, null, $resp->getBody());
        }
        else
        {
            $this->trace->debug(TraceCode::CAPITAL_ES_SERVICE_RESPONSE, [
                'status_code'   => $resp->getStatusCode(),
            ]);
        }

        return json_decode($resp->getBody(), true);
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
}
