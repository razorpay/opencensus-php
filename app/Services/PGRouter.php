<?php

namespace RZP\Services;

use App;
use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Card;
use RZP\Models\Offer;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Error\ErrorClass;
use RZP\Models\Order\Metric;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Offer\EntityOffer\Repository as EntityOfferRepository;

class PGRouter
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $mode;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $auth;

    protected $currentEndPoint;

    const PGRouterOrderSyncUrl = 'v1/sync/order';

    const PGRouterUpdateSyncedOrderUrl = 'v1/update/synced/order';

    const PGRouterBulkOrderSyncUrl = 'v1/bulk/sync/orders';

    // Payment related PG Router APIs below
    // Deliberately using this URL. When PG Router becomes face of orders & payments, integrated merchants will hit this URL
    // and PG Router will take this as first call to create paymentId and perform validations. So, will be easy to switch in future.
    const PGRouterValidateAndCreatePayment = 'v1/payments/create/ajax';

    const PGRouterValidateAndCreatePaymentCheckout = 'v1/payments/create/checkout';

    const PGRouterFetchPayment = 'v1/payments/';

    const PGRouterFetchCard    = 'v1/cards/';

    const PGRouterInitiatePayment = 'v1/payments/initiate';

    const PGRouterPaymentCapture = '/v1/payments/%s/capture';

    const PGRouterPaymentVerify = '/v1/payments/%s/verify';

    const PGRouterPaymentCancel = '/v1/payments/%s/cancel';

    const PGRouterPaymentAuthenticate = '/v1/payments/%s/authenticate';

    const PGRouterCreateOrder = 'v1/orders';

    const PGRouterFetchOrder = 'v1/orders/%s';

    const PGRouterUpdateInternalOrder = 'v1/internal/orders/%s';

    const PGRouterUpdateCurrencyRoute   = 'v1/update/currency/rate';

    const PGRouterPaymentCreateJson = 'v1/payments/create/json';

    const PGRouterPaymentCreateRedirect = 'v1/payments/create/redirect';

    const PGRouterOTPResendPrivate = "v1/payments/%s/otp/resend";

    const PGRouterFetchOrderPayments = "v1/orders/%s/payments";

    const PGRouterOTPSubmitPrivate = "v1/payments/%s/otp/submit";

    const PG_ROUTER_STATIC_CALLBACK = "/v1/payments/%s/static_callback";

    const PG_ROUTER_FAILURE_STATUS_CODE = "pg_router_failure_status_code";

    const PGRouterValidateAndCreatePaymentUpi = 'v1/payments/create/upi';

    const PG_ROUTER_REQUEST_FAILURE = "pg_router_request_failure";

    // Headers
    const ACCEPT            = 'Accept';
    const X_MODE            = 'X-Mode';
    const CONTENT_TYPE      = 'Content-Type';
    const X_REQUEST_ID      = 'X-Request-ID';
    const X_REQUEST_TASK_ID = 'X-Razorpay-TaskId';

    const DEFAULT_REQUEST_TIMEOUT   = 60;

    const RESPONSE_CODE     = 'code';

    const MODE = 'mode';

    // Requests will be logged by default or if value for path is true.
    const REQUEST_LOGGER_MAP = [
        Requests::POST.'_'.self::PGRouterValidateAndCreatePayment   => true,
        Requests::GET.'_'.self::PGRouterPaymentCancel               => false,
        Requests::GET.'_'.self::PGRouterPaymentVerify               => false,
        Requests::GET.'_'.self::PGRouterFetchCard                   => false,
        Requests::GET.'_'.self::PGRouterFetchOrderPayments          => false,
        Requests::GET.'_'.self::PGRouterFetchPayment                => false,
        Requests::GET.'_'.self::PGRouterFetchOrder                  => false,
        Requests::PATCH.'_'.self::PGRouterFetchOrder                => false,
        Requests::POST.'_'.self::PGRouterCreateOrder                => false,
        Requests::PATCH.'_'.self::PGRouterUpdateCurrencyRoute       => false,
        Requests::POST.'_'.self::PGRouterOTPSubmitPrivate           => false
    ];

    const RESPONSE_LOGGER_MAP = [
        Requests::GET.'_'.self::PGRouterFetchPayment                => false,
        Requests::GET.'_'.self::PGRouterFetchOrder                  => false,
        Requests::PATCH.'_'.self::PGRouterFetchOrder                => false,
        Requests::POST.'_'.self::PGRouterCreateOrder                => false,
        Requests::PATCH.'_'.self::PGRouterUpdateInternalOrder       => false,
        Requests::PATCH.'_'.self::PGRouterUpdateCurrencyRoute       => false,
        Requests::POST.'_'.self::PGRouterOTPSubmitPrivate           => false
    ];

    /**
     * PGRouter constructor.
     *
     * @param $app
     */
    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.pg_router');

        $this->baseUrl = $this->config['url'];

        $this->mode = (isset($app['rzp.mode']) === true) ? $app['rzp.mode'] : Mode::LIVE;

        $this->request = $app['request'];

        $this->key = $this->config['pg_router_key'];

        $this->secret = $this->config['pg_router_secret'];

        $this->auth = $app['basicauth'];

        $this->setHeaders();
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function validateAndCreatePayment(array $input, bool $throwExceptionOnFailure = false): array
    {
        $this->updateIpandUserAgent($input);

        $this->currentEndPoint = self::PGRouterValidateAndCreatePayment;

        $output = $this->sendRequest(self::PGRouterValidateAndCreatePayment, Requests::POST, $input, $throwExceptionOnFailure, 90);

        return $output['body'];
    }

    public function validateAndCreatePaymentCheckout(array $input, bool $throwExceptionOnFailure = false): array
    {
        $this->updateIpandUserAgent($input);

        $this->currentEndPoint = self::PGRouterValidateAndCreatePaymentCheckout;

        $output = $this->sendRequest(self::PGRouterValidateAndCreatePaymentCheckout, Requests::POST, $input, $throwExceptionOnFailure, 90);

        return $output['body'];
    }

    public function validateAndCreatePaymentUpi(array $input, bool $throwExceptionOnFailure = false): array
    {
        $this->updateIpandUserAgent($input, true);

        $this->currentEndPoint = self::PGRouterValidateAndCreatePaymentUpi;

        $output = $this->sendRequest(self::PGRouterValidateAndCreatePaymentUpi, Requests::POST, $input, $throwExceptionOnFailure, 90);

        return $output['body'];
    }

    public function validateAndCreatePaymentJson(array $input, bool $throwExceptionOnFailure = false): array
    {
        $this->updateIpandUserAgent($input, true);

        $this->currentEndPoint = self::PGRouterPaymentCreateJson;

        $output = $this->sendRequest(self::PGRouterPaymentCreateJson, Requests::POST, $input, $throwExceptionOnFailure, 90);

        return $output['body'];
    }

    public function validateAndCreatePaymentRedirect(array $input, bool $throwExceptionOnFailure = false): array
    {
        $this->updateIpandUserAgent($input, true);

        $this->currentEndPoint = self::PGRouterPaymentCreateRedirect;

        $output = $this->sendRequest(self::PGRouterPaymentCreateRedirect, Requests::POST, $input, $throwExceptionOnFailure, 90);

        return $output['body'];
    }

    protected function updateIpandUserAgent(array & $input, $s2s = false)
    {
        $ip = null;
        $userAgent = null;

        if (empty($this->request) === false)
        {
            $ip         = $this->request->ip();
            $userAgent  = $this->request->userAgent();
        }

        if ($s2s === true)
        {
            $ip = $input['ip'] ?? $ip;
            $userAgent = $input['user_agent'] ?? $userAgent;
        }

        $input['_']['ip'] = $ip;
        $input['_']['user_agent'] = $userAgent;
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function paymentCapture(string $id, array $captureParams, bool $throwExceptionOnFailure = false): array
    {
        $url = sprintf(self::PGRouterPaymentCapture, $id);

        $this->currentEndPoint = self::PGRouterPaymentCapture;

        $output = $this->sendRequest($url, Requests::POST, $captureParams, $throwExceptionOnFailure, 90);

        return $output['body']['data']['payment'];
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function paymentCancel(string $id, string $merchantId, bool $throwExceptionOnFailure = false): array
    {
        $url = sprintf(self::PGRouterPaymentCancel, $id);

        if (empty($merchantId) === false)
        {
            $url .= '?merchant_id='.$merchantId;
        }

        $this->currentEndPoint = self::PGRouterPaymentCancel;

        $output = $this->sendRequest($url, Requests::GET, [], $throwExceptionOnFailure);

        return $output['body'];
    }

     /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function paymentAuthenticate(string $id, array $input, bool $throwExceptionOnFailure = false): array
    {
        $this->updateIpandUserAgent($input, true);

        $url = sprintf(self::PGRouterPaymentAuthenticate, $id);

        $this->currentEndPoint = self::PGRouterPaymentAuthenticate;

        $output = $this->sendRequest($url, Requests::POST, $input, $throwExceptionOnFailure);

        return $output['body'];
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function paymentVerify(string $id, bool $throwExceptionOnFailure = false): array
    {
        $url = sprintf(self::PGRouterPaymentVerify, $id);

        $this->currentEndPoint = self::PGRouterPaymentVerify;

        $output = $this->sendRequest($url, Requests::GET, [], $throwExceptionOnFailure, 90);

        return $output['body']['data']['payment'];
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function initiatePayment(array $input, bool $throwExceptionOnFailure = false): array
    {
        $this->currentEndPoint = self::PGRouterInitiatePayment;

        return $this->sendRequest(self::PGRouterInitiatePayment, Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function syncOrderToPgRouter(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::PGRouterOrderSyncUrl, Requests::POST, $input, $throwExceptionOnFailure);
    }

    public function updateSyncedOrderToPgRouter(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::PGRouterUpdateSyncedOrderUrl, Requests::PATCH, $input, $throwExceptionOnFailure);
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function syncBulkOrderToPgRouter(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::PGRouterBulkOrderSyncUrl, Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * This method has been called from External Repo.
     *
     * @param string $entity
     * @param string $id
     * @param string $merchantId
     * @param array $input
     * @return Card\Entity|Order\Entity|Payment\Entity|null
     */
    public function fetch(string $entity, string $id, string $merchantId, array $input)
    {
        switch ($entity)
        {
            case Entity::ORDER:
                return $this->fetchOrder($id, $merchantId, $input);
            case Entity::CARD:
                return $this->fetchCard($id, $merchantId, $input);
            case Entity::PAYMENT:
                return $this->fetchPayment($id, $merchantId, $input);
        }

        return null;
    }

    public function fetchCard(string $id, string $merchantId, array $input)
    {
        $endpoint = 'v1/cards/' . $id;

        if (empty($merchantId) === false)
        {
            $endpoint .= '?merchant_id='.$merchantId;
        }

        $this->currentEndPoint = self::PGRouterFetchCard;

        $response = $this->sendRequest($endpoint, Requests::GET, [], false, 2, true);

        if (empty($response) === false and isset($response['body']['data']['card']))
        {
            return (new Card\Entity)->forceFill($response['body']['data']['card']);
        }

        return null;
    }

    public function fetchOrderPayments(string $orderId, string $merchantId, $withCard = false)
    {
        $endpoint = sprintf(self::PGRouterFetchOrderPayments, $orderId);

        $endpoint = $endpoint."?merchant_id=".$merchantId;

        $card = null;

        $this->currentEndPoint = self::PGRouterFetchOrderPayments;

        $response = $this->sendRequest($endpoint, Requests::GET, [], false, self::DEFAULT_REQUEST_TIMEOUT, true);

        $collection = new PublicCollection();

        if (empty($response) === false and empty($response['body']) === false)
        {
            $paymentsData = $response["body"];

            forEach($paymentsData as $payment)
            {
                if(isset($payment['data']['payment']) === true)
                {
                    if (isset($payment['data']['payment']['acquirer_data']) === true and
                        isset($payment['data']['payment']['acquirer_data']['auth_code']) === true)
                    {
                        $payment['data']['payment']['reference2'] =
                            $payment['data']['payment']['acquirer_data']['auth_code'];
                    }

                    if (isset($payment['data']['payment']['card']) === true)
                    {
                        $payment['data']['payment']['card']['id'] = $payment['data']['payment']['id'];

                        $card = (new Card\Entity)->forceFill($payment['data']['payment']['card']);

                        $card->setExternal(true);

                        unset($payment['data']['payment']['card']);
                    }

                    if (isset($payment['data']['payment']['notes']) === true and is_array($payment['data']['payment']['notes']) === false)
                    {
                        $payment['data']['payment']['notes'] = json_decode($payment['data']['payment']['notes']);
                    }

                    $paymentEntity = (new Payment\Entity)->forceFill($payment['data']['payment']);

                    if ($paymentEntity->isUpi() === true)
                    {
                        $input = $payment['data']['payment'];

                        (new Payment\Entity)->modifyInput($input);

                        $paymentEntity = (new Payment\Entity)->forceFill($input);
                    }

                    if (($card !== null) and ($withCard === true))
                    {
                        $paymentEntity->card()->associate($card);
                    }

                    if ($paymentEntity->isFailed() === false)
                    {
                        $paymentEntity->setErrorNull();
                    }

                    $collection->push($paymentEntity);
                }
            }
        }
        return $collection;
    }

    public function fetchPayment(string $id, string $merchantId, array $input)
    {
        $endpoint = 'v1/payments/' . $id;

        if (empty($merchantId) === false)
        {
            $endpoint .= '?merchant_id='.$merchantId;
        }

        $this->currentEndPoint = self::PGRouterFetchPayment;

        $card = null;

        $response = $this->sendRequest($endpoint, Requests::GET, [], false, 2, true);

        if (empty($response) === false and isset($response['body']['data']['payment']))
        {
            if (isset($response['body']['data']['payment']['acquirer_data']) === true and
                isset($payment['body']['data']['payment']['acquirer_data']['auth_code']) === true)
            {
                $response['body']['data']['payment']['reference2'] =
                    $response['body']['data']['payment']['acquirer_data']['auth_code'];
            }

            if (isset($response['body']['data']['payment']['card']) === true)
            {
                $response['body']['data']['payment']['card']['id'] = $response['body']['data']['payment']['id'];

                $card = (new Card\Entity)->forceFill($response['body']['data']['payment']['card']);

                $card->setExternal(true);

                unset($response['body']['data']['payment']['card']);
            }

            $payment = (new Payment\Entity)->forceFill($response['body']['data']['payment']);

            if ($payment->isUpi() === true)
            {
                $input = $response['body']['data']['payment'];

                (new Payment\Entity)->modifyInput($input);

                $payment = (new Payment\Entity)->forceFill($input);
            }

            if ($card !== null)
            {
                $payment->card()->associate($card);
            }

            if ($payment->isFailed() === false)
            {
                $payment->setErrorNull();
            }

            return $payment;
        }

        return null;
    }

    public function fetchOrder(string $id, string $merchantId, array $input)
    {
        if ((app()->isEnvironmentProduction() === true) and
            ($this->mode === Mode::TEST))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $endpoint = 'v1/orders/' . $id;

        $this->currentEndPoint = self::PGRouterFetchOrder;

        if (empty($merchantId) === false)
        {
            $endpoint .= '?merchant_id='.$merchantId;
        }

        $response = $this->sendRequest($endpoint, Requests::GET, [], true, 2, true);

        return $this->forceFillOrderFromResponse($response);
    }

    public function save(string $entity, string $id, string $merchantId, array $input)
    {
        switch($entity)
        {
            case Entity::ORDER:

                return $this->updateInternalOrder($input, $id, $merchantId, true);

            case Entity::PAYMENT:

                $endpoint = 'v1/payments/'.$id;

                return $this->sendRequest($endpoint, Requests::POST, $input, true);
        }
        return null;
    }

    public function createOrder(array $input, bool $throwExceptionOnFailure = false)
    {
        $this->currentEndPoint = self::PGRouterCreateOrder;

        $response = $this->sendRequest(self::PGRouterCreateOrder, Requests::POST, $input, $throwExceptionOnFailure);

        return $this->forceFillOrderFromResponse($response);
    }

    public function updateOrder(array $input, $orderId, $merchantId, bool $throwExceptionOnFailure = false)
    {
        $endpoint = 'v1/orders/' . $orderId;

        if (empty($merchantId) === false)
        {
            $endpoint .= '?merchant_id='.$merchantId;
        }

        $this->currentEndPoint = self::PGRouterFetchOrder;

        $response = $this->sendRequest($endpoint, Requests::PATCH, $input, $throwExceptionOnFailure);

        return $this->forceFillOrderFromResponse($response);
    }

    public function updateInternalOrder(array $input, $orderId, $merchantId, bool $throwExceptionOnFailure = false, $timeout = self::DEFAULT_REQUEST_TIMEOUT)
    {
        $endpoint = 'v1/internal/orders/' . $orderId;

        if (empty($merchantId) === false)
        {
            $endpoint .= '?merchant_id='.$merchantId;
        }

        $this->currentEndPoint = self::PGRouterUpdateInternalOrder;

        $response = $this->sendRequest($endpoint, Requests::PATCH, $input, $throwExceptionOnFailure, $timeout, true);

        return $this->forceFillOrderFromResponse($response);
    }

    public function updateCurrencyCache(array $input, bool $throwExceptionOnFailure = false)
    {
        $endpoint = 'v1/update/currency/rate';

        $this->currentEndPoint = $endpoint;

        return $this->sendRequest($endpoint, Requests::PATCH, $input, $throwExceptionOnFailure, self::DEFAULT_REQUEST_TIMEOUT, true);
    }

    public function getOrderEntityFromOrderAttributes(array $orderAttributes): ?Order\Entity
    {
        $response['body'] = $orderAttributes;

        return $this->forceFillOrderFromResponse($response);
    }

    private function forceFillOrderFromResponse($response)
    {
        if ((empty($response) === false) and
            (isset($response['body']) === true))
        {
            if (isset($response['body']['notes']) === true and is_array($response['body']['notes']) === false)
            {
                $response['body']['notes'] = json_decode($response['body']['notes']);
            }

            $response['body']['bank_account_data'] = $response['body']['bank_account'];

            $order = (new Order\Entity())->forceFill($response['body']);

            if (isset($response['body']['order_metas']) === true)
            {
                foreach ($response['body']['order_metas'] as $meta)
                {
                    $order_meta = (new Order\OrderMeta\Entity)->forceFill($meta);

                    $order->orderMetas->add($order_meta);
                }
            }

            $entityOffers = (new EntityOfferRepository())->findByEntityIdAndType($order->getId(), 'offer');

            if ((isset($entityOffers) === true) and
                (count($entityOffers) > 0))
            {
                $order->offers = new PublicCollection();

                foreach ($entityOffers as $entityOffer)
                {
                    $offer = Offer\Entity::findOrFail($entityOffer->offer_id);

                    $order->offers->push($offer);
                }
            }

            $order->setExternal(true);

            if (strpos($this->request->getRequestUri(), '/v1/admin/') !== 0)
            {
                return $this->forceFillNonAdminEntites($order);
            }

            return $order;
        }

        return null;
    }

    public function forceFillNonAdminEntites($order)
    {
        if (isset($response['body']['merchant_id']) === true)
        {
            $merchant = Merchant\Entity::findOrFail($response['body']['merchant_id']);

            $order = $order->merchant()->associate($merchant);
        }

        return $order;
    }

    public function otpSubmitPrivate($id, array $input, bool $throwExceptionOnFailure = false): array
    {
        $url = sprintf(self::PGRouterOTPSubmitPrivate, $id);

        $this->currentEndPoint = self::PGRouterOTPSubmitPrivate;

        $output = $this->sendRequest($url, Requests::POST, $input, $throwExceptionOnFailure);

        return $output['body'];
    }

    public function otpResendPrivate($id, array $input, bool $throwExceptionOnFailure = false): array
    {
        $url = sprintf(self::PGRouterOTPResendPrivate, $id);

        $this->currentEndPoint = self::PGRouterOTPResendPrivate;

        $output = $this->sendRequest($url, Requests::POST, $input, $throwExceptionOnFailure);

        return $output['body'];
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     * @param bool   $throwExceptionOnFailure
     *
     * @return array
     */
    public function sendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        bool $throwExceptionOnFailure = false,
        int $timeout = self::DEFAULT_REQUEST_TIMEOUT,
        bool $retry = false)
    {
        $request = $this->generateRequest($endpoint, $method, $data, $timeout);

        if ($retry === true)
        {
            $response = $this->sendPGRouterRequestWithRetry($request, $endpoint);
        }
        else
        {
            $response = $this->sendPGRouterRequest($request, $endpoint);
        }

        if (strpos($response->headers['content-type'], 'text/html') !== false)
        {
            $decodedResponse = [
                'html' => $response->body
            ];
        }
        else
        {
            $decodedResponse = json_decode($response->body, true);
        }

        $traceData = $decodedResponse;

        // SBB Issue - Axis Migs
        unset($traceData["request"]["content"]);

        unset($traceData["response"]["data"]["payment"]["card"]);

        $logResponse = $this->shouldLogResponse($endpoint, $method);

        if($logResponse === true)
        {
            $this->trace->info(TraceCode::PG_ROUTER_RESPONSE,
                ["response" => $traceData ?? [],
                    "statusCode" => $response->status_code
                ]);
        }

        $this->currentEndPoint = "";

        return $this->parseResponse($decodedResponse, $response->status_code, $throwExceptionOnFailure, $endpoint);
    }

    public function shouldLogResponse(string $endpoint, string $method) :bool
    {
        $mapKey = $method.'_'.$endpoint;

        $logResponse = true;

        if(isset(self::RESPONSE_LOGGER_MAP[$mapKey]))
        {
            $logResponse = self::RESPONSE_LOGGER_MAP[$mapKey];
        }
        else
        {
            if($this->currentEndPoint !== "" and isset(self::RESPONSE_LOGGER_MAP[$method.'_'.$this->currentEndPoint]) === true)
            {
                $logResponse = self::RESPONSE_LOGGER_MAP[$method.'_'.$this->currentEndPoint];
            }
        }
        return $logResponse;
    }

    /**
     * Function used to set headers for the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]              = 'application/json';
        $headers[self::CONTENT_TYPE]        = 'application/json';
        $headers[self::X_MODE]              = $this->mode;
        $headers[self::X_REQUEST_ID]        = $this->request->getId();
        $headers[self::X_REQUEST_TASK_ID]   = $this->request->getTaskId();

        $this->headers = $headers;
    }

    /**
     * @param array $request
     *
     * @return \WpOrg\Requests\Response
     * @throws \WpOrg\Requests\Exception
     */
    protected function sendPGRouterRequest(array $request, string $endpoint)
    {
        $this->traceRequest($request, $endpoint);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);
        }
        catch(\WpOrg\Requests\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_ROUTER_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);

            $dimensions = [
                'url' => $request['url']
            ];

            $this->trace->count(self::PG_ROUTER_REQUEST_FAILURE, $dimensions);

            throw $e;
        }

        return $response;
    }

    protected function sendPGRouterRequestWithRetry(array $request, string $endpoint)
    {
        $this->traceRequest($request, $endpoint);

        $res = null;
        $exception = null;
        $maxAttempts = 3;

        while ($maxAttempts--)
        {
            try
            {
                $res = Requests::request(
                    $request['url'],
                    $request['headers'],
                    $request['content'],
                    $request['method'],
                    $request['options']);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PG_ROUTER_REQUEST_FAILURE,
                    [
                        'data' => $e->getMessage()
                    ]);

                $dimensions = [
                    'url' => $this->currentEndPoint
                ];

                $this->trace->count(self::PG_ROUTER_REQUEST_FAILURE, $dimensions);

                $exception = $e;

                continue;
            }
            if ($res !== null and $res->status_code > 500)
            {
                continue;
            }

            // In case it succeeds in another attempt.
            $exception = null;
            break;
        }

        // An exception is thrown by lib in cases of network errors e.g. timeout etc.
        if ($exception !== null)
        {
            throw new $exception;
        }

        return $res;
    }

    /**
     * @param array $request
     */
    protected function traceRequest(array $request, string $endpoint)
    {
        $traceRequest = $request;

        unset($traceRequest['options']['auth']);

        if (is_array($traceRequest['content']) === true)
        {
            unset($traceRequest['content']['order_sync_request']['account_number']);

            unset($traceRequest['content']['card']['number']);

            unset($traceRequest['content']['card']['cvv']);

            unset($traceRequest['content']['bank_account']['account_number'], $traceRequest['content']['bank_account']['name'],
                $traceRequest['content']['notes'], $traceRequest['content']['receipt'], $traceRequest['content']['cardnumber'],
                $traceRequest['content']['products']);
        }
        else
        {
            $content = json_decode($traceRequest['content'], true);

            unset($content['card']['number']);

            unset($content['card']['cvv']);

            unset($content['bank_account']['account_number'], $content['bank_account']['name'],
                $content['notes'], $content['receipt'], $content['cardnumber'],
                $content['products']);

            $traceRequest['content'] = json_encode($content);
        }

        $logRequest = $this->shouldLogRequest($endpoint, $request['method']);

        if($logRequest === true)
        {
            $this->trace->info(TraceCode::PG_ROUTER_REQUEST, $traceRequest);
        }
    }

    public function shouldLogRequest(string $endpoint, string $method) :bool
    {
        $logRequest = true;
        $mapKey = $method.'_'.$endpoint;
        if(isset(self::REQUEST_LOGGER_MAP[$mapKey]))
        {
            $logRequest = self::REQUEST_LOGGER_MAP[$mapKey];
        }
        else
        {
            if($this->currentEndPoint !== "" and isset(self::REQUEST_LOGGER_MAP[$method.'_'.$this->currentEndPoint]) === true)
            {
                $logRequest = self::REQUEST_LOGGER_MAP[$method.'_'.$this->currentEndPoint];
            }
        }

        return $logRequest;
    }

    /**
     * @param $response
     * @param $statusCode
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\ServerErrorException
     */
    protected function parseResponse($response, $statusCode, bool $throwExceptionOnFailure = false, $endpoint = ""): array
    {
        if (in_array($statusCode, [503], true) === true)
        {
            //TODO: Check if we have to disable admin config for rearch routing logic
            throw new Exception\ServerErrorException('PG Router Service is unreachable', ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE);
        }

        if ($response === null)
        {
            throw new Exception\ServerErrorException('PG Router Response cannot be null', ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE);
        }

        if ($throwExceptionOnFailure === true)
        {
            $this->checkForErrors($response,$statusCode, $endpoint);
        }

        return [
            'body' => $response,
            'code' => $statusCode,
        ];
    }

    public function checkForErrors($response, $statusCode, $endpoint = "")
    {
        if (isset($response['error']) === false)
        {
            return;
        }

        $errorCode = $response['error']['code'];

        $description = $response['error']['description'];

        $metadata = null;

        if (isset($response['error']['metadata']) === true)
        {
            $metadata = $response['error']['metadata'];
        }

        $errorData = [];

        if (($metadata != null) and
            (isset($metadata['payment_id']) === true))
        {
            $errorData['payment_id'] = $metadata['payment_id'];
        }

        if (($metadata != null) and
            (isset($metadata['order_id']) === true))
        {
            $errorData['order_id'] = $metadata['order_id'];
        }

        $internalErrorCode = $response['internal']['code'];

        $internalMetadata = null;

        if (isset($response['internal']['metadata']) === true)
        {
            $internalMetadata = $response['internal']['metadata'];
        }

        //TODO: Get method from pg router and update here
        $errorData['method'] = "card";

        if (($internalMetadata != null) and
            (isset($internalMetadata['service']) === true))
        {
            $errorData['method'] = $internalMetadata['service'];
        }

        $dimensions = [
            "status_code"           => $statusCode,
            "internal_error_code"   => $internalErrorCode,
            "route"                 => $endpoint
        ];
        $this->trace->count(self::PG_ROUTER_FAILURE_STATUS_CODE, $dimensions);

        $class = Error::getErrorClassFromErrorCode($errorCode);

        switch ($class)
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors($internalErrorCode, $description,$errorData);
                break;

            case ErrorClass::BAD_REQUEST:
                if (empty($response['next']) === false)
                {
                    $errorData['next'] = $response['next'];
                }
                throw new Exception\BadRequestException(
                    $internalErrorCode, null, $errorData, $description);

            case ErrorClass::SERVER:
                throw new Exception\ServerErrorException('Error with PG Router service',
                    ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE, $errorData);

            default:
                throw new Exception\InvalidArgumentException('Not a valid error code class',
                    array_merge(['errorClass' => $class], $errorData));
        }
    }

    protected function handleGatewayErrors($internalErrorCode, $description, $errorData)
    {
        switch ($internalErrorCode)
        {
            case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                throw new Exception\GatewayRequestException($description,null,false,$errorData);

            case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                throw new Exception\GatewayTimeoutException($description,null,false,$errorData);

            default:
                throw new Exception\GatewayErrorException($internalErrorCode,
                    null,null, $errorData);
        }
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

        // json encode if data is must, else ignore
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

        $this->setHeaders();

        $headers = $this->headers;

        $headers['PHP_AUTH_USER'] = $this->auth->getPublicKey();

        if (isset($this->app['rzp.mode']) and $this->app['rzp.mode'] === 'test')
        {
            $testCaseId = $this->request->header('X-RZP-TESTCASE-ID');

            if (empty($testCaseId) === false)
            {
                $headers['X-RZP-TESTCASE-ID'] = $testCaseId;
            }
        }

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

    private function logResponseTimeOfPgRouter(float $startTime, string $requestUrl)
    {
        try
        {
            $responseTime = get_diff_in_millisecond($startTime);

            $dimensions = [
                "request_url" => $requestUrl
            ];

            $this->trace->histogram(Metric::PG_ROUTER_ORDER_SYNC_RESPONSE_TIME, $responseTime, $dimensions);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_ROUTER_ERROR_LOGGING_RESPONSE_TIME_METRIC
            );
        }
    }

    /**
     * @param string $paymentId
     * @param $input
     * @return array
     */
    public function sendStaticCallbackRequestToPgRouter(string $paymentId, $input) {

        $url = sprintf(self::PG_ROUTER_STATIC_CALLBACK, $paymentId);

        $output = $this->sendRequest($url, Requests::POST, $input, true, 90);

        return $output['body'];
    }
}
