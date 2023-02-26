<?php

namespace RZP\Services;

use App;

use Request;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use Psr\Http\Message\RequestInterface;
use RZP\Exception\BadRequestException;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;

class CapitalCardsClient
{
    const CAPITAL_CARDS_CLIENT                   = 'capital_cards_client';

    const GET_CORP_CARD_ACCOUNT_DETAILS_ENDPOINT = 'v1/vendorpayment/account_details';

    const GET                                    = 'GET';

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->ba = $this->app['basicauth'];
    }

    public function getCorpCardAccountDetails(array $request)
    {
        if(empty($request['balance_id']) === true)
        {
            throw new Exception\InvalidArgumentException('balance_id is mandatory');
        }

        $url = self::GET_CORP_CARD_ACCOUNT_DETAILS_ENDPOINT.'?'.http_build_query($request);

        try
        {
            $response = $this->sendRequestAndParseResponse(
                $url,
                [],
                [   'X-Auth-Type'    => 'direct',
                    'X-Service-Name' => 'api'
                ],
                self::GET
            );

            return $response['account_detail'][0];
        }
        catch(\Throwable $e)
        {
            if($e->getCode() == ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND)
            {
                return [];
            }
            else
            {
                throw $e;
            }
        }
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

        $defaultHeaders = $headers + [
                'Accept'            => 'application/json',
                'Content-Type'      => 'application/json',
                'X-Task-Id'         => $this->app['request']->getTaskId(),
                'Authorization'     => 'Basic '. base64_encode($username . ':' . $password),
            ];

        if(empty(Request::header(RequestHeader::DEV_SERVE_USER)) === false)
        {
            $defaultHeaders[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

        return $this->sendRequest($defaultHeaders, $baseUrl . $url, $method, empty($body) ? '' : json_encode($body));
    }

    protected function sendRequest($headers, $url, $method, $body)
    {
        $this->trace->debug(TraceCode::CAPITAL_CARDS_PROXY_REQUEST, [
            'url'     => $url,
            'method'  => $method,
            'body'    => $body,
        ]);

        $req = $this->newRequest($headers, $url, $method, $body , 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        if ($resp->getStatusCode() >= 400)
        {
            $this->trace->warning(TraceCode::CAPITAL_CARDS_PROXY_RESPONSE, [
                'status_code'   => $resp->getStatusCode(),
                'body'          => $resp->getBody(),
            ]);


            if($resp->getStatusCode() == 404)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,null, null, null);
            }


            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,null, null, $resp->getBody());
        }
        else
        {
            $this->trace->debug(TraceCode::CAPITAL_CARDS_PROXY_RESPONSE, [
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
