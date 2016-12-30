<?php

namespace RZP\Gateway\Wallet\Jiomoney;

use Carbon\Carbon;

use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Verify;
use RZP\Models\Payment;
use RZP\Models\Payment\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Wallet\Base\Entity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $canRunOtpFlow = false;

    protected $topup = false;

    /**
     * Format for date param accepted by jiomoney
     */
    const DATE_FORMAT = 'YmdHis';

    const TXN_CHANNEL = 'WEB';

    /**
     * Needs to be sent in verify api calls to get response in json from gateway
     */
    const JSON_MODE = '2';

    /**
     * Defines the version for status query api that needs to be
     * passed in the request params.
     */
    const STATUS_QUERY_API_VERSION = '1.0';

    // Status code returned by jiomoney on successful transaction
    const SUCCESS_STATUS = 'SUCCESS';

    protected $gateway = 'wallet_jiomoney';

    protected $sortRequestContent = false;

    protected $map = [
        RequestFields::MERCHANT_ID => Entity::GATEWAY_MERCHANT_ID,
    ];

    protected $statusQueryValid = false;

    /**
     * Returns JioMoney request content to be redirected to from checkout
     *
     * @param  array  $input
     *
     * @return array  $request
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getPurchaseRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input);

        $contentToSave = [
            RequestFields::MERCHANT_ID => $this->getMerchantId(),
            RequestFields::PAYMENT_ID  => $input['payment'][Payment\Entity::ID],
            RequestFields::AMOUNT      => $input['payment'][Payment\Entity::AMOUNT],
            Entity::EMAIL              => $input['payment'][Payment\Entity::EMAIL],
            Entity::CONTACT            => $input['payment'][Payment\Entity::CONTACT],
            Entity::RECEIVED           => false
        ];

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);

        // Redirects to JioMoney payment page
        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $input['gateway'] = $this->parseResponseBody($input['gateway']);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $input['gateway']);

        $this->verifySecureHash($input['gateway']);

        if ($input['gateway'][ResponseFields::STATUS_CODE] !== StatusCode::SUCCESS)
        {
            return $this->callbackAuthFailureFlow($input);
        }

        $this->callbackAuthSuccessFlow($input);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequest($input);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->parseGatewayResponse($response);

        $this->verifySecureHash($content);

        $this->createWalletRefundEntity($content, $input);

        if ($content[ResponseFields::STATUS_CODE] !== StatusCode::SUCCESS)
        {
            $this->handleRefundFailure($content);
        }
    }

    //------------------Authorize helper methods begin--------------------------

    protected function getPurchaseRequestArray(array $input)
    {
        $payment = $input['payment'];

        $content = $this->getPurchaseRequestContent($payment, $input['callbackUrl']);

        return $this->getStandardRequestArray($content);
    }

    protected function getPurchaseRequestContent(array $payment, string $callbackUrl)
    {
        $timestamp = $this->getFormattedDateFromTimeStamp($payment[Payment\Entity::CREATED_AT]);

        $amount = $this->getFormattedAmount($payment[Payment\Entity::AMOUNT]);

        $content = [
            RequestFields::MERCHANT_ID                                                              => $this->getMerchantId(),
            RequestFields::CLIENT_ID                                                                => $this->getClientId(),
            RequestFields::CHANNEL                                                                  => self::TXN_CHANNEL,
            RequestFields::CALLBACK_URL                                                             => $callbackUrl,
            RequestFields::TOKEN                                                                    => '',
            self::getFormattedRequestField(RequestFields::TRANSACTION, RequestFields::PAYMENT_ID)   => $payment[Payment\Entity::ID],
            self::getFormattedRequestField(RequestFields::TRANSACTION, RequestFields::TIMESTAMP)    => $timestamp,
            self::getFormattedRequestField(RequestFields::TRANSACTION, RequestFields::TXN_TYPE)     => strtoupper(Action::PURCHASE),
            self::getFormattedRequestField(RequestFields::TRANSACTION, RequestFields::AMOUNT)       => $amount,
            self::getFormattedRequestField(RequestFields::TRANSACTION, RequestFields::CURRENCY)     => Currency::INR,
            self::getFormattedRequestField(RequestFields::SUBSCRIBER, RequestFields::CUSTOMER_NAME) => $payment[Payment\Entity::EMAIL],
            self::getFormattedRequestField(RequestFields::SUBSCRIBER, RequestFields::EMAIL)         => $payment[Payment\Entity::EMAIL],
            self::getFormattedRequestField(RequestFields::SUBSCRIBER, RequestFields::CONTACT)       => $payment[Payment\Entity::CONTACT]
        ];

        $hashArray = $this->getPurchaseRequestArrayToHash($content);

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($hashArray);

        return $content;
    }

    protected function getPurchaseRequestArrayToHash($content)
    {
        return [
            $content[RequestFields::CLIENT_ID],
            $content[self::getFormattedRequestField(RequestFields::TRANSACTION, RequestFields::AMOUNT)],
            $content[self::getFormattedRequestField(RequestFields::TRANSACTION, RequestFields::PAYMENT_ID)],
            $content[RequestFields::CHANNEL],
            $content[RequestFields::MERCHANT_ID],
            $content[RequestFields::TOKEN],
            $content[RequestFields::CALLBACK_URL],
            $content[self::getFormattedRequestField(RequestFields::TRANSACTION, RequestFields::TIMESTAMP)],
            $content[self::getFormattedRequestField(RequestFields::TRANSACTION, RequestFields::TXN_TYPE)]
        ];
    }

    //-------------------------------Authorize helper methods end---------------------------------

    //-------------------------------Callback helper methods begin--------------------------------

    protected function callbackAuthSuccessFlow(array $input)
    {
        $content = $input['gateway'];

        $date = $this->getEpochTime($content[ResponseFields::DATE], self::DATE_FORMAT);

        $contentToSave = [
            ResponseFields::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            ResponseFields::RESPONSE_CODE        => $content[ResponseFields::RESPONSE_CODE],
            ResponseFields::RESPONSE_DESCRIPTION => $content[ResponseFields::RESPONSE_DESCRIPTION],
            ResponseFields::GATEWAY_PAYMENT_ID   => $content[ResponseFields::GATEWAY_PAYMENT_ID],
            ResponseFields::DATE                 => $date,
            Entity::RECEIVED                     => true
        ];

        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);
    }

    protected function callbackAuthFailureFlow(array $input)
    {
        $content = $input['gateway'];

        $contentToSave = [
            ResponseFields::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            ResponseFields::RESPONSE_CODE        => $content[ResponseFields::RESPONSE_CODE],
            ResponseFields::RESPONSE_DESCRIPTION => $content[ResponseFields::RESPONSE_DESCRIPTION],
            ResponseFields::GATEWAY_PAYMENT_ID   => $content[ResponseFields::GATEWAY_PAYMENT_ID],
            ResponseFields::DATE                 => $content[ResponseFields::DATE]
        ];

        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);

        $this->handleCallbackFailure($content);
    }

    protected function handleCallbackFailure(array $content)
    {
        throw new Exception\GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
            $content[ResponseFields::RESPONSE_CODE],
            $content[ResponseFields::RESPONSE_DESCRIPTION]
        );
    }

    //-------------------------------Callback helper methods end----------------------------------

    //-------------------------------Refund helper functions begin--------------------------------

    protected function getRefundRequest(array $input)
    {
        $wallet = $this->repo->fetchWalletByPaymentId($input['payment']['id']);

        $content = $this->getRefundRequestContent($input, $wallet);

        $content = json_encode($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = $this->getRequestHeaders($content);

        return $request;
    }

    protected function getRefundRequestContent(array $input, $wallet)
    {
        $refundInfo = $this->generateRefundInfo($wallet);

        $timestamp = Carbon::now('Asia/Kolkata')->format(self::DATE_FORMAT);

        $content = [
            RequestFields::CLIENT_ID    => $this->getClientId(),
            RequestFields::MERCHANT_ID  => $this->getMerchantId(),
            RequestFields::CHANNEL      => self::TXN_CHANNEL,
            RequestFields::TOKEN        => '',
            RequestFields::CALLBACK_URL => 'NA',
            RequestFields::TRANSACTION  => [
                RequestFields::PAYMENT_ID => $input['refund']['id'],
                RequestFields::TIMESTAMP  => $timestamp,
                RequestFields::TXN_TYPE   => strtoupper(Action::REFUND),
                RequestFields::AMOUNT     => $this->getFormattedAmount($input['amount']),
                RequestFields::CURRENCY   => Currency::INR,
            ],
            RequestFields::REFUND_INFO  => $refundInfo,
        ];

        $content[RequestFields::CHECKSUM] = $this->getRefundRequestHash($content);

        return $content;
    }

    protected function generateRefundInfo($wallet)
    {
        $refundinfo = [
            $wallet['gateway_payment_id'],
            $this->getFormattedDateFromTimeStamp($wallet['date']),
            'NA'
        ];

        return implode('|', $refundinfo);
    }

    protected function getRefundRequestHash($content)
    {
        $hashArray = [
            $content[RequestFields::CLIENT_ID],
            $content[RequestFields::TRANSACTION][RequestFields::AMOUNT],
            $content[RequestFields::TRANSACTION][RequestFields::PAYMENT_ID],
            $content[RequestFields::CHANNEL],
            $content[RequestFields::MERCHANT_ID],
            $content[RequestFields::TOKEN],
            $content[RequestFields::CALLBACK_URL],
            $content[RequestFields::TRANSACTION][RequestFields::TIMESTAMP],
            $content[RequestFields::TRANSACTION][RequestFields::TXN_TYPE]
        ];

        return $this->getHashOfArray($hashArray);
    }

    protected function createWalletRefundEntity(array $content, array $input)
    {
        $refundAttributes = $this->getRefundEntityAttributesFromRefundResponse($content, $input);

        return $this->createGatewayRefundEntity($refundAttributes);
    }

    protected function getRefundEntityAttributesFromRefundResponse(array $content, array $input)
    {
        $refundAttributes = [
            Entity::PAYMENT_ID           => $input['payment']['id'],
            Entity::GATEWAY_MERCHANT_ID  => $this->getMerchantId(),
            Entity::ACTION               => $this->action,
            Entity::AMOUNT               => $input['refund']['amount'],
            Entity::RECEIVED             => true,
            Entity::WALLET               => $input['payment']['wallet'],
            Entity::GATEWAY_REFUND_ID    => $content[ResponseFields::GATEWAY_PAYMENT_ID],
            Entity::REFUND_ID            => $input['refund']['id'],
            Entity::EMAIL                => $input['payment']['email'],
            Entity::CONTACT              => $input['payment']['contact'],
            Entity::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            Entity::RESPONSE_CODE        => $content[ResponseFields::RESPONSE_CODE],
            Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::RESPONSE_DESCRIPTION]
        ];

        return $refundAttributes;
    }

    protected function handleRefundFailure(array $content)
    {
        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_REFUND_FAILED,
            $content[ResponseFields::RESPONSE_CODE],
            $content[ResponseFields::RESPONSE_DESCRIPTION]
        );
    }

    //-------------------Refund helper functions end----------------------------

    //------------------ Verify helper functions begin--------------------------

    /**
     * JioMoney payment verification is weird. They have 2 Apis
     *
     * 1. STATUSQUERY - Cache based api, returns transaction status stored
     *     in a cache and cache is wiped after 3 hours
     *
     * 2. CHECKPAYMENTSTATUS - DB based api, As per the documentation
     *     this will return transaction data 3-4 mins post transaction time.
     *     This might realistically be about 10 mins so for our verify use case,
     *     we first make a call to the STATUSQUERY API. The response has a
     *     flag to indicate if data was found in cache.
     *     If found, we proceed with the verify flow, else we make a call
     *     to CHECKPAYMENTSTATUS API and proceed with its response
     */
    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $statusQueryRequest = $this->getStatusQueryRequestArray($input);

        $statusQueryResponse = $this->sendGatewayRequest($statusQueryRequest);

        $content = $this->jsonToArray($statusQueryResponse->body);

        $response = $statusQueryResponse;

        if ($this->validStatusQueryResponse($content) === false)
        {
            $checkPaymentStatusRequest = $this->getCheckPaymentStatusRequest($input);

            $checkPaymentStatusResponse = $this->sendGatewayRequest($checkPaymentStatusRequest);

            $response = $checkPaymentStatusResponse;

            $content = $this->jsonToArray($response->body);
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content'    => $content,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $response;

        $verify->verifyResponseBody = $response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function verifyPayment($verify)
    {
        $gatewayPayment = $verify->payment;
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        $verify->apiSuccess = true;

        // apiSuccess if false if the payment entity is in failed or created state
        if (($input['payment']['status'] === Payment\Status::FAILED) or
                ($input['payment']['status'] === Payment\Status::CREATED))
        {
            $verify->apiSuccess = false;
        }

        if ($this->checkPaymentStatusResponseFailed($content) === true)
        {
            $verify->gatewaySuccess = false;
        }
        else
        {
            $verify->gatewaySuccess = ($this->getGatewayTxnStatus($content) === self::SUCCESS_STATUS) ? true : false;
        }
        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        // save the gateway payment entity with verify response values if there is a status mismatch
        if ($verify->match === false)
        {
            $verify->payment = $this->saveVerifyContent($verify);
        }
    }

    protected function saveVerifyContent($verify)
    {
        $gatewayPayment = $verify->payment;

        if ($verify->gatewaySuccess === true)
        {
            $walletAttributes = $this->getVerifyWalletCreateAttributes($verify);

            $gatewayPayment->fill($walletAttributes);

            $gatewayPayment->saveOrFail();
        }

        return $gatewayPayment;
    }

    protected function getVerifyWalletCreateAttributes($verify)
    {
        $payment = $this->input['payment'];

        $content = $verify->verifyResponseContent;

        $contentToSave = array(
            ResponseFields::AMOUNT               => $payment[Payment\Entity::AMOUNT],
            RequestFields::MERCHANT_ID           => $this->getMerchantId(),
            Entity::RECEIVED                     => true,
            Entity::EMAIL                        => $payment[Payment\Entity::EMAIL],
            Entity::CONTACT                      => $payment[Payment\Entity::CONTACT],
            ResponseFields::STATUS_CODE          => StatusCode::SUCCESS,
            ResponseFields::RESPONSE_CODE        => self::SUCCESS_STATUS,
            ResponseFields::RESPONSE_DESCRIPTION => 'APPROVED',
            ResponseFields::GATEWAY_PAYMENT_ID   => $this->getGatewayPaymentId($content)
        );

        return $contentToSave;
    }

    /**
     * Checks if txn data is present in status query response
     *
     * @param  array  $content STATUSQUERY API response
     *
     * @return bool
     */
    public function validStatusQueryResponse(array $content)
    {
        if (isset($content[StatusQueryResponseFields::RESPONSE_HEADER]) === true)
        {
            $this->statusQueryValid = true;

            return $content[StatusQueryResponseFields::RESPONSE_HEADER][StatusQueryResponseFields::API_STATUS] === '1';
        }

        return false;
    }

    protected function checkPaymentStatusResponseFailed(array $content)
    {
        if ($this->statusQueryValid === false)
        {
            return ($content[ResponseFields::RESPONSE][ResponseFields::RESPONSE_HEADER]
                    [ResponseFields::STATUS] !== self::SUCCESS_STATUS);
        }

        return false;
    }

    protected function getGatewayTxnStatus(array $content)
    {
        if ($this->statusQueryValid === true)
        {
            return $content[StatusQueryResponseFields::PAYLOAD_DATA][StatusQueryResponseFields::TXN_STATUS];
        }

        return $content[ResponseFields::RESPONSE][ResponseFields::CHECKPAYMENTSTATUS]
                [ResponseFields::TXN_STATUS];
    }

    protected function getGatewayPaymentId(array $content)
    {
        if ($this->statusQueryValid === true)
        {
            return $content[StatusQueryResponseFields::PAYLOAD_DATA][StatusQueryResponseFields::JM_TRAN_REF_NO];
        }

        return $content[ResponseFields::RESPONSE][ResponseFields::CHECKPAYMENTSTATUS]
                [ResponseFields::JM_TRAN_REF_NO];
    }

    protected function getCheckPaymentStatusRequest(array $input)
    {
        $this->domainType = $this->mode . '_' . $this->action;

        $content = [
            RequestFields::APINAME       => ApiName::CHECKPAYMENTSTATUS,
            RequestFields::MODE          => self::JSON_MODE,
            RequestFields::REQUEST_ID    => gen_uuid(false),
            RequestFields::STARTDATETIME => 'NA',
            RequestFields::ENDDATETIME   => 'NA',
            RequestFields::MERCHANT_ID   => $this->getMerchantId(),
            RequestFields::PAYMENT_ID    => $input['payment']['id']
        ];

        $hashString = $this->getStringToHash(array_values($content), '~');

        $content[RequestFields::CHECKSUM] = $this->getHashOfString($hashString);

        $content = implode('~', array_values($content));

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = $this->getRequestHeaders($content);

        return $request;
    }

    protected function getStatusQueryRequestArray(array $input)
    {
        $content = [
            StatusQueryRequestFields::REQUEST_HEADER => [
                StatusQueryRequestFields::VERSION  => self::STATUS_QUERY_API_VERSION,
                StatusQueryRequestFields::API_NAME => ApiName::STATUSQUERY,
            ],
            StatusQueryRequestFields::PAYLOAD_DATA => [
                StatusQueryRequestFields::CLIENT_ID   => $this->getClientId(),
                StatusQueryRequestFields::MERCHANT_ID => $this->getMerchantId(),
                StatusQueryRequestFields::TRAN_REF_NO => $input['payment']['id']
            ]
        ];

        $hashArray = [
            $this->getClientId(),
            $this->getMerchantId(),
            ApiName::STATUSQUERY,
            $input['payment'][Payment\Entity::ID]
        ];

        $hash = $this->getHashOfArray($hashArray);

        $content[RequestFields::CHECKSUM] = $hash;

        $content = json_encode($content);

        $this->action = Action::PAYMENT_STATUS;

        $request = $this->getStandardRequestArray($content);

        $this->action = Action::VERIFY;

        $request['headers'] = $this->getRequestHeaders($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        return $request;
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }
    //----------------------------Verify helper methods end--------------------------------

    protected function parseGatewayResponse(\Requests_Response $response)
    {
        $content = $this->jsonToArray($response->body);

        return $this->parseResponseBody($content);
    }

    protected function parseResponseBody($content)
    {
        $responseFieldsArray = ResponseFields::getResponseFieldsArray();

        $gatewayResponseArray = explode('|', $content['response']);

        return array_combine($responseFieldsArray, $gatewayResponseArray);
    }

    protected function verifySecureHash(array $content)
    {
        $hashArray = $this->getResponseHashArray($content);

        $generated = $this->getHashOfArray($hashArray);

        $actual = $content[ResponseFields::CHECKSUM];

        $this->compareHashes($actual, $generated);
    }

    protected function getResponseHashArray($content)
    {
        return [
            $content[ResponseFields::STATUS_CODE],
            $content[ResponseFields::CLIENT_ID],
            $content[ResponseFields::MERCHANT_ID],
            $content[ResponseFields::CUSTOMER_ID],
            $content[ResponseFields::PAYMENT_ID],
            $content[ResponseFields::GATEWAY_PAYMENT_ID],
            $content[ResponseFields::AMOUNT],
            $content[ResponseFields::RESPONSE_CODE],
            $content[ResponseFields::RESPONSE_DESCRIPTION],
            $content[ResponseFields::DATE],
            $content[ResponseFields::CARD_NUMBER],
            $content[ResponseFields::CARD_TYPE],
            $content[ResponseFields::CARD_NETWORK]
        ];
    }

    protected function getRequestHeaders($content)
    {
        return [
            'Content-Type'   =>  'application/json',
        ];
    }

    public function getStringToHash($content, $glue = '|')
    {
        return parent::getStringToHash($content, $glue);
    }

    public function getHashOfString($hashString)
    {
        $secret = $this->getSecret();

        return hash_hmac(HashAlgo::SHA256, $hashString, $secret);
    }

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            s('here', $this->config['test_merchant_id']);
            return $this->config['test_merchant_id'];
        }

        return $this->input['terminal']['gateway_merchant_id'];
    }

    protected function getClientId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_client_id'];
        }

        return $this->input['terminal']['gateway_access_code'];
    }

    protected function getFormattedDateFromTimeStamp(
        $timestamp, $format = self::DATE_FORMAT)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format($format);
    }

    protected function getEpochTime(string $date, string $format)
    {
        return Carbon::createFromFormat($format, $date)->timestamp;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format(($amount / 100), 2);
    }

    public static function getFormattedRequestField(string $prefix, string $field, string $delimiter = '.')
    {
        return ($prefix . $delimiter . $field);
    }

    protected function getMappedAttributes($attributes)
    {
        $attr = [];

        $map = $this->map;

        foreach ($attributes as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey = $map[$key];
                $attr[$newKey] = $value;
            }
            else
            {
                $attr[$key] = $value;
            }
        }

        return $attr;
    }
}
