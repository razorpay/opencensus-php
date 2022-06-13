<?php

namespace RZP\Services;

use Requests_Hooks;
use Aws\Kms\KmsClient;
use RZP\Error\ErrorClass;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Error;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Card\Validator;
use RZP\Http\Request\Requests;
use RZP\Models\Payment;
use RZP\Gateway\Base\Metric;
use RZP\Models\Customer\Token;

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

    const TOKENEX_VAULT_MAPPING   = 'tokenex_vault_mapping';
    const SERVICE_PROVIDER_TOKENS = 'service_provider_tokens';

    const REQUEST_TIMEOUT = 20;

    const MAX_RETRY_COUNT = 1;

    // card-vault namespaces
    const CARD      =   'card';
    const MPAN      =   'mpan';
    const RAZORPAYX =   'razorpayx';

    const TOKENIZATION_ROUTES = array(Card\Constants::FETCH_PAR_VAL, Card\Constants::TOKENS_CRYPTOGRAM, Card\Constants::TOKENS, Card\Constants::TOKENS_MIGRATE, Card\Constants::TOKENS_FETCH, Card\Constants::TOKENS_DELETE, Card\Constants::TOKENS_UPDATE);

    protected $baseUrl;

    protected $config;

    protected $trace;

    protected $app;

    protected $request;

    protected $cardNumberToToken = [];

    protected $namespace;

    protected $kmsClient;
    /**
     * @var string
     */
    private $key;
    /**
     * @var string
     */
    private $secret;

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

        // default for cards
        $keyName = 'key';
        $secretName = 'secret';
        if ($namespace != self::CARD) {
            $keyName = $namespace . '_key';
            $secretName = $namespace . '_secret';
        }

        $this->key = $this->config[$keyName];
        $this->secret = $this->config[$secretName];
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
                    'card vault ping request failed', [Error\Error::DATA => $response]);
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
                'card vault request failed', [Error\Error::DATA => $response]);
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
                'card vault request failed', [Error\Error::DATA => $response]);
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

        $tokenizationUrl = $url;

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
            'namespace' => $this->namespace
        ]);

        $isTokenisationRoute = in_array($tokenizationUrl, self::TOKENIZATION_ROUTES);

        if($isTokenisationRoute)
        {

            list($action, $event) = $this->fetchActionAndEvent($isTokenisationRoute, $tokenizationUrl);

            (new Token\Event())->pushEvents($request['content'], $event, "_REQUEST_SENT");
        }

        $response = $this->sendCardVaultRequest($request);

        $network = '';

        if(!empty($request["content"]["iin"]["network"]))
        {
            $network = $request["content"]["iin"]["network"];
        }


        if($isTokenisationRoute)
        {
            $this->handleVaultResponse($request, $response, $network, $action, $event);
        }
        else {
            $this->checkErrors($response);
        }
        return json_decode($response->body, true);
    }

    public function sendBulkRequest($url, $method, $data = null)
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
            'content'  => $data
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
                            'message'           => $e->getMessage(),
                            'type'              => $e->getType(),
                            Error\Error::DATA   => $e->getData()
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

        $this->trace->info(
            TraceCode::CARD_VAULT_RESPONSE,
            [
                'response'  => $this->getRedactedData($responseBody),
                'namespace' => $this->namespace,
                'status_code' => $response->status_code,
            ]);

        if ($response->status_code >= 500)
        {
            throw new Exception\RuntimeException(
                'Vault request failed', [Error\Error::DATA => $responseBody]);
        }

        if ($success === false || $success === 0)
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

    protected function getRedactedData($response)
    {
        // in detokenize response will contain card number
        unset($response[self::VALUE]);

        // in network tokenization response will contain tokenized card number, cryptogram value etc
        unset($response[self::SERVICE_PROVIDER_TOKENS]);

        return $response;
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
                'Tokenize request failed', [Error\Error::DATA => $response]);
        }

        $this->trace->info(TraceCode::VAULT_TOKEN_CREATE_COMPLETE);

        return $response;
    }

    public function createTokenizedCard(array $input): array
    {
        $this->trace->info(TraceCode::VAULT_CREATE_TOKEN);

        $response = $this->sendRequest(Card\Constants::TOKENS, 'post', $input);

        return $response;
    }

    public function migrateToTokenizedCard(array $input): array
    {
        $this->trace->info(TraceCode::VAULT_MIGRATE_TOKEN);

        $response = $this->sendRequest(Card\Constants::TOKENS_MIGRATE, 'post', $input);

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

        $response = $this->sendRequest(Card\Constants::TOKENS_RENEWAL, 'post', null);

        $this->trace->info(
            TraceCode::VAULT_TOKEN_RENEWAL_RESPONSE,
            [
                'response' => $response
            ]);

        if ($response[self::SUCCESS] === false)
        {
            throw new Exception\RuntimeException(
                'Service Token renewal request failed', [Error\Error::DATA => $response]);
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

    public function fetchCryptogram($input): array
    {
        $this->trace->info(TraceCode::VAULT_FETCH_CRYPTOGRAM);

        $response = $this->sendRequest(Card\Constants::TOKENS_CRYPTOGRAM, 'post', $input);

        return $response;
    }

    public function fetchToken($input): array
    {
        $this->trace->info(TraceCode::VAULT_FETCH_TOKEN);

        $response = $this->sendRequest(Card\Constants::TOKENS_FETCH, 'post', $input);

        return $response;
    }

    public function fetchParValue($input) : array
    {
        $response = $this->sendRequest(Card\Constants::FETCH_PAR_VAL, 'post', $input);

        return $response;
    }

    public function deleteNetworkToken($input): array
    {
        $this->trace->info(TraceCode::VAULT_DELETE_TOKEN);

        $response = $this->sendRequest(Card\Constants::TOKENS_DELETE, 'post', $input);

        return $response;
    }

    public function updateToken($input): array
    {
        $this->trace->info(TraceCode::VAULT_UPDATE_TOKEN);

        $response = $this->sendRequest(Card\Constants::TOKENS_UPDATE, 'post', $input);

        return $response;
    }


    public function migrateVaultTokenNamespace($input)
    {
        $this->trace->info(TraceCode::VAULT_MIGRATE_TOKEN_BULK_REQUEST, ['input' => $input]);

        $tokenInput['tokens'] = $input;

        $response = $this->sendBulkRequest(Card\Constants::TOKENS_MIGRATE_BULK, 'post', $tokenInput);

        $this->trace->info(TraceCode::VAULT_MIGRATE_TOKEN_BULK_RESPONSE, ['response' => $response]);

        return $response;
    }

    protected function handleVaultResponse($request, $response, $network = null, $action = null, $event = null)
    {
        if(empty($response) === true)
        {
            $this->trace->info(TraceCode::VAULT_SERVICE_INTERNAL_ERROR);

            throw new Exception\GatewayErrorException(
                'SERVER_ERROR_VAULT_TOKENIZE_FAILED'
            );
        }

        $statusCode = $response->status_code;

        try {

            $this->checkForErrors($response);

            $this->pushDimensions($request, Metric::SUCCESS, $statusCode, $action);

            (new Token\Event())->pushEvents($request['content'], $event, "_RESPONSE_RECEIVED", $response);
        }

        catch(Exception\BaseException $e) {
            $error = $e->getError();

            $this->trace->info(TraceCode::ERROR_EXCEPTION, [$e->getError()]);

            $this->pushDimensions($request, Metric::FAILED, $statusCode, $action, $e);

            (new Token\Event())->pushEvents($request['content'], $event, "_RESPONSE_RECEIVED", $response, $e);

            $internalErrorCode = $error->getInternalErrorCode();

            $error->setDetailedError($internalErrorCode, Payment\Method::CARD, $network);

            $error->setPaymentMethod(Payment\Method::CARD);

            throw $e;
        }
    }

    protected function checkForErrors($response)
    {
        $responsebody = json_decode($response->body, true);

        $this->trace->info(
            TraceCode::CARD_VAULT_RESPONSE,
            [
                'response'    => $this->getRedactedData($responsebody),
                'namespace'   => $this->namespace,
                'status_code' => $response->status_code,
            ]);

        if ((empty($responsebody['success']) === false) and
            ($responsebody['success'] === true))
        {
            return;
        }

        $error_code = '';

        if(!empty($responsebody[self::ERROR][Error\Error::INTERNAL_ERROR_CODE]))
        {
            $error_code = $responsebody[self::ERROR][Error\Error::INTERNAL_ERROR_CODE];
        }

        $class = $this->getErrorClassFromErrorCode($error_code);

        switch ($class)
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors($responsebody[self::ERROR], $responsebody);
                break;

            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors($responsebody[self::ERROR], $responsebody);
                break;

            case ErrorClass::SERVER:
                $this->handleInternalServerErrors($responsebody[self::ERROR]);
                break;

            default:
                throw new Exception\InvalidArgumentException('Not a valid error code class',
                    ['errorClass' => $class]);
        }
    }

   protected function getErrorClassFromErrorCode($code)
   {
       $pos = strpos($code, '_');

       $class = substr($code, 0, $pos);

       if ($class == 'BAD') {
           $class = ErrorClass::BAD_REQUEST;
       }

       return $class;
   }

   protected  function handleGatewayErrors(array $error, array $response)
   {
       $errorCode = $error[Error\Error::INTERNAL_ERROR_CODE];

       $gatewayErrorCode = $error[Error\Error::GATEWAY_ERROR_CODE] ?? null;

       $gatewayErrorDescription = $error['gateway_error_description'] ?? null;

       $this->trace->info(TraceCode::ERROR_CODE_FOR_VAULT_RESPONSE, [$error]);

       switch ($errorCode)
       {
           case Error\ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
               throw new Exception\GatewayRequestException($errorCode);

           case Error\ErrorCode::GATEWAY_ERROR_TIMED_OUT:
               throw new Exception\GatewayTimeoutException($errorCode);

           default:
               throw new Exception\GatewayErrorException($errorCode,
                   $gatewayErrorCode,
                   $gatewayErrorDescription
               );
       }
   }

    protected function handleBadRequestErrors(array $error, array $response)
    {
        $errorCode = $error[Error\Error::INTERNAL_ERROR_CODE];

        $data = $response[Error\Error::DATA] ?? null;

        $description = $error[Error\Error::DESCRIPTION] ?? null;

        $this->trace->info(TraceCode::ERROR_CODE_FOR_VAULT_RESPONSE, [$errorCode]);

        if (empty($error[Error\Error::GATEWAY_ERROR_CODE]) === false)
        {
            $this->handleGatewayErrors($error, $response);
        }
        else if ($errorCode !== '')
        {
            throw new Exception\BadRequestException($errorCode);
        }
        else
        {
            throw new Exception\LogicException(
                $description,
                $errorCode,
                $data);
        }
    }

    protected function handleInternalServerErrors(array $error)
    {
        $code = $error[Error\Error::INTERNAL_ERROR_CODE];

        $data = $error[Error\Error::DATA] ?? null;

        $description = $error[Error\Error::DESCRIPTION] ?? 'Vault request failed';

        $this->trace->info(TraceCode::ERROR_CODE_FOR_VAULT_RESPONSE, [$code]);

        throw new Exception\LogicException(
            $description,
            $code,
            $data);
    }

    protected function pushDimensions($request, $status, $statusCode = null, $action = null, $exe = null)
    {
        if (($this->mode === Mode::TEST) and
            ($this->app->runningUnitTests() === false))
        {
            return;
        }

        (new Token\Metric)->pushTokenHQDimensions($request['content'], $status, $statusCode, $action, $exe);
    }

    /**
     * @param string $url
     * @return false|string
     */
    protected function getTokenizationAction(string $url)
    {
        $action = '';

        if ($url === 'tokens')
        {
            $action = 'create';
        }

        if($url === 'cards/fingerprints')
        {
            $action = 'par_api';
        }

        else if (strlen($url) >6 && substr($url, 0, 6) == 'tokens')
        {
            $action = substr($url, 7, strlen($url));
        }

        return $action;
    }

    /**
     * @param string $action
     * @return false|string
     */
    protected function getTokenizationEvent(string $action)
    {
        return Token\Event::ACTION_EVENT_MAPPING[$action];
    }

    /**
     * @param bool $isTokenisationRoute
     * @param $tokenizationUrl
     * @return array
     */
    protected function fetchActionAndEvent(bool $isTokenisationRoute, $tokenizationUrl): array
    {
        $action = '';
        $event = '';

        if ($isTokenisationRoute)
        {
            $action = $this->getTokenizationAction($tokenizationUrl);

            $event = $this->getTokenizationEvent($action);
        }
        return array($action, $event);
    }
}
