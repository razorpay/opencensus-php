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
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Status;
use RZP\Models\Currency\Currency;
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

    const JIOMONEY_UUID_FORMAT = '%04x%04x-%04x-%04x-%04x-%04x%04x%04x';

    const NUM_SECONDS_IN_DAY = 86400;

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
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Entity::PAYMENT_ID          => $input['payment'][Payment::ID],
            Entity::AMOUNT              => $input['payment'][Payment::AMOUNT],
            Entity::EMAIL               => $input['payment'][Payment::EMAIL],
            Entity::CONTACT             => $this->getFormattedContact($input['payment'][Payment::CONTACT]),
            Entity::RECEIVED            => false
        ];

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);

        // Redirects to JioMoney payment page
        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $input['gateway'] = $this->parseResponseBody($input['gateway']);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $input['gateway']);

        $this->verifySecureHash($input['gateway']);

        $this->assertPaymentId($input['payment']['id'], $input['gateway'][ResponseFields::PAYMENT_ID]);

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
        $timestamp = $this->getFormattedDateFromTimeStamp($payment[Payment::CREATED_AT]);

        $amount = $this->getFormattedAmount($payment[Payment::AMOUNT]);
        $contact = $this->getFormattedContact($payment[Payment::CONTACT]);

        $txn = RequestFields::TRANSACTION;
        $sub = RequestFields::SUBSCRIBER;

        $content = [
            RequestFields::MERCHANT_ID                                      => $this->getMerchantId(),
            RequestFields::CLIENT_ID                                        => $this->getClientId(),
            RequestFields::CHANNEL                                          => self::TXN_CHANNEL,
            RequestFields::CALLBACK_URL                                     => $callbackUrl,
            RequestFields::TOKEN                                            => '',
            RequestFields::getFormatted($txn, RequestFields::PAYMENT_ID)    => $payment[Payment::ID],
            RequestFields::getFormatted($txn, RequestFields::TIMESTAMP)     => $timestamp,
            RequestFields::getFormatted($txn, RequestFields::TXN_TYPE)      => strtoupper(Action::PURCHASE),
            RequestFields::getFormatted($txn, RequestFields::AMOUNT)        => $amount,
            RequestFields::getFormatted($txn, RequestFields::CURRENCY)      => $payment[Payment::CURRENCY],
            RequestFields::getFormatted($sub, RequestFields::CUSTOMER_NAME) => $payment[Payment::EMAIL],
            RequestFields::getFormatted($sub, RequestFields::EMAIL)         => $payment[Payment::EMAIL],
            RequestFields::getFormatted($sub, RequestFields::CONTACT)       => $contact
        ];

        $hashArray = $this->getPurchaseRequestArrayToHash($content);

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($hashArray);

        return $content;
    }

    protected function getPurchaseRequestArrayToHash($content)
    {
        return [
            $content[RequestFields::CLIENT_ID],
            $content[RequestFields::getFormatted(RequestFields::TRANSACTION, RequestFields::AMOUNT)],
            $content[RequestFields::getFormatted(RequestFields::TRANSACTION, RequestFields::PAYMENT_ID)],
            $content[RequestFields::CHANNEL],
            $content[RequestFields::MERCHANT_ID],
            $content[RequestFields::TOKEN],
            $content[RequestFields::CALLBACK_URL],
            $content[RequestFields::getFormatted(RequestFields::TRANSACTION, RequestFields::TIMESTAMP)],
            $content[RequestFields::getFormatted(RequestFields::TRANSACTION, RequestFields::TXN_TYPE)]
        ];
    }

    //-------------------------------Authorize helper methods end---------------------------------

    //-------------------------------Callback helper methods begin--------------------------------

    protected function callbackAuthSuccessFlow(array $input)
    {
        $content = $input['gateway'];

        $date = Carbon::createFromFormat(self::DATE_FORMAT, $content[ResponseFields::DATE], 'Asia/Kolkata')->timestamp;

        $contentToSave = [
            Entity::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            Entity::RESPONSE_CODE        => $content[ResponseFields::RESPONSE_CODE],
            Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::RESPONSE_DESCRIPTION],
            Entity::GATEWAY_PAYMENT_ID   => $content[ResponseFields::GATEWAY_PAYMENT_ID],
            Entity::DATE                 => $date,
            Entity::RECEIVED             => true
        ];

        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);
    }

    protected function callbackAuthFailureFlow(array $input)
    {
        $content = $input['gateway'];

        $contentToSave = [
            Entity::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            Entity::RESPONSE_CODE        => $content[ResponseFields::RESPONSE_CODE],
            Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::RESPONSE_DESCRIPTION],
            Entity::GATEWAY_PAYMENT_ID   => $content[ResponseFields::GATEWAY_PAYMENT_ID],
            Entity::DATE                 => $content[ResponseFields::DATE]
        ];

        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);

        $this->handleCallbackFailure($content);
    }

    protected function handleCallbackFailure(array $content)
    {
        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
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
                RequestFields::CURRENCY   => $input['payment'][Payment::CURRENCY],
            ],
            RequestFields::REFUND_INFO  => $refundInfo,
        ];

        $content[RequestFields::CHECKSUM] = $this->getRefundRequestHash($content);

        return $content;
    }

    protected function generateRefundInfo($wallet)
    {
        $gatewayPaymentDate = $wallet['date'] ?? $wallet['created_at'];

        $refundinfo = [
            $wallet['gateway_payment_id'],
            $this->getFormattedDateFromTimeStamp($gatewayPaymentDate),
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
            Entity::CONTACT              => $this->getFormattedContact($input['payment']['contact']),
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

        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $paymentCreatedAt = $input['payment'][Payment::CREATED_AT];

        // If time elapsed from payment creation time is less than 24 hours we
        // call STATUSQUERYAPI to verify else we verify using CHECKPAYMENTSATUS API
        if (($now - $paymentCreatedAt) <= self::NUM_SECONDS_IN_DAY)
        {
            list($content, $response) = $this->verifyUsingStatusQuery($input);
        }
        else
        {
            list($content, $response) = $this->verifyUsingCheckPaymentStatus($input);
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
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

    protected function verifyUsingStatusQuery(array $input)
    {
        $statusQueryRequest = $this->getStatusQueryRequestArray($input);

        $statusQueryResponse = $this->sendGatewayRequest($statusQueryRequest);

        $content = $this->jsonToArray($statusQueryResponse->body);

        $response = $statusQueryResponse;

        if ($this->validStatusQueryResponse($content) === false)
        {
            return $this->verifyUsingCheckPaymentStatus($input);
        }

        return [$content, $response];
    }

    protected function verifyUsingCheckPaymentStatus(array $input)
    {
        $checkPaymentStatusRequest = $this->getCheckPaymentStatusRequest($input);

        $checkPaymentStatusResponse = $this->sendGatewayRequest($checkPaymentStatusRequest);

        $response = $checkPaymentStatusResponse;

        $content = $this->jsonToArray($response->body);

        return [$content, $response];
    }

    protected function verifyPayment($verify)
    {
        $input = $verify->input;
        $content = $verify->verifyResponseContent;
        $gatewayPayment = $verify->payment;

        $verify->status = VerifyResult::STATUS_MATCH;

        $verify->apiSuccess = true;

        // apiSuccess if false if the payment entity is in failed or created state
        if (($input['payment']['status'] === Status::FAILED) or
            ($input['payment']['status'] === Status::CREATED))
        {
            $verify->apiSuccess = false;
        }

        $verify->gatewaySuccess = false;

        if ($this->checkPaymentStatusResponseFailed($content) === false)
        {
            // Temporarily hardcoding this payment id here to manually pass verify
            // and change payment status to authorized, as this payment was successfully
            // processed by Jiomoney but marked as failed by timeout cron as callback was received late
            if ($input['payment']['id'] === '7LaaHWTPMl9PQL')
            {
                $verify->gatewaySuccess = true;
            }
            else
            {
                $verify->gatewaySuccess = ($this->getGatewayTxnStatus($content) === StatusCode::API_SUCCESS);
            }
        }

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->content = $this->getVerifyWalletAttributes($verify);

        $gatewayPayment->fill($verify->content);

        $gatewayPayment->saveOrFail();
    }

    protected function getVerifyWalletAttributes($verify)
    {
        $payment = $this->input['payment'];

        $content = $verify->verifyResponseContent;

        $contentToSave = [
            Entity::RECEIVED             => true,
            Entity::STATUS_CODE          => StatusCode::SUCCESS,
            Entity::RESPONSE_CODE        => StatusCode::API_SUCCESS,
            Entity::RESPONSE_DESCRIPTION => 'APPROVED',
            Entity::DATE                 => $this->getGatewayPaymentDate($content, $payment),
            Entity::GATEWAY_PAYMENT_ID   => $this->getGatewayPaymentId($content, $payment)
        ];

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
            $txnFound = ($content[StatusQueryResponseFields::RESPONSE_HEADER]
                                 [StatusQueryResponseFields::API_STATUS] === '1');

            if ($txnFound === true)
            {
                $this->statusQueryValid = true;
            }

            return $txnFound;
        }

        return false;
    }

    protected function checkPaymentStatusResponseFailed(array $content)
    {
        if ($this->statusQueryValid === false)
        {
            $response = $content[ResponseFields::RESPONSE][ResponseFields::RESPONSE_HEADER];

            if (isset($response[ResponseFields::STATUS]) === true)
            {
                return ($response[ResponseFields::STATUS] !== StatusCode::API_SUCCESS);
            }
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

    /**
     * Fetches the gateway payment datte from the verify response
     * Jiomoney only returns the timestamp in CHECKPAYMENTSTATUS response and
     * not in STATUSQUERY response. So if verify response came through STATUSQUERY
     * API we return the payment created at timestamp, else we return null
     *
     * @param  array  $content verify response content
     * @param  array  $payment payment array
     * @return string          gateway payment timestamp
     */
    public function getGatewayPaymentDate(array $content, array $payment)
    {
        // Temporarily hardcoding the gateway payment timestamp for this payment id
        // as verify is no longer returning valid response for this
        if ($payment['id'] === '7LaaHWTPMl9PQL')
        {
            return '1488009243';
        }

        if ($this->statusQueryValid === false)
        {
            $date = $content[ResponseFields::RESPONSE][ResponseFields::CHECKPAYMENTSTATUS]
                    [ResponseFields::TXN_TIME_STAMP];

            return Carbon::createFromFormat(self::DATE_FORMAT, $date, 'Asia/Kolkata')
                        ->timestamp;
        }

        return null;
    }

    protected function getGatewayPaymentId(array $content, array $payment)
    {
        // Temporarily hardcoding the gateway payment id here for this specific payment to
        // manually pass verify flow
        if ($payment['id'] === '7LaaHWTPMl9PQL')
        {
            return '301005129694';
        }

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
            RequestFields::REQUEST_ID    => gen_uuid(self::JIOMONEY_UUID_FORMAT),
            RequestFields::STARTDATETIME => 'NA',
            RequestFields::ENDDATETIME   => 'NA',
            RequestFields::MERCHANT_ID   => $this->getMerchantId(),
            RequestFields::PAYMENT_ID    => $input['payment']['id']
        ];

        $hashString = $this->getStringToHash($content, '~');

        $content[RequestFields::CHECKSUM] = $this->getHashOfString($hashString);

        $content = implode('~', $content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = $this->getRequestHeaders($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        return $request;
    }

    protected function getStatusQueryRequestArray(array $input)
    {
        $this->action = Action::PAYMENT_STATUS;

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
            $input['payment'][Payment::ID]
        ];

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($hashArray);

        $content = json_encode($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = $this->getRequestHeaders($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $this->action = Action::VERIFY;

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
            'Content-Type' => 'application/json',
        ];
    }

    public function getStringToHash($content, $glue = '|')
    {
        return parent::getStringToHash($content, $glue);
    }

    public function getHashOfString($string)
    {
        $secret = $this->getSecret();

        return hash_hmac(HashAlgo::SHA256, $string, $secret);
    }

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->terminal['gateway_merchant_id'];
    }

    protected function getClientId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_client_id'];
        }

        return $this->terminal['gateway_access_code'];
    }

    public function getSecret()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestSecret();
        }

        return $this->terminal['gateway_terminal_password'];
    }

    protected function getFormattedDateFromTimeStamp(
        $timestamp, $format = self::DATE_FORMAT)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format($format);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format(($amount / 100), 2);
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
