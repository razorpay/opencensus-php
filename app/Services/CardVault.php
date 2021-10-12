<?php

namespace RZP\Services;


use Requests_Hooks;
use Aws\Kms\KmsClient;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Card\Validator;
use RZP\Http\Request\Requests;
use RZP\Models\Card;

class CardVault
{
    const TOKEN             = 'token';
    const ERROR             = 'error';
    const VALUE             = 'value';
    const SECRET            = 'secret';
    const SUCCESS           = 'success';
    const NAMESPACE         = 'namespace';
    const SCHEME            = 'scheme';
    const TOKENEX_TOKEN     = 'tokenex_token';
    const TOKENEX_TOKENS    = 'tokenex_tokens';
    const X_RAZORPAY_TASKID = 'X-Razorpay-TaskId';
    const TOKENEX_VAULT_MAPPING = 'tokenex_vault_mapping';

    const REQUEST_TIMEOUT = 20;

    const MAX_RETRY_COUNT = 1;

    // card-vault namespaces
    const CARD      =   'card';
    const MPAN      =   'mpan';

    protected $baseUrl;

    protected $config;

    protected $trace;

    protected $app;

    protected $request;

    protected $cardNumberToToken = [];

    protected $namespace;

    protected $kmsClient;

    public function __construct($app, $namespace = 'card')
    {
        $this->app = $app;

        $this->mode = $app['rzp.mode'] ?? Mode::LIVE;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.card_vault');

        $this->baseUrl = $this->config['url'];

        $this->request = $app['request'];

        $this->namespace = $namespace;

        $this->kmsClient = new KmsClient([
             'version' => $this->config['version'],
             'region'  => $this->config['region']
        ]);

        if ($namespace === self::CARD)
        {
            $this->key = $this->config['key'];

            $this->secret = $this->config['secret'];
        }
        else
        {
            $keyName = $namespace . '_key';

            $secretName = $namespace . '_secret';

            $this->key = $this->config[$keyName];

            $this->secret = $this->config[$secretName];
        }
    }

    public function ping()
    {
        try
        {
            $payload = [
                self::SECRET => '4111111111111111',
            ];

            $response = $this->sendRequest('tokenize', 'post', $payload);

            $vault = Card\Vault::RZP_ENCRYPTION;

            if (isset($response['scheme']) === true)
            {
               $vault = Card\Vault::getVaultName($response['scheme']);
            }

            if ((empty($response[self::TOKEN]) === true) or
                ($vault === Card\Vault::RZP_ENCRYPTION))
            {
                throw new Exception\RuntimeException(
                    'card vault ping request failed', ['data' => $response]);
            }

            return true;
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::VAULT_PING_REQUEST_FAILED, []);
        }

        return false;

    }

    public function tokenize($input)
    {
        $key  = '';

        if ($this->namespace !== self::CARD)
        {
            $payload = [
                self::SECRET => $input['secret']
            ];

            $key = $this->namespace. '_' . $input['secret'];
        }
        else
        {
            if (array_key_exists('card', $input) === true)
            {
                $payload = [
                    self::SECRET => $input['card'],
                ];

                $key = $input['card'];
            }
        }

        if (array_key_exists(self::SCHEME, $input) === true)
        {
            $payload[self::SCHEME] = $input[self::SCHEME];

            $key = $key . '_' . $input[self::SCHEME];
        }

        if (empty($this->cardNumberToToken[$key]) === false)
        {
            return $this->cardNumberToToken[$key];
        }

        $response = $this->sendRequest('tokenize', 'post', $payload);

        if (empty($response[self::TOKEN]) === true)
        {
            throw new Exception\RuntimeException(
                'card vault request failed', ['data' => $response]);
        }

        $this->cardNumberToToken[$key] = $response[self::TOKEN];

        return $response[self::TOKEN];
    }

    public function getTokenAndFingerprint($input)
    {
        $payload = [
            self::SECRET => $input['card'],
        ];

        $key = $input['card'];

        $response = $this->sendRequest('tokenize', 'post', $payload);

        if (empty($response[self::TOKEN]) === true)
        {
            throw new Exception\RuntimeException(
                'card vault request failed', ['data' => $response]);
        }

        return $response;
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

    public function getVaultTokenFromTempToken($tempVaultToken)
    {
        $input = [
            self::TOKEN  => $tempVaultToken,
        ];

        $response = $this->sendRequest('token/migrate', 'post', $input);

        return $response;
    }

    public function deleteToken($tempVaultToken)
    {
        $input = [
            self::TOKEN  => $tempVaultToken,
        ];

        $response = $this->sendRequest('token/delete', 'post', $input);

        return $response;
    }

    public function sendRequest($url, $method, $data = null)
    {
        // new namespaces(other than 'card') requires namespace to be explicitly mentioned in the requestdata
        if ($this->namespace !== self::CARD)
        {
            $data[self::NAMESPACE]  =  $this->namespace;
        }

        // temporary code to debug
        if (($url === 'tokenize') or
            ($url === 'detokenize'))
        {
            $trackId = Base\UniqueIdEntity::generateUniqueId();

            $url = $url . '/track/' . $trackId;
        }

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
            'hooks' => $this->getRequestHooks(),
        ];

        $request = [
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        ];


        $this->trace->info(TraceCode::CARD_VAULT_REQUEST, [
            'url' => $request['url'],
            'namespace' => $this->namespace,
        ]);

        $response = $this->sendCardVaultRequest($request);

        $this->checkErrors($response);

        return json_decode($response->body, true);
    }

    protected function getRequestHooks()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        return $hooks;
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
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
        $responseBody = json_decode($response->body, true);

        $success = $responseBody[self::SUCCESS];

        // in detokenize response will contain card number
        unset($responseBody[self::VALUE]);

        $this->trace->info(
            TraceCode::CARD_VAULT_RESPONSE,
            [
                'response'  => $responseBody,
                'namespace' => $this->namespace,
                'status_code' => $response->status_code,
            ]);

        if ($response->status_code >= 500)
        {
            throw new Exception\RuntimeException(
                'Vault request failed', ['data' => $responseBody]);
        }

        if ($success === false)
        {
            $error = $responseBody[self::ERROR];

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

    public function createVaultToken(array $input): array
    {
        (new Validator)->validateInput('create_vault_token', $input);

        $this->trace->info(TraceCode::VAULT_TOKEN_CREATE_INIT);

        $input[self::SECRET] = str_replace(array("\r", "\n"), '', $input[self::SECRET]);

        $response = $this->sendRequest('tokenize', 'post', $input);

        if (empty($response[self::TOKEN]) === true)
        {
            throw new Exception\RuntimeException(
                'Tokenize request failed', ['data' => $response]);
        }

        $this->trace->info(TraceCode::VAULT_TOKEN_CREATE_COMPLETE);

        return $response;
    }

    public function createTokenizedCard(array $input): array
    {
        $this->trace->info(TraceCode::VAULT_CREATE_TOKEN);

        $response = $this->sendRequest('tokens', 'post', $input);

        if ($response[self::SUCCESS] === false)
        {
            throw new Exception\RuntimeException(
                'Network Token create request failed', ['data' => $response]);
        }

        return $response;
    }


    public function renewVaultToken(): array
    {
        $this->trace->info(TraceCode::VAULT_TOKEN_RENEWAL_REQUEST);

        $response = $this->sendRequest('token/renewal', 'post', null);

        $this->trace->info(
            TraceCode::VAULT_TOKEN_RENEWAL_RESPONSE,
            [
                'response' => $response
            ]);

        if ($response[self::SUCCESS] === false)
        {
            throw new Exception\RuntimeException(
                'Service Token renewal request failed', ['data' => $response]);
        }

        return $response;
    }

    public function encrypt(array $input)
    {
        if ($this->config['kms_mock'] === true)
        {
            return $this->app['encrypter']->encrypt($input['card']);
        }

        $result = $this->kmsClient->encrypt([
            'KeyId' => $this->config['key_id'],
            'Plaintext' => $input['card'],
        ]);

        return base64_encode($result->get('CiphertextBlob'));
    }

    public function decrypt($token)
    {
        if ($this->config['kms_mock'] === true)
        {
            return $this->app['encrypter']->decrypt($token);
        }

       $result = $this->kmsClient->decrypt([
            'CiphertextBlob' => base64_decode($token),
        ]);

       return $result->get('Plaintext');
    }
}
