<?php

namespace RZP\Services;

use Requests;
use Requests_Response;
use Requests_Exception;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Jobs\SalesforceRequestJob;


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

    // Constants

    const ACCESS_TOKEN    = 'access_token';
    const PRODUCT_BANKING = 'Razorpay X';

    const JSON_METHOD     = [self::POST, self::PUT, self::PATCH];

    // Request Constants
    const HEADERS               = 'headers';
    const CONTENT               = 'content';
    const OPTIONS               = 'options';
    const STATUS_CODE           = 'status_code';
    const URL                   = 'url';
    const METHOD                = 'method';
    const APPLICATION_JSON      = 'application/json';
    const TIMEOUT               = 'timeout';
    const POST                  = 'POST';
    const PUT                   = 'PUT';
    const PATCH                 = 'PATCH';

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

    protected function getAccessTokenRequest()
    {
        $url = $this->generateUrl();

        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [],
            'options' => [],
            'headers' => [
                RequestHeader::CONTENT_TYPE => 'application/json',
            ]
        ];

        return $request;
    }

    public function fetchAccessToken()
    {
        $request = $this->getAccessTokenRequest();

        $response = $this->createAndSendRequest($request);

        $accessToken = $this->parseAccessToken($response);

        if ($accessToken === null)
        {
            $this->trace->error(TraceCode::SALESFORCE_ACCESS_TOKEN_ERROR, $response);

            throw new Exception\IntegrationException('Unable to parse and fetch Access Token');
        }

        return $accessToken;
    }

    public function sendPreSignupDetails(array $input, Merchant\Entity $merchant)
    {
        $url = $this->generateUrlForMerchantUpsert();

        $data = $this->payloadGenerationForPreSignupDetails($input, $merchant);

        $this->dispatchRequestJob($url,
                                  $data,
                                  TraceCode::SALESFORCE_PRE_SIGNUP_REQUEST,
                                  TraceCode::SALESFORCE_PRE_SIGNUP_RESPONSE,
                                  TraceCode::SALESFORCE_PRE_SIGNUP_EXCEPTION
        );
    }

    public function payloadGenerationForPreSignupDetails(array $input, Merchant\Entity $merchant)
    {
        $data = ['merchant_id' => $merchant->getId(), 'email' => $merchant->getEmail(), 'name' => $merchant->getName()];

        $keyMap = [
            'business_name'      => 'business_name',
            'business_type'      => 'business_type',
            'contact_mobile'     => 'contact_mobile',
            'transaction_volume' => 'transaction_volume',
            'website'            => 'Ref_Website',
            'first_utm_campaign' => 'Traffic_Campaign',
            'first_utm_medium'   => 'Traffic_Medium',
            'first_utm_source'   => 'Traffic_Source',
        ];

        foreach ($keyMap as $key => $value)
        {
            $this->checkAndInsert($input, $key, $data, $value);
        }

        return $data;
    }

    public function checkAndInsert($input,$key, & $output, $outputKey)
    {
        if ($input != null && isset($input[$key]))
        {
            $output[$outputKey] = $input[$key];
        }
    }

    public function fetchAccountDetails($nextUrl = '')
    {
        $accessToken = $this->fetchAccessToken();

        $url = $this->generateUrlForAccountFetch($nextUrl);

        $request = [
            'url'     => $url,
            'method'  => 'GET',
            'content' => [],
            'options' => ['timeout' => 120],
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                RequestHeader::AUTHORIZATION => RequestHeader::BEARER . ' ' . $accessToken,
            ]
        ];

        $response = $this->createAndSendRequest($request);

        return $response;
    }

    public function captureInterestOfPrimaryMerchantInBanking(Merchant\Entity $merchant)
    {
        $url = $this->baseUrl . '/services/apexrest/DashboardOpportunityUpsert';

        $payload = [
            [
                "merchant_id"       => $merchant->id,
                "submission_date"   => date("Y-m-d"),
                "product_name"      => self::PRODUCT_BANKING
            ]
        ];

        $this->dispatchRequestJob($url,
                                  $payload,
                                  TraceCode::SALESFORCE_INTEREST_IN_X_REQUEST,
                                  TraceCode::SALESFORCE_INTEREST_IN_X_RESPONSE,
                                  TraceCode::SALESFORCE_INTEREST_IN_X_ERROR);
    }

    protected function parseAccessToken($response)
    {
        if (isset($response[self::ACCESS_TOKEN]) === true)
        {
            return $response[self::ACCESS_TOKEN];
        }

        return null;
    }

    protected function generateUrlForMerchantUpsert()
    {
        return $this->baseUrl . '/services/apexrest/MerchantUpsert';
    }

    protected function generateUrlForAccountFetch(string $nextUrl)
    {
        if (empty($nextUrl) === false)
        {
            return $this->baseUrl . $nextUrl;
        }

        return $this->baseUrl . '/services/data/v34.0/query?q=select Account.Merchant_ID__c, Account.Owner.Email, Owner_Role__c, Managers_in_role_hierarchy__c from Account where Owner_Role__c != null AND Merchant_ID__c != null AND ((NOT Website like \'%mswipe%\') OR (Transacting__c = true))';
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

        $url = $this->baseUrl . '/services/oauth2/token' . '?';

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
        $responseArr = json_decode($response->body, true);

        $responseBody = array_except($responseArr, self::ACCESS_TOKEN);

        $payload = [
            'status_code' => $response->status_code,
            'success'     => $response->success,
            'response'    => $responseBody,
        ];

        $this->trace->info(TraceCode::SALESFORCE_INTEGRATION_API_RESPONSE, $payload);
    }

    /**
     * Filters request array and returns only traceable data
     *
     * @param array $request
     *
     * @return array
     */
    public function getTraceableRequest(array $request): array
    {
        $request = $this->removeQueryParamsFromUrl($request);

        return array_only($request, ['url', 'method', 'content']);
    }

    /**
     * Removing Sensitive Information from Request URL
     *
     * @param array $input
     *
     * @return array
     */
    protected function removeQueryParamsFromUrl(array $input): array
    {
        $input['url'] = strtok($input['url'], '?');

        return $input;
    }

    protected function getResponse(array $request)
    {
        $content = $request['content'];

        if (in_array($request['method'], self::JSON_METHOD))
        {
            $content = json_encode($request['content']);
        }

        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $content,
            $request['method'],
            $request['options']);

        return $response;
    }

    /**
     * Dispatches a request job to queue
     *
     * @param $url
     * @param $payload
     * @param $traceCodeRequest
     * @param $traceCodeResponse
     * @param $traceCodeError
     */
    protected function dispatchRequestJob($url, $payload, $traceCodeRequest, $traceCodeResponse, $traceCodeError)
    {
        $request = [
            self::URL     => $url,
            self::METHOD  => self::POST,
            self::CONTENT => json_encode($payload),
            self::OPTIONS => [
                self::TIMEOUT => 20
            ],
            self::HEADERS => [
                RequestHeader::CONTENT_TYPE  => self::APPLICATION_JSON,
            ],
        ];

        SalesforceRequestJob::dispatch($request,
                                       $traceCodeRequest,
                                       $traceCodeResponse,
                                       $traceCodeError);
    }
}
