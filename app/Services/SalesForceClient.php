<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use Requests_Response;
use Requests_Exception;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use Razorpay\Trace\Logger as Trace;

class SalesForceClient
{
    protected $baseUrl;

    protected $username;

    protected $password;

    protected $client_id;

    protected $client_secret;

    protected $grant_type;

    protected $config;

    protected $trace;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.salesforce');

        $this->baseUrl = $this->config['url'];

        $this->username = $this->config['username'];

        $this->password = $this->config['password'];

        $this->client_id = $this->config['client_id'];

        $this->client_secret = $this->config['client_secret'];

        $this->grant_type = 'password';
    }

    protected function fetchAccessToken()
    {
        $url = $this->generateUrl();

        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [],
            'options' => [],
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
            ]
        ];

        $response = $this->createAndSendRequest($request);

        $accessToken = $this->parseAccessToken($response);

        if ($accessToken === null)
        {
            $this->trace->error(TraceCode::SALESFORCE_ACCESS_TOKEN_ERROR, $response);

            throw new Exception\IntegrationException('Unable to parse and fetch Access Token');
        }

        return $accessToken;
    }

    public function fetchAccountDetails()
    {
        $accessToken = $this->fetchAccessToken();

        $url  = $this->generateUrlForAccountFetch();

        $request = [
            'url'     => $url,
            'method'  => 'GET',
            'content' => [],
            'options' => [],
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                RequestHeader::AUTHORIZATION => RequestHeader::BEARER . ' ' . $accessToken,
            ]
        ];

        $response = $this->createAndSendRequest($request);

        return $response;
    }

    protected function parseAccessToken($response)
    {
        if (isset($response['access_token']) === true)
        {
            return $response['access_token'];
        }

        return null;
    }

    protected function generateUrlForAccountFetch()
    {
        return $this->baseUrl . '/data/v34.0/query?q=select Account.Merchant_ID__c, Account.Owner.Email, Owner_Role__c, Managers_in_role_hierarchy__c from Account where ( First_Transaction_Date__c != null AND Owner_Role__c != null )';
    }

    protected function generateUrl()
    {
        $queryParams = [
            'grant_type'    => $this->grant_type,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'username'      => $this->username,
            'password'      => $this->password,
        ];

        $url = $this->baseUrl . '/oauth2/token'. '?';

        $url = $url . http_build_query($queryParams);

        return $url;
    }

    protected function createAndSendRequest(array $request)
    {
        $response = $this->sendRequest($request);

        return json_decode($response->body, true);
    }

    protected function sendRequest(array $request): Requests_Response
    {
        try
        {
            $this->trace->info(TraceCode::SALESFORCE_INTEGRATION_API_REQUEST, $this->getTraceableRequest($request));

            $response = $this->getResponse($request);

            $this->traceResponse($response);
        }
        catch (Requests_Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::SALESFORCE_INTEGRATION_ERROR,
                $this->getTraceableRequest($request));

            throw new Exception\IntegrationException('Fail to fetch Salesforce Data');
        }

        return $response;
    }

    protected function traceResponse(Requests_Response $response)
    {
        $payload = [
            'status_code' => $response->status_code,
        ];

        $this->trace->info(TraceCode::SALESFORCE_INTEGRATION_API_RESPONSE, $payload);
    }

    /**
     * Filters request array and returns only traceable data
     *
     * @param  array  $request
     *
     * @return array
     */
    protected function getTraceableRequest(array $request): array
    {
        return array_only($request, ['url', 'method', 'content']);
    }

    protected function getResponse(array $request)
    {
        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['method'],
            $request['options']);

        return $response;
    }
}
