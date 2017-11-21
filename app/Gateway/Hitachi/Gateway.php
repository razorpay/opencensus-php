<?php

namespace RZP\Gateway\Hitachi;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Gateway\Blade;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\HashAlgo;
use RZP\Constants\Timezone;
use RZP\Models\Card\Network;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;

class Gateway extends Base\Gateway
{
    use Base\CardCacheTrait;
    use Base\AuthorizeFailed;

    protected $gateway = 'hitachi';

    protected $secureCacheDriver;

    const CACHE_KEY = 'hitachi_%s_card_details';

    const TIME_FORMAT = 'His';
    const DATE_FORMAT = 'md';

    public function __construct()
    {
        parent::__construct();

        $this->secureCacheDriver = $this->app['config']->get('cache.secure_default');
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        $authResponse = $this->callAuthenticationGateway($input);

        if ($authResponse !== null)
        {
            $this->persistCardDetailsTemporarily($input);

            return $authResponse;
        }

        return $this->authorizeNotEnrolled($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->setCardNumberAndCvv($input);

        $authResponse = $this->callAuthenticationGateway($input);

        $this->authorizeEnrolled($input, $authResponse);

        // TODO: Add authenticate data for 2FA
        return $this->getCallbackResponseData($input);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                            $input['payment']['id'], Base\Action::AUTHORIZE);

        $request = $this->getCaptureRequestArray($input, $gatewayPayment);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::PAYMENT_CAPTURE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_CAPTURE_RESPONSE);

        $attributes = $this->getAttributesFromCaptureResponse($response);

        $this->createGatewayPaymentEntity($input, $attributes);

        $this->checkErrorsAndThrowException($response);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                            $input['payment']['id'], Base\Action::AUTHORIZE);

        $request = $this->getRefundRequestArray($input, $gatewayPayment);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($request, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        $attributes = $this->getAttributesFromRefundReverseResponse($response);

        $this->createGatewayRefundEntity($input, $attributes);

        $this->checkErrorsAndThrowException($response);
    }

    public function reverse(array $input)
    {
        parent::reverse($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                            $input['payment']['id'], Base\Action::AUTHORIZE);

        $request = $this->getReverseRequestArray($input, $gatewayPayment);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REVERSE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($request, $input, TraceCode::GATEWAY_REVERSE_RESPONSE);

        $attributes = $this->getAttributesFromRefundReverseResponse($response);

        $this->createGatewayRefundEntity($input, $attributes);

        $this->checkErrorsAndThrowException($response);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    /**
     * Hitachi does not do to the authentication step of the payment itself.
     * It calls Blade to authenticate, then uses the response to authorize.
     *
     * @param array $input
     * @throws Exception\GatewayErrorException
     * @return array|null
     */
    protected function callAuthenticationGateway(array $input)
    {
        return $this->app['gateway']->call(
            Payment\Gateway::BLADE,
            $this->action,
            $input,
            $this->mode);
    }

    protected function authorizeNotEnrolled(array $input)
    {
        $request = $this->getAuthorizeRequestArrayForNotEnrolled($input);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $attributes = $this->getAttributesFromAuthResponse($response);

        $this->createGatewayPaymentEntity($input, $attributes, Base\Action::AUTHORIZE);

        $this->checkErrorsAndThrowException($response);
    }

    protected function authorizeEnrolled(array $input, array $authResponse)
    {
        $request = $this->getAuthorizeRequestArrayForEnrolled($input, $authResponse);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $attributes = $this->getAttributesFromAuthResponse($response);

        $this->createGatewayPaymentEntity($input, $attributes, Base\Action::AUTHORIZE);

        $this->checkErrorsAndThrowException($response);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $verify->verifyResponseContent = $response;
    }

    protected function verifyPayment(Verify $verify)
    {
        $this->setVerifyStatus($verify);

        $verify->payment = $this->saveVerifyResponseIfNeeded($verify);
    }

    protected function setVerifyStatus(Verify $verify)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MISMATCH;

        if ($verify->apiSuccess === $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->status = $status;
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        // TODO: Need to confirm that it is S or RespCode 00
        if ((isset($content[ResponseFields::STATUS])) and
            ($content[ResponseFields::STATUS] === Status::SUCCESS) and
            ($content[ResponseFields::RESPONSE_CODE] === Status::SUCCESS_CODE))
        {
            $verify->gatewaySuccess = true;
        }
    }

    // ----------------------------------------- Request Arrays --------------------------------------------------------

    protected function getAuthorizeRequestArrayForEnrolled(array $input, array $authResponse)
    {
        $content = $this->getDefaultAuthorizeRequestArray($input);

        $content[RequestFields::AUTH_STATUS] = $authResponse[Blade\Entity::STATUS];
        $content[RequestFields::ECI]         = $authResponse[Blade\Entity::ECI];
        $content[RequestFields::XID]         = $authResponse[Blade\Entity::XID];
        $content[RequestFields::ALGORITHM]   = $authResponse[Blade\Entity::CAVV_ALGORITHM];

        $network = Network::getCode($this->input['card']['network']);

        if ($network === Card\Network::VISA)
        {
            $content[RequestFields::CAVV2] = $authResponse[Blade\Entity::CAVV];
        }
        else if ($network === Card\Network::MC)
        {
            $content[RequestFields::UCAF] = $authResponse[Blade\Entity::CAVV];
        }
        else
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_TYPE_INVALID);
        }

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_REQUEST,
            [
                'request'    => $content,
                'gateway'    => 'hitachi',
                'payment_id' => $input['payment']['id'],
            ]);

        $content += $this->getCardDataForAuthorizeRequestArray($input);

        return $this->getStandardRequestArray($content);
    }

    protected function getAuthorizeRequestArrayForNotEnrolled(array $input)
    {
        $content = $this->getDefaultAuthorizeRequestArray($input);

        $content[RequestFields::AUTH_STATUS] = '';
        $content[RequestFields::ECI]         = '';
        $content[RequestFields::XID]         = '';
        $content[RequestFields::ALGORITHM]   = '';
        $content[RequestFields::CAVV2]       = '';

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_REQUEST,
            [
                'request'    => $content,
                'gateway'    => 'hitachi',
                'payment_id' => $input['payment']['id'],
            ]);

        $content += $this->getCardDataForAuthorizeRequestArray($input);

        return $this->getStandardRequestArray($content);
    }

    protected function traceGatewayPaymentResponse(
        $response,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_RESPONSE)
    {
        unset($response[ResponseFields::CARD_NUMBER]);

        $this->trace->info($traceCode,
            [
                'response'   => $response,
                'gateway'    => 'hitachi',
                'payment_id' => $input['payment']['id'],
            ]);
    }

    protected function getDefaultAuthorizeRequestArray(array $input)
    {
        $time = Carbon::now(Timezone::IST)->format(self::TIME_FORMAT);
        $date = Carbon::now(Timezone::IST)->format(self::DATE_FORMAT);

        $expiry = substr($input['card']['expiry_year'], 2) . $input['card']['expiry_month'];

        return [
            RequestFields::TRANSACTION_TYPE    => TransactionType::AUTH,
            RequestFields::TRANSACTION_AMOUNT  => str_pad($input['payment']['amount'], 10, 0, STR_PAD_LEFT),
            RequestFields::TRANSACTION_TIME    => $time,
            RequestFields::TRANSACTION_DATE    => $date,
            RequestFields::MERCHANT_ID         => $this->getMerchantId(),
            RequestFields::MERCHANT_REF_NUMBER => $input['payment']['id'],
            RequestFields::EXPIRY_DATE         => $expiry,
        ];
    }

    protected function getCardDataForAuthorizeRequestArray(array $input)
    {
        return [
            RequestFields::CARD_NUMBER         => $input['card']['number'],
            RequestFields::CVV2                => $input['card']['cvv'],
        ];
    }

    protected function getCaptureRequestArray(array $input, Entity $gatewayPayment)
    {
        $createdAt = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST);

        $content = [
            RequestFields::TRANSACTION_TYPE    => TransactionType::CAPTURE,
            RequestFields::REQUEST_ID          => $input['payment']['id'],
            RequestFields::TRANSACTION_AMOUNT  => $input['payment']['amount'] / 100,
            RequestFields::TRANSACTION_TIME    => $createdAt->format(self::TIME_FORMAT),
            RequestFields::TRANSACTION_DATE    => $createdAt->format(self::DATE_FORMAT),
            RequestFields::RETRIEVAL_REF_NUM   => $gatewayPayment->getRrn(),
            RequestFields::MERCHANT_ID         => $this->getMerchantId(),
            RequestFields::MERCHANT_REF_NUMBER => $input['payment']['id']
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function getRefundRequestArray(array $input, Entity $gatewayPayment)
    {
        $content = $this->getDefaultRefundReverseArray($input, $gatewayPayment);

        $content[RequestFields::TRANSACTION_TYPE] = TransactionType::REFUND;
        $content[RequestFields::REQUEST_ID]       = $input['refund']['id'];

        return $this->getStandardRequestArray($content);
    }

    protected function getReverseRequestArray(array $input, Entity $gatewayPayment)
    {
        $content = $this->getDefaultRefundReverseArray($input, $gatewayPayment);

        $content[RequestFields::TRANSACTION_TYPE] = TransactionType::VOID;

        return $this->getStandardRequestArray($content);
    }

    protected function getDefaultRefundReverseArray(array $input, Entity $gatewayPayment)
    {
        $createdAt = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST);

        $time = $createdAt->format(self::TIME_FORMAT);
        $date = $createdAt->format(self::DATE_FORMAT);

        $content = [
            RequestFields::TRANSACTION_AMOUNT  => $input['refund']['amount'] / 100,
            RequestFields::TRANSACTION_TIME    => $time,
            RequestFields::TRANSACTION_DATE    => $date,
            RequestFields::RETRIEVAL_REF_NUM   => $gatewayPayment->getRrn(),
            RequestFields::MERCHANT_ID         => $this->getMerchantId(),
            RequestFields::MERCHANT_REF_NUMBER => $input['refund']['id']
        ];

        return $content;
    }

    protected function getVerifyRequestArray(array $input)
    {
        $content = [
            RequestFields::TRANSACTION_TYPE    => TransactionType::TXN,
            RequestFields::REQUEST_ID          => $input['payment']['id'],
            RequestFields::TRANSACTION_AMOUNT  => $input['payment']['amount'] / 100,
            RequestFields::MERCHANT_ID         => $this->getMerchantId(),
            RequestFields::MERCHANT_REF_NUMBER => $input['payment']['id']
        ];

        return $this->getStandardRequestArray($content);
    }

    // ----------------------------------------- Get Attributes --------------------------------------------------------

    protected function getAttributesFromAuthResponse(array $response) : array
    {
        $attributes = [
            Entity::RECEIVED      => true,
            Entity::RRN           => $response[ResponseFields::RETRIEVAL_REF_NUM] ?? null,
            Entity::RESPONSE_CODE => $response[ResponseFields::RESPONSE_CODE] ?? null,
            Entity::AUTH_ID       => $response[ResponseFields::AUTH_ID] ?? null,
        ];

        return $attributes;
    }

    protected function getAttributesFromCaptureResponse(array $response) : array
    {
        $attributes = [
            Entity::RECEIVED      => true,
            Entity::RRN           => $response[ResponseFields::RETRIEVAL_REF_NUM],
            Entity::RESPONSE_CODE => $response[ResponseFields::RESPONSE_CODE],
            Entity::REQUEST_ID    => $response[ResponseFields::REQUEST_ID],
        ];

        return $attributes;
    }

    protected function getAttributesFromRefundReverseResponse(array $response) : array
    {
        $attributes = [
            Entity::RRN           => $response[ResponseFields::RETRIEVAL_REF_NUM],
            Entity::RESPONSE_CODE => $response[ResponseFields::RESPONSE_CODE],
        ];

        return $attributes;
    }

    protected function saveVerifyResponseIfNeeded(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $gatewayAttributes = [
            Entity::STATUS     => $content[ResponseFields::STATUS],
            Entity::REQUEST_ID => $content[ResponseFields::REQUEST_ID],
        ];

        $gatewayPayment->fill($gatewayAttributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    // ----------------------------------------- Gateway Payment -------------------------------------------------------

    protected function createGatewayPaymentEntity(array $input, array $attributes = [], $action = null)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $action = $action ?: $this->action;

        $acquirer = $input['terminal']->getGatewayAcquirer();

        $gatewayPayment->setAcquirer($acquirer);

        $gatewayPayment->setAction($action);

        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->setCurrency($input['payment']['currency']);

        $gatewayPayment->setAmount($input['payment']['amount']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function createGatewayRefundEntity(array $input, array $attributes = [])
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $acquirer = $input['terminal']->getGatewayAcquirer();

        $gatewayPayment->setAcquirer($acquirer);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->setRefundId($input['refund']['id']);

        $gatewayPayment->setCurrency($input['payment']['currency']);

        $gatewayPayment->setAmount($input['refund']['amount']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    // --------------------------------------------- Helpers------------------------------------------------------------

    protected function checkErrorsAndThrowException(array $response)
    {
        if ($response[ResponseFields::RESPONSE_CODE] !== Status::SUCCESS_CODE)
        {
            $code = $response[ResponseFields::RESPONSE_CODE];

            $errorCode = ResponseCode::getErrorCode($code);

            $message = ResponseCode::getResponseMessage($code);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $code,
                $message);
        }
    }

    protected function getStringToHash($content, $glue = '')
    {
        $array = [
            $this->getSecret(),
            $content[RequestFields::MERCHANT_REF_NUMBER],
            $this->getMerchantId(),
            $this->getSecret2()
        ];

        return parent::getStringToHash($array, $glue);
    }

    protected function getHashOfString($str)
    {
        return hash(HashAlgo::SHA256, $str);
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        $body = json_encode($content);

        $request = parent::getStandardRequestArray($body, $method, $type);

        $request['headers']['checksum'] = $this->generateHash($content);
        $request['headers']['Content-Type'] = 'application/json';

        $request['options'] = [
            'timeout'         => 30,
            'connect_timeout' => 30,
            'verify'          => $this->getCaInfo(),
        ];

        return $request;
    }

    protected function sendGatewayRequest($request)
    {
        $response = parent::sendGatewayRequest($request);

        $body = $response->body;

        return $this->parseResponseBody($body);
    }

    protected function parseResponseBody(string $body)
    {
        // TODO: Ask hitachi to fix it.
        try
        {
            $responseArray = $this->jsonToArray($body);
        }
        catch (Exception\RuntimeException $e)
        {
            // This happens because gateway return two json instead of one
            // Till they fix this, we need to apply this hack

            $body = explode('}', $body)[0] . '}';

            $responseArray = $this->jsonToArray($body);
        }

        return $responseArray;
    }

    protected function getUrl($type = null)
    {
        return constant(Url::class . '::' . strtoupper($this->mode));
    }

    protected function getMerchantId()
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }

    protected function getSecret2()
    {
        $secret2 = $this->input['terminal']['gateway_terminal_password'];

        if ($this->mode === Mode::TEST)
        {
            $secret2 = $this->config['test_hash_secret2'];
        }

        return $secret2;
    }

    protected function getCaInfo()
    {
        $clientCertPath = dirname(__FILE__) . '/cainfo/cainfo.pem';

        return $clientCertPath;
    }
}
