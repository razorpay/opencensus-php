<?php

namespace RZP\Modules\Subscriptions;

use Config;
use Requests;
use Requests_Session;
use Illuminate\Support\Str;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use RZP\Models\Plan\Subscription;

class External extends Base
{
    protected $config;

    protected $request;

    const REQUEST_TIMEOUT = 10;      // In seconds

    const MERCHANT_HEADER_KEY = 'X-Razorpay-MerchantId';
    const MODE_HEADER_KEY     = 'X-Razorpay-Mode';

    public function __construct()
    {
        parent::__construct();

        $this->config = Config::get('applications.subscriptions');

        if ($this->request === null)
        {
            $this->request = $this->initRequestObject();
        }
    }

    protected function initRequestObject()
    {
        $baseUrl = $this->config['url'] . 'v1/';

        $username = $this->config['username'];

        $password = $this->config['secret'];

        $defaultHeaders = [
            'Accept'         => 'application/json',
            'X-Razorpay-App' => 'api',
        ];

        $defaultOptions = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$username, $password],
        ];

        $request = new Requests_Session($baseUrl, $defaultHeaders, [], $defaultOptions);

        return $request;
    }

    public function fetchCheckoutInfo(array $input, Merchant\Entity $merchant)
    {
        $isCardChange       = $input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false;

        $subscriptionId     = $input[Payment\Entity::SUBSCRIPTION_ID];

        $requestBody = [
            Subscription\Entity::SUBSCRIPTION_CARD_CHANGE => $isCardChange,
        ];

        $this->traceRequest($requestBody);

        $headers = [
            self::MERCHANT_HEADER_KEY => $merchant->getId(),
            self::MODE_HEADER_KEY     => $this->mode,
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $url = 'subscriptions/' . $subscriptionId.'/checkout_info';

        return $this->sendRequest($url, Requests::GET, $requestBody, $headers);
    }

    public function fetchSubscriptionInfo(array $input, Merchant\Entity $merchant, $callback = false, $appTokenPresent = false)
    {
        $amount             = $input[Payment\Entity::AMOUNT] ?? null;
        $isCardChange       = $input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false;
        $isCardPresent      = (isset($input[Payment\Entity::CARD]) === true);

        $requestBody = [
            Payment\Entity::AMOUNT                        => $amount,
            Subscription\Entity::SUBSCRIPTION_CARD_CHANGE => $isCardChange,
            'app_token_present'                           => $appTokenPresent,
            'card_present'                                => $isCardPresent,
            'callback'                                    => $callback,
        ];

        if (isset($input[Payment\Entity::TOKEN]) === true)
        {
            if (strpos($input[Payment\Entity::TOKEN], 'token_') === false)
            {
                $input[Payment\Entity::TOKEN] = Token\Entity::getSignedId($input[Payment\Entity::TOKEN]);
            }

            $requestBody[Payment\Entity::TOKEN] = $input[Payment\Entity::TOKEN];
        }

        $this->traceRequest($requestBody);

        $headers = [
            self::MERCHANT_HEADER_KEY => $merchant->getId(),
            self::MODE_HEADER_KEY     => $this->mode,
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $subscriptionId = $input[Payment\Entity::SUBSCRIPTION_ID];

        $url = 'subscriptions/' . $subscriptionId . '/info';

        return $this->sendRequest($url, Requests::GET, $requestBody, $headers);
    }

    protected function sendRequest(
        string $url,
        string $method,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        try
        {
            $response = $this->request->request(
                $url,
                $headers,
                $body,
                $method,
                $options);
        }
        catch(\Requests_Exception $e)
        {
            $this->trace->traceException($e);

            $errorCode = ($this->hasRequestTimedOut($e) === true) ?
                ErrorCode::SERVER_ERROR_SUBSCRIPTION_SERVICE_TIMEOUT :
                ErrorCode::SERVER_ERROR_SUBSCRIPTION_SERVICE_FAILURE;

            throw new Exception\ServerErrorException(
                $e->getMessage(),
                $errorCode
            );
        }

        return $this->parseResponse($response);
    }

    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::SUBSCRIPTION_SERVICE_REQUEST, $request);
    }

    protected function parseResponse($response)
    {
        $code = $response->status_code;

        // TODO: handle json decode errors here
        $responseBody = json_decode($response->body, true);

        if ($response->success === true)
        {
            return $this->createSubscriptionEntity($responseBody);
        }
        elseif ($code >= 400 and $code < 500)
        {
            $this->handleBadRequestErrors($responseBody['error']);
        }
        else
        {
            $this->handleInternalServerErrors($responseBody['error']);
        }
    }

    protected function createSubscriptionEntity(array $body)
    {
        $subscription = new Subscription\Entity;

        $subscription->forceFill($body);

        $subscription->setExternal(true);

        return $subscription;
    }

    protected function handleBadRequestErrors(array $error)
    {
        $code = $error['internal_error_code'];

        $field = $error['field'] ?? null;

        $data = $error['data'] ?? null;

        $description = $error['description'] ?? null;

        throw new Exception\BadRequestException($code, $field, $data, $description);
    }

    protected function handleInternalServerErrors(array $error)
    {
        $message = $error['description'] ?? 'subscriptions service request failed';

        throw new Exception\ServerErrorException(
            $message,
            ErrorCode::SERVER_ERROR_SUBSCRIPTION_SERVICE_FAILURE,
            $error);
    }

    protected function hasRequestTimedOut(\Requests_Exception $e): bool
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'operation timed out',
            'network is unreachable',
            'name or service not known',
            'failed to connect',
            'could not resolve host',
            'resolving timed out',
            'name lookup timed out',
            'connection timed out',
            'aborted due to timeout',
        ]);
    }
}
