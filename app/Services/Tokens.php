<?php

namespace RZP\Services;

use Razorpay\Edge\Passport\Passport;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\RuntimeException;
use RZP\Http\Request\Requests;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Models\Customer\Token\Entity as TokenEntity;
use RZP\Models\PaymentsUpi\Vpa\Entity as VpaEntity;
use RZP\Trace\TraceCode;
use RZP\Exception;
use \WpOrg\Requests\Exception as Requests_Exception;

class Tokens
{
    protected $trace;

    protected $baseUrl;

    protected $config;

    protected $key;

    protected $secret;

    protected $mode;

    protected $request;

    protected $headers;

    protected $auth;

    const TokensBaseURL = 'tokens';

    // Headers
    const ACCEPT            = 'Accept';
    const X_MODE            = 'X-Mode';
    const ADMIN_EMAIL       = 'X-Dashboard-Admin-Email';
    const CONTENT_TYPE      = 'Content-Type';
    const X_REQUEST_ID      = 'X-Request-ID';
    Const X_INIT_SOURCE     = 'X-INIT-SOURCE';
    Const ROUTE_NAME        = 'route-name';

    const DEFAULT_REQUEST_TIMEOUT = 60;

    const URLS = [
        'fetch_tokens_multiple' => 'fetch',
        'fetch_tokens_by_ids'   => 'fetch_by_ids',
        'update_token_by_id'    => 'update'
    ];


    /**
     * Tokens constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.tokens');

        $this->mode = $app['rzp.mode'];

        $this->baseUrl = $this->config['url'][$this->mode];

        $this->request = $app['request'];

        $this->key = $this->config['key'][$this->mode];

        $this->secret = $this->config['secret'][$this->mode];

        $this->auth = $app['basicauth'];

        $this->route = $app['api.route'];

        $this->setHeaders();
    }

    /**
     * Function used to set headers for the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]        = 'application/json';
        $headers[self::CONTENT_TYPE]  = 'application/json';
        $headers[self::X_MODE]        = $this->mode;
        $headers[self::ADMIN_EMAIL]   = $this->getAdminEmail();
        $headers[self::X_REQUEST_ID]  = $this->request->getId();
        $headers[self::X_INIT_SOURCE] = $this->auth->getInternalApp();

        $this->headers = $headers;
    }

    /**
     * @return string
     */
    protected function getAdminEmail(): string
    {
        return $this->auth->getDashboardHeaders()['admin_email'] ?? '';
    }

    public function fetchToken($input)
    {
        $resp = $this->fetchTokensInternal($input);

        $token = $this->forceFillTokensFromResponse($resp['body']['data'][0]);

        return $this->loadRelatedEntity($token);
    }

    public function fetchTokens($input)
    {
        $resp = $this->fetchTokensInternal($input);

        $tokens = new PublicCollection();

        foreach ($resp['body']['data'] as $tok)
        {
            $token = $this->forceFillTokensFromResponse($tok);

            $token = $this->loadRelatedEntity($token);

            $tokens->add($token);
        }

        return $tokens;
    }

    public function fetchCustomerTokens($input, $skipUsedAt=false)
    {
        if ($skipUsedAt){
            $resp = $this->fetchCustomerTokensInternalWithoutUsedAtCheck($input);
        } else {
            $resp = $this->fetchCustomerTokensInternal($input);
        }

        $tokens = new PublicCollection();

        foreach ($resp['body']['data'] as $tok)
        {
            $token = $this->forceFillTokensFromResponse($tok);

            $token = $this->loadRelatedEntity($token);

            $tokens->add($token);
        }

        return $tokens;
    }

    public function fetchTokensByIds($ids)
    {
        $resp = $this->fetchTokenByIdsInternal($ids);

        $tokens = new PublicCollection();

        foreach ($resp['body']['data'] as $tok)
        {
            $token = $this->forceFillTokensFromResponse($tok);

            $token = $this->loadRelatedEntity($token);

            $tokens->add($token);
        }

        return $tokens;

    }

    public function updateToken($input)
    {
         return $this->updateTokenInternal($input);
    }

    public function fetchTokensInternal($input)
    {
        $resp = $this->sendRequest(
            self::TokensBaseURL. '/'. self::URLS['fetch_tokens_multiple'],
            Requests::POST,
            $input,
            true
        );

        if (in_array($resp['code'], [200, 201, "200", "201"]) == false)
        {
            return new Exception\RuntimeException(
                'Unexpected response code received from Tokens service.',
                [
                    'input'         => array_keys($input),
                ]);
        }

        return $resp;
    }

    public function fetchTokenByIdsInternal($input)
    {
        $resp = $this->sendRequest(
            self::TokensBaseURL. '/'. self::URLS['fetch_tokens_by_ids'],
            Requests::POST,
            $input,
            true
        );

        if (in_array($resp['code'], [200, 201, "200", "201"]) == false)
        {
            return new Exception\RuntimeException(
                'Unexpected response code received from Tokens service.',
                [
                    'ids'         => $input,
                ]);
        }

        return $resp;
    }

    public function getPublicCollection($resp): PublicCollection
    {
        $tokens = new PublicCollection();

        foreach ($resp as $tok)
        {
            $token = $this->forceFillTokensFromResponse($tok);

            $token = $this->loadRelatedEntity($token);

            $tokens->add($token);
        }

        return $tokens;
    }

    public function fetchCustomerTokensInternal($input)
    {
        $resp = $this->sendRequest(
             'customers/'. $input['customer_id'].'/tokens',
            Requests::GET,
            $input,
        );

        if (in_array($resp['code'], [200, 201, "200", "201"]) == false)
        {
            return new Exception\RuntimeException(
                'Unexpected response code received from Tokens service.',
                [
                    'customer_id'         => $input['customer_id'],
                ]);
        }

        return $resp;
    }

    public function fetchCustomerTokensInternalWithoutUsedAtCheck($input)
    {
        $resp = $this->sendRequest(
            'customers/'. $input['customer_id'].'/tokens?skip_used_at_check=true',
            Requests::GET,
            $input,
        );

        if (in_array($resp['code'], [200, 201, "200", "201"]) == false)
        {
            return new Exception\RuntimeException(
                'Unexpected response code received from Tokens service.',
                [
                    'customer_id'         => $input['customer_id'],
                ]);
        }

        return $resp;
    }

    public function updateTokenInternal($input)
    {
        $resp = $this->sendUpdateRequest(
            self::TokensBaseURL. '/'. self::URLS['update_token_by_id'],
            Requests::POST,
            $input,
            true
        );

        if (in_array($resp['code'], [200, 201, "200", "201"]) == false)
        {
            return new Exception\RuntimeException(
                'Unexpected response code received from Tokens service.',
                [
                    'input'         => array_keys($input),
                ]);
        }

        return $resp;
    }

    /**
     * @throws RuntimeException
     */
    public function deleteTokensInternal($tokenID)
    {
        $this->trace->info(TraceCode::TOKENS_DELETE_EXTERNAL, [
            'token_id'      => $tokenID,
        ]);

        return $this->sendRequest(
            self::TokensBaseURL . '/' . $tokenID,
            Requests::DELETE,
            (array)null,
            true
        );
    }

    private function forceFillTokensFromResponse($response)
    {
        if (empty($response) === false)
        {
            if ((isset($response['notes']) === true) and (is_array($response['notes']) === false))
            {
                $response['notes'] = json_decode($response['notes']); //[], ["as","as"], {}
            }

            if ((isset($response['namespace']) === true) and (isset($response['entity_id']) === true))
            {
                if ($response['namespace'] == 'card')
                {
                    $response['card_id'] = $response['entity_id'];
                }
                elseif ($response['namespace'] == 'upi')
                {
                    $response['vpa_id'] = $response['entity_id'];
                }
                elseif ($response['namespace'] == 'wallet')
                {
                    $response['gateway_token'] = $response['wallet']['access_token'];
                    $response['gateway_token2'] = $response['wallet']['refresh_token'];
                    $response['expired_at'] = $response['wallet']['expires_at'];
                    $response['wallet'] = $response['wallet']['provider'];
                }

                unset($response['namespace']);
                unset($response['entity_id']);
            }

            $token = (new TokenEntity());

            $token->setExternal(true);

            $token->forceFill($response);

            $token->generate($response);

            return $token;
        }
        return null;
    }

    private function loadRelatedEntity($token)
    {
        if(isset($token['vpa']) && is_array($token['vpa']))
        {
            $vpa = $token['vpa'];

            $vpa[UniqueIdEntity::ID] = $token['vpa_id'];

            unset($token['vpa']);

            $vpaEntity = (new VpaEntity());

            $vpaEntity->forceFill($vpa);

            $token->vpa()->associate($vpaEntity);

            return $token;
        }

        //the token card is load early during the getCardAttribute which is happen when $token->forcefill() happen.
        if(($token->relationLoaded('card') === false) && (isset($token['card'])) && is_array($token['card']))
        {
            $card = $token['card']->toArray();

            unset($token['card']);

            $cardEntity = (new CardEntity());

            $card[UniqueIdEntity::ID] = $token['card_id'];

            $repo = \App::getFacadeRoot()['repo'];

            $merchant = $repo->merchant->findorFail($token['merchant_id']);

            $cardEntity->forceFill($card);

            //associate the merchant into card
            $cardEntity->merchant()->associate($merchant);

            $token->card()->associate($cardEntity);

            return $token;
        }

        //the token card is load early during the getCardAttribute which is happen when $token->forcefill() happen
        if (isset($token['card']))
        {
            return $token;
        }

        if (isset($token['wallet']))
        {
            return $token;
        }

        return null;
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws Requests_Exception
     */
    protected function sendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        bool $throwExceptionOnFailure = false,
        int $timeout = self::DEFAULT_REQUEST_TIMEOUT,
    ): array
    {
        $request = $this->generateRequest($endpoint, $method, $data, $timeout);

        $response = $this->sendTokensRequest($request);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::TOKENS_RESPONSE, [
            'function_name' => __FUNCTION__,
            'response_size'      => is_array($decodedResponse['data']) ? sizeof($decodedResponse['data']) : 0,
        ]);

        return $this->parseResponse($response, $throwExceptionOnFailure);
    }

    protected function sendUpdateRequest(
        string $endpoint,
        string $method,
        array $data = [],
        bool $throwExceptionOnFailure = false,
        int $timeout = self::DEFAULT_REQUEST_TIMEOUT,
    ): array
    {
        $request = $this->generateRequest($endpoint, $method, $data, $timeout);

        $response = $this->sendTokensRequest($request);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::TOKENS_RESPONSE, [
            'function_name' => __FUNCTION__,
            'response'      => !isset($decodedResponse['error']) ? 1 : 0,
        ]);

        return $this->parseResponse($response, $throwExceptionOnFailure);
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data, int $timeout): array
    {
        $url = $this->baseUrl . $endpoint;

        $this->trace->info(TraceCode::TOKENS_MIGRATION, [
            'function_name' => __FUNCTION__,
            'url'           => $url,
        ]);

        // json encode if data is must, else ignore.
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout' => $timeout,
            'auth'    => [
                $this->key,
                $this->secret
            ],
        ];

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $this->headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

    protected function sendTokensRequest(array $request)
    {
        $this->traceRequest($request);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);
        }
            // TODO: Check why are we catching this and rethrowing
        catch(Requests_Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TOKENS_FAILURE_EXCEPTION,
                [
                    'function_name' => __FUNCTION__,
                    'data'          => $e->getMessage()
                ]);

            throw $e;
        }

        return $response;
    }

    /**
     * @param \WpOrg\Requests\Response $response
     * @param bool               $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseResponse($response, bool $throwExceptionOnFailure = false): array
    {
        $code = $response->status_code;

        if (($throwExceptionOnFailure === true) and
            (in_array($code, [200, 201, 204, 302], true) === false))
        {

            throw new Exception\RuntimeException(
                'Unexpected response code received from Tokens service.',
                [
                    'status_code'   => $code,
                    'response_body' => json_decode($response->body),
                ]);
        }

        return [
            'body' => json_decode($response->body, true),
            'code' => $code,
        ];
    }


    /**
     * @param array $request
     */
    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);

        unset($request['headers'][Passport::PASSPORT_JWT_V1 ]);

        $this->trace->info(TraceCode::TOKENS_REQUEST, [
            'function_name' => __FUNCTION__,
            'request'      => array_keys($request),
        ]);
    }
}
