<?php

namespace RZP\Services;

use RZP\Exception;
use Requests;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class TokenEx
{
    const API_KEY           = 'APIKey';
    const TOKENEX_ID        = 'TokenExID';
    const TOKEN             = 'Token';
    const DATA              = 'Data';
    const TOKEN_SCHEME      = 'TokenScheme';
    const SUCCESS           = 'Success';
    const REFERENCE_NUMBER  = 'ReferenceNumber';
    const ERROR             = 'Error';
    const VALID             = 'Valid';
    const VALUE             = 'Value';

    protected $tokenScheme;

    protected $apiKey;

    protected $tokenExId;

    protected $baseUrl;

    protected $config;

    protected $trace;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.card_tokenex');

        $this->apiKey = $this->config['key'];

        $this->tokenExId = $this->config['id'];

        $this->tokenScheme = $this->config['scheme'];

        $this->baseUrl = $this->config['url'];

        $this->proxy = $app['proxy_address'];
    }

    public function tokenize($data)
    {
        $input = array(
            self::API_KEY       => $this->apiKey,
            self::TOKENEX_ID    => $this->tokenExId,
            self::DATA          => $data,
            self::TOKEN_SCHEME  => (int) $this->tokenScheme
        );

        $response = $this->sendRequest('REST/Tokenize', 'post', $input);

        return $response[self::TOKEN];
    }

    public function validateToken($token)
    {
        $input = array(
            self::API_KEY       => $this->apiKey,
            self::TOKENEX_ID    => $this->tokenExId,
            self::TOKEN         => $token
        );

        $response = $this->sendRequest('REST/ValidateToken', 'post', $input);

        return $response;
    }

    public function detokenize($token)
    {
        $input = array(
            self::API_KEY       => $this->apiKey,
            self::TOKENEX_ID    => $this->tokenExId,
            self::TOKEN         => $token,
        );

        $response = $this->sendRequest('REST/Detokenize', 'post', $input);

        return $response[self::VALUE];
    }

    public function deleteToken($token)
    {
        $input = array(
            self::API_KEY       => $this->apiKey,
            self::TOKENEX_ID    => $this->tokenExId,
            self::TOKEN         => $token,
        );

        $response = $this->sendRequest('REST/DeleteToken', 'post', $input);

        return $response;
    }

    public function sendRequest($url, $method, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
            $data = '';

        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';

        $options = array(
            'proxy' => $this->proxy,
        );

        $request = array(
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendTokenExRequest($request);

        $this->checkErrors(json_decode($response->body, true));

        return json_decode($response->body, true);
    }

    protected function sendTokenExRequest($request)
    {
        $method = $request['method'];

        try
        {
            $response = Requests::$method(
                $request['url'],
                $request['headers'],
                json_encode($request['content']),
                $request['options']);
        }
        catch(\Requests_Exception $e)
        {
            throw $e;
        }

        return $response;
    }

    protected function checkErrors($response)
    {
        $referenceNumber = $response[self::REFERENCE_NUMBER];
        $success = $response[self::SUCCESS];

        // if successful, value contains the card number, removes if present
        unset($response[self::VALUE]);

        $this->trace->info(
            TraceCode::TOKENEX_REQUEST,
            [
                'response' => $response
            ]);

        if ($success === false)
        {
            throw new Exception\RuntimeException('tokenex request: '. $referenceNumber . ' failed');
        }
    }
}
