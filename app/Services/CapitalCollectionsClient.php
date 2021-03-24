<?php

namespace RZP\Services;

use App;

use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use Psr\Http\Message\RequestInterface;
use RZP\Exception\BadRequestException;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;

class CapitalCollectionsClient implements ExternalService
{
    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->ba = $this->app['basicauth'];
    }

    public function fetchMultiple(string $entity, array $input)
    {
        $this->trace->debug(TraceCode::CAPITAL_COLLECTIONS_PROXY, $input);

        $request = [
            'from'          => $input['from'] ?? 0,
            'to'            => $input['to'] ?? 0,
            'skip'          => $input['skip'] ?? 0,
            'count'         => $input['count'] ?? 20,
            'entity_name'   => $entity,
        ];

        unset($input['from']);
        unset($input['to']);
        unset($input['skip']);
        unset($input['count']);

        if (empty($input) === false)
        {
            $request['filter'] = $input;
        }

        $response = $this->sendRequestAndParseResponse('v1/entities', $request, [], 'POST');

        $entities = $response['entities'][$entity];

        return [
            'entity'    => 'collection',
            'count'     => count($entities),
            'items'     => $entities,
        ];
    }

    public function fetch(string $entity, string $id, array $input)
    {
        return $this->sendRequestAndParseResponse('v1/entity/'. $entity . '/'. $id, [], [], 'GET')['entity'];
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        string $method,
        array $options = [])
    {
        $config                  = config('applications.capital_collections');
        $baseUrl                 = $config['url'];
        $username                = $config['username'];
        $password                = $config['secret'];

        $headers += [
            'Accept'            => 'application/json',
            'Content-Type'      => 'application/json',
            'X-Task-Id'         => $this->app['request']->getTaskId(),
            'X-Admin-Id'        => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email'     => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'       => 'admin',
            'Authorization'     => 'Basic '. base64_encode($username . ':' . $password),
        ];

        return $this->sendRequest($headers, $baseUrl . $url, $method, empty($body) ? '' : json_encode($body));
    }

    protected function sendRequest($headers, $url, $method, $body)
    {
        $this->trace->debug(TraceCode::CAPITAL_COLLECTIONS_PROXY_REQUEST, [
            'url'     => $url,
            'method'  => $method,
            'body'    => $body,
        ]);

        $req = $this->newRequest($headers, $url, $method, $body , 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        if ($resp->getStatusCode() >= 400)
        {
            $this->trace->warning(TraceCode::CAPITAL_COLLECTIONS_PROXY_RESPONSE, [
                'status_code'   => $resp->getStatusCode(),
                'body'          => $resp->getBody(),
            ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,null, null, $resp->getBody());
        }
        else
        {
            $this->trace->debug(TraceCode::CAPITAL_COLLECTIONS_PROXY_RESPONSE, [
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
