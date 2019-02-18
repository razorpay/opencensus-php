<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;

class CardVault
{
    const TOKEN             = 'token';
    const ERROR             = 'error';
    const VALUE             = 'value';
    const SECRET            = 'secret';
    const SUCCESS           = 'success';
    const TOKENEX_TOKEN     = 'tokenex_token';
    const TOKENEX_TOKENS    = 'tokenex_tokens';
    const X_RAZORPAY_TASKID = 'X-Razorpay-TaskId';
    const TOKENEX_VAULT_MAPPING = 'tokenex_vault_mapping';

    const REQUEST_TIMEOUT = 20;
    const MAX_RETRY_COUNT = 1;

    protected $baseUrl;

    protected $config;

    protected $trace;

    protected $request;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.card_vault');

        $this->baseUrl = $this->config['url'];

        $this->key = $this->config['key'];

        $this->secret = $this->config['secret'];

        $this->request = $app['request'];
    }

    public function tokenize($input)
    {
        $input = [
            self::SECRET => $input['card'],
        ];

        $response = $this->sendRequest('tokenize', 'post', $input);

        if (empty($response[self::TOKEN]) === true)
        {
            throw new Exception\RuntimeException(
                'card vault request failed', ['data' => $response]);
        }

        return $response[self::TOKEN];
    }

    public function validateToken($token)
    {
        $input = [
            self::TOKEN => $token
        ];

        $response = $this->sendRequest('validate', 'post', $input);

        return $response;
    }

    public function detokenize($token)
    {
        $input = [
            self::TOKEN         => $token,
        ];

        $response = $this->sendRequest('detokenize', 'post', $input);

        return $response[self::VALUE];
    }

    public function deleteToken($token)
    {
        // need to implement this
        return [];
    }

    public function sendRequest($url, $method, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
            $data = '';

        $headers['Content-Type'] = 'application/json';

        $headers['Accept'] = 'application/json';

        $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth' => [
                $this->key,
                $this->secret
            ],
        ];

        $request = [
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        ];

        $response = $this->sendCardVaultRequest($request);

        $this->checkErrors(json_decode($response->body, true));

        return json_decode($response->body, true);
    }

    protected function sendCardVaultRequest($request)
    {
        $method = $request['method'];

        $retryCount = 0;

        while (true)
        {
            try
            {
                $response = Requests::$method(
                    $request['url'],
                    $request['headers'],
                    json_encode($request['content']),
                    $request['options']);

                break;
            }
            catch(\Requests_Exception $e)
            {
                // check curl error, increase retry count if timeout
                // throw the error if retry count reaches max allowed value
                if (($retryCount < self::MAX_RETRY_COUNT) and
                    (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
                {
                    $this->trace->info(
                        TraceCode::CARD_VAULT_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'type'    => $e->getType(),
                            'data'    => $e->getData()
                        ]);

                    $retryCount++;
                }
                else
                {
                    throw $e;
                }
            }
        }

        return $response;
    }

    protected function checkErrors($response)
    {
        $success = $response[self::SUCCESS];

        // in detokenize response will contain card number
        unset($response[self::VALUE]);

        $this->trace->info(
            TraceCode::CARD_VAULT_RESPONSE,
            [
                'response' => $response
            ]);

        if ($success === false)
        {
            $error = $response[self::ERROR];

            // case where validate token return success false because of invalid token
            // error will be empty
            if (empty($error) === false)
            {
                $data = [
                    'error' => $error,
                ];

                throw new Exception\RuntimeException('card vault request failed', $data);
            }
        }
    }
}
