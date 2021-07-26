<?php

namespace RZP\Services;

use App;

use RZP\Trace\Tracer;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use Psr\Http\Message\RequestInterface;
use RZP\Exception\BadRequestException;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;

class ExternalServiceClient implements ExternalService
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
        $this->trace->debug(TraceCode::EXTERNAL_SERVICE_PROXY, $input);

        $entityArray = $this->getServiceNameAndEntityName($entity);

        $request = [
            'from'          => $input['from'] ?? 0,
            'to'            => $input['to'] ?? 0,
            'skip'          => $input['skip'] ?? 0,
            'count'         => $input['count'] ?? 20,
            'entity_name'   => $entityArray[1],
        ];

        unset($input['from']);
        unset($input['to']);
        unset($input['skip']);
        unset($input['count']);

        if (empty($input) === false)
        {
            $request['filter'] = $input;
        }

        $response = $this->sendRequestAndParseResponse('twirp/rzp.common.dashboard.v1.Dashboard/FetchMultiple', $request, $entityArray[0], 'POST');

        $entities = $response['entities'][$entityArray[1]];

        return [
            'entity'    => $entityArray[0],
            'count'     => count($entities),
            'items'     => $entities,
        ];
    }

    public function fetch(string $entity, string $id, array $input)
    {
        $this->trace->debug(TraceCode::EXTERNAL_SERVICE_PROXY_REQUEST, [
            'entity'    => $entity,
            'id'        => $id,
            'input'     => $input,
        ]);

        $entityArray = $this->getServiceNameAndEntityName($entity);

        $request = [
            'id'          => $id,
            'entity_name' => $entityArray[1],
        ];
        return $this->sendRequestAndParseResponse('twirp/rzp.common.dashboard.v1.Dashboard/Fetch', $request, $entityArray[0], 'POST')['entity'];
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        string $serviceName,
        string $method,
        array $options = [])
    {
        try
        {
            $config  = config('applications.'.$serviceName);
        }
        catch(\Throwable $e)
        {
            throw new Exception\ServerErrorException(
                'invalid serviceName: '.$serviceName,
                ErrorCode::SERVER_ERROR_CONFIG_READ_ERROR,
                null,
                $e
            );
        }

        $baseUrl                 = $config['url'];
        $username                = $config['username'];
        $password                = $config['secret'];

        $headers = [
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
        $this->trace->debug(TraceCode::EXTERNAL_SERVICE_PROXY_REQUEST, [
            'url'     => $url,
            'method'  => $method,
            'body'    => $body,
        ]);

        $req = $this->newRequest($headers, $url, $method, $body , 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        if ($resp->getStatusCode() >= 400)
        {
            $this->trace->debug(TraceCode::EXTERNAL_SERVICE_PROXY_RESPONSE, [
                'status_code'   => $resp->getStatusCode(),
                'body'          => $resp->getBody(),
            ]);
            throw new Exception\TwirpException($resp->getBody());
        }
        else
        {
            $this->trace->debug(TraceCode::EXTERNAL_SERVICE_PROXY_RESPONSE, [
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

    private function getServiceNameAndEntityName(string $entity)
    {
        if(strpos($entity, '.') == true)
        {
            return explode('.', $entity);
        }
        else
        {
            throw new Exception\LogicException('invalid entity name: '. $entity);
        }
    }

}
