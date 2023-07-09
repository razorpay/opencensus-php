<?php

namespace RZP\Modules\Subscriptions;

use Config;
use RZP\Http\Request\Requests;
use \WpOrg\Requests\Session as Requests_Session;
use Illuminate\Support\Str;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use RZP\Models\Plan\Subscription;
use RZP\Jobs\SubscriptionPaymentHandler;
use RZP\Models\Payment\Processor\Constants;

class External extends Base
{
    protected $config;

    protected $request;

    protected $getsEntityResponse;

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

        $this->getsEntityResponse = true;
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
            'timeout' => $this->config['timeout'],
            'auth'    => [$username, $password],
        ];

        $request = new Requests_Session($baseUrl, $defaultHeaders, [], $defaultOptions);

        return $request;
    }

    public function fetchAdminEntity(string $entityName, string $entityId, array $input)
    {
        $this->getsEntityResponse = false;

        $headers = [
            self::MODE_HEADER_KEY     => $this->mode,
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $mode = $this->mode === 'test' ? 't' : 'l';

        $url = $mode . '/admin/' . $entityName . '/'.$entityId;

        return $this->sendRequest($url, Requests::GET, $input, $headers);
    }

    /**
     * Call Subscription Payment Processed
     **/
    public function paymentProcess(array $paymentPayload, string $mode)
    {
        $shouldCallEndpoint = $this->shouldCallEndpoint();

        $this->trace->info(TraceCode::SUBSCRIPTION_PAYMENT_NOTIFY,
            [
                'payment'    => $paymentPayload,
                'queue_mode' => ($shouldCallEndpoint === false)
            ]);

        if ($shouldCallEndpoint === true)
        {
            $this->paymentProcessSync($paymentPayload, $mode);
        }
        else
        {
            SubscriptionPaymentHandler::dispatch($paymentPayload, $mode);
        }
    }

    /**
     * should call endpoint instead of queue
     */
    private function shouldCallEndpoint()
    {
        return ((Config::get('queue.default') === 'sync') or ($this->config['queue_sync'] === true));
    }

    /**
     * Call Subscription Payment Processed, if queue is sync
     **/
    private function paymentProcessSync($paymentPayload, $mode)
    {
        $headers = [
            self::MODE_HEADER_KEY     => $mode ?? 'test',
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $url = 'subscriptions/sync_payment_process';

        return $this->sendRequest($url, Requests::POST, $paymentPayload, $headers);
    }

    public function fetchMultipleAdminEntity(string $entityName, array $input)
    {
        $this->getsEntityResponse = false;

        $headers = [
            self::MODE_HEADER_KEY     => $this->mode,
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $mode = $this->mode === 'test' ? 't' : 'l';

        $url = $mode . '/admin/' . $entityName ;

        return $this->sendRequest($url, Requests::GET, $input, $headers);
    }

    public function fetchCheckoutInfo(array $input, Merchant\Entity $merchant,bool $isupienabled = false)
    {
        $this->getsEntityResponse = true;

        $isCardChange       = $input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false;

        $subscriptionId     = $input[Payment\Entity::SUBSCRIPTION_ID];

        $requestBody = [
            Subscription\Entity::SUBSCRIPTION_CARD_CHANGE => $isCardChange,
            'isupienabled'                                => $isupienabled
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

    public function fetchSubscriptionForHosted(string $subscriptionId, Merchant\Entity $merchant)
    {
        $this->getsEntityResponse = true;

        $headers = [
            self::MERCHANT_HEADER_KEY => $merchant->getId(),
            self::MODE_HEADER_KEY     => $this->mode
        ];

        $mode = $this->mode === 'test' ? 't' : 'l';

        $url = $mode . '/subscriptions/' . $subscriptionId . '/hosted';

        return $this->sendRequest($url, Requests::GET, [], $headers);
    }

    public function fetchSubscriptionForInvoice(string $subscriptionId, Merchant\Entity $merchant)
    {
        $this->getsEntityResponse = false;

        $headers = [
            self::MERCHANT_HEADER_KEY => $merchant->getId(),
            self::MODE_HEADER_KEY     => $this->mode
        ];

        $mode = $this->mode === 'test' ? 't' : 'l';

        $url = $mode . '/subscriptions/' . $subscriptionId . '/hosted';

        return $this->sendRequest($url, Requests::GET, [], $headers);
    }

    public function fetchMerchantIdAndMode(string $subscriptionId)
    {
        $this->getsEntityResponse = true;

        $headers = [
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $this->getsEntityResponse = false;

        $url = 'subscriptions/'.$subscriptionId.'/merchant_mode';

        return $this->sendRequest($url, Requests::GET, [], $headers);
    }

    public function fetchSubscription(Merchant\Entity $merchant, string $subscriptionId)
    {
        $this->getsEntityResponse = true;

        $headers = [
            self::MERCHANT_HEADER_KEY => $merchant->getId(),
            self::MODE_HEADER_KEY     => $this->mode,
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $url = 'subscriptions/' . $subscriptionId ;

        return $this->sendRequest($url, Requests::GET, [], $headers);
    }

    public function fetchSubscriptionInfo(array $input, Merchant\Entity $merchant, $callback = false, $appTokenPresent = false)
    {
        $this->getsEntityResponse = true;

        $amount             = $input[Payment\Entity::AMOUNT] ?? null;
        $isCardChange       = $input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false;
        $isCardPresent      = (empty($input[Payment\Entity::CARD]) === false);
        $method             = ((isset($input['method']) === true) ? $input['method'] : (($isCardPresent === true) ? Constants::CARD :Constants::UPI ));

        $requestBody = [
            Payment\Entity::AMOUNT                        => $amount,
            Subscription\Entity::SUBSCRIPTION_CARD_CHANGE => $isCardChange,
            'app_token_present'                           => $appTokenPresent,
            'card_present'                                => $isCardPresent,
            'callback'                                    => $callback,
            'method'                                      => $method
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

    public function fetchSubscriptionInfoCardMandate(array $input, Merchant\Entity $merchant)
    {
        $requestBody = $input;

        $requestBody['data_fetch'] = 'card_mandate';

        $headers = [
            self::MERCHANT_HEADER_KEY => $merchant->getId(),
            self::MODE_HEADER_KEY     => $this->mode,
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $subscriptionId = $input['subscription_id'];

        $url = 'subscriptions/' . $subscriptionId . '/info';

        return $this->sendRequest($url, Requests::GET, $requestBody, $headers);
    }

    public function fetchSubscriptionInfoUpiAutoPay(array $input, Merchant\Entity $merchant)
    {
        $requestBody = $input;

        $requestBody['data_fetch'] = 'upi_autopay';

        $headers = [
            self::MERCHANT_HEADER_KEY => $merchant->getId(),
            self::MODE_HEADER_KEY     => $this->mode,
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $subscriptionId = $input['subscription_id'];

        $url = 'subscriptions/' . $subscriptionId . '/info';

        return $this->sendRequest($url, Requests::GET, $requestBody, $headers);
    }

    public function createSubscription(array $input, Merchant\Entity $merchant, $headers = [])
    {
        $this->traceRequest($input);

        $headers += [
            self::MERCHANT_HEADER_KEY => $merchant->getId(),
            self::MODE_HEADER_KEY     => $this->mode,
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $url = 'subscriptions';

        return $this->sendRequest($url, Requests::POST, $input, $headers);
    }

    public function fetchPlan(string $planId, Merchant\Entity $merchant)
    {
        $this->getsEntityResponse = false;

        $this->traceRequest([$planId]);

        $headers = [
            self::MERCHANT_HEADER_KEY => $merchant->getId(),
            self::MODE_HEADER_KEY     => $this->mode,
            'X-Razorpay-Auth'         => $this->app['basicauth']->getAuthType(),
        ];

        $url = 'plans/' . $planId;

        return $this->sendRequest($url, Requests::GET, [], $headers);
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
        catch(\WpOrg\Requests\Exception $e)
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
            if ($this->getsEntityResponse === true)
            {
                return $this->createSubscriptionEntity($responseBody);
            }
            else
            {
                return $responseBody;
            }
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

    protected function hasRequestTimedOut(\WpOrg\Requests\Exception $e): bool
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
