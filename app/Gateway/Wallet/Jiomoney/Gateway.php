<?php

namespace RZP\Gateway\Wallet\Jiomoney;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Error;
use RZP\Exception;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Wallet\Base;
use \WpOrg\Requests\Response;
use RZP\Models\Payment\Status;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Base\Entity;
use RZP\Gateway\Base as GatewayBase;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment as PaymentModel;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Gateway as PaymentGateway;

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

    // Time period till we use STATUSQUERY API as fallback for verify
    // Value is the number of seconds in 4 hours
    const STATUSQUERY_API_FALLBACK_THRESHOLD_PERIOD = 14400;

    const NUM_SECONDS_IN_TWO_DAYS = 172800;

    const TRANSACTION_NOT_FOUND = "TRANSACTION_NOT_FOUND";

    protected $gateway = 'wallet_jiomoney';

    protected $sortRequestContent = false;

    protected $map = [
        RequestFields::MERCHANT_ID => Entity::GATEWAY_MERCHANT_ID,
    ];

    // Flag to indicate if payment was verified using STATUSQUERY API
    protected $verifiedUsingStatusQuery = false;

    // Flag to indicate if  payment was verified using CHECKPAYMENTSTATUS API
    protected $verifiedUsingCheckPaymentStatus = false;

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

        $content = $input['gateway'];

        $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_PAYMENT_CALLBACK);

        $this->verifySecureHash($input['gateway']);

        if (($input['gateway'][ResponseFields::STATUS_CODE] !== StatusCode::SUCCESS) and
            ($input['gateway'][ResponseFields::RESPONSE_CODE] !== ResponseCode::SUCCESS))
        {
            return $this->callbackAuthFailureFlow($input);
        }

        $this->assertGatewayResponse($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $this->verifyCallback($gatewayPayment, $input);

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

        $this->traceGatewayPaymentRequest($request, $input,TraceCode::GATEWAY_REFUND_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $content = $this->parseGatewayResponse($response);

        $this->traceGatewayPaymentResponse($content, $input,TraceCode::GATEWAY_REFUND_RESPONSE);

        $this->verifySecureHash($content);

        $this->createWalletRefundEntity($content, $input);

        if ($content[ResponseFields::STATUS_CODE] !== StatusCode::SUCCESS)
        {
            $this->handleRefundFailure($content);
        }

        return [
            PaymentModel\Gateway::GATEWAY_RESPONSE => json_encode($content),
            PaymentModel\Gateway::GATEWAY_KEYS     => $this->getGatewayData($content)
        ];
    }

    public function alreadyRefunded(array $input)
    {
        $paymentId = $input['payment_id'];
        $refundAmount = $input['refund_amount'];
        $refundId = $input['refund_id'];

        $gatewayRefundEntities = $this->repo->findSuccessfulRefundByRefundId($refundId, Wallet::JIOMONEY);

        if ($gatewayRefundEntities->count() === 0)
        {
            return false;
        }

        $gatewayRefundEntity = $gatewayRefundEntities->first();

        $gatewayRefundEntityPaymentId = $gatewayRefundEntity->getPaymentId();
        $gatewayRefundEntityRefundAmount = $gatewayRefundEntity->getAmount();
        $gatewayRefundEntityStatusCode = $gatewayRefundEntity->getStatusCode();

        $this->trace->info(
            TraceCode::GATEWAY_ALREADY_REFUNDED_INPUT,
            [
                'input'                 => $input,
                'refund_payment_id'     => $gatewayRefundEntityPaymentId,
                'gateway_refund_amount' => $gatewayRefundEntityRefundAmount,
                'status_code'           => $gatewayRefundEntityStatusCode,
            ]);

        $gatewayRefundSuccess = ($gatewayRefundEntityStatusCode === StatusCode::SUCCESS);

        if (($gatewayRefundEntityPaymentId !== $paymentId) or
            ($gatewayRefundEntityRefundAmount !== $refundAmount) or
            ($gatewayRefundSuccess === false))
        {
            return false;
        }

        return true;
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                                                            $input['payment']['id'],
                                                            Action::AUTHORIZE);

        // Return true if already authorized on gateway
        if (($gatewayPayment->getGatewayPaymentId() !== null) and
            ($gatewayPayment->getStatusCode() === StatusCode::SUCCESS))
        {
            return true;
        }

        if (empty($input['gateway']['gateway_payment_id']) === true)
        {
            throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_AUTH_DATA_MISSING,
                        null,
                        $input);
        }

        $contentToSave = [
            Entity::GATEWAY_PAYMENT_ID => $input['gateway']['gateway_payment_id'],
            Entity::STATUS_CODE        => StatusCode::SUCCESS,
            Entity::RESPONSE_CODE      => ResponseCode::SUCCESS
        ];

        $gatewayPayment->fill($contentToSave);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $request = $this->getVerifyRefundRequest($input);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
        [
            'response'   => $response,
            'refund_id'  => $input['refund']['id'],
            'payment_id' => $input['payment']['id'],
            'gateway'    => $this->gateway,
        ]);

        $content = $this->jsonToArray($response->body);

        return $this->verifyRefundUsingGatewayResponse($content, $input);

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

        $contentToSave = [
            Entity::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            Entity::RESPONSE_CODE        => $content[ResponseFields::RESPONSE_CODE],
            Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::RESPONSE_DESCRIPTION],
            Entity::GATEWAY_PAYMENT_ID   => $content[ResponseFields::GATEWAY_PAYMENT_ID],
            Entity::DATE                 => $content[ResponseFields::DATE],
            Entity::RECEIVED             => true
        ];

        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);
    }

    protected function verifyCallback(Base\Entity $gatewayPayment, array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $verify->gatewaySuccess = false;
        $verify->amountMismatch = false;

        $content = $verify->verifyResponseContent;

        if($this->verifiedUsingStatusQuery)
        {

            if ((isset($content[StatusQueryRequestFields::PAYLOAD_DATA]['txn_status']) === true) and
                ($content[StatusQueryRequestFields::PAYLOAD_DATA]['txn_status'] === ResponseCode::SUCCESS))
            {
                $verify->gatewaySuccess = true;
            }

        }
        else {

            $data = $content[ResponseFields::RESPONSE];
            if ((isset($data)) and
                (isset($data[ResponseFields::CHECKPAYMENTSTATUS][ResponseFields::TXN_STATUS]) === true) and
                ($data[ResponseFields::CHECKPAYMENTSTATUS][ResponseFields::TXN_STATUS] === ResponseCode::SUCCESS))
            {
                $verify->gatewaySuccess = true;
            }

        }


        if ($verify->gatewaySuccess !== true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                'FAILED',
                '',
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }

        if($this->verifiedUsingStatusQuery){

            if ((isset($content[StatusQueryRequestFields::PAYLOAD_DATA]['txn_amount']) === false) ||
                ($this->getFormattedAmount($input['payment']['amount']) !== $content[StatusQueryRequestFields::PAYLOAD_DATA]['txn_amount']))
            {
                $verify->amountMismatch = true;
            }

        }
        else
        {

            $data = $content[ResponseFields::RESPONSE];
            if ((isset($data[ResponseFields::CHECKPAYMENTSTATUS][ResponseFields::TXN_AMOUNT]) === false) ||
                ($input['payment']['amount'] !== $data[ResponseFields::CHECKPAYMENTSTATUS][ResponseFields::TXN_AMOUNT]))
            {
                $verify->amountMismatch = true;
            }

        }

        if ($verify->amountMismatch === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_AMOUNT_TAMPERED,
                'FAILED',
                '',
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $verify->gateway,
                ]);
        }
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

    /**
     * Performs required assertion on response received from gateway

     * @param  array  $input input containing payment data and gateway response
     */
    protected function assertGatewayResponse(array $input)
    {
        $this->assertPaymentId(
                $input['payment']['id'],
                $input['gateway'][ResponseFields::PAYMENT_ID]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount = number_format($input['gateway'][ResponseFields::AMOUNT], 2, '.', '');
        $this->assertAmount($expectedAmount, $actualAmount);
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

        $timestamp = Carbon::now(Timezone::IST)->format(self::DATE_FORMAT);

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
        $gatewayPaymentDate = $wallet['date'] ?? $this->getFormattedDateFromTimeStamp($wallet['created_at']);

        $refundinfo = [
            $wallet['gateway_payment_id'],
            $gatewayPaymentDate,
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
            $content[ResponseFields::RESPONSE_DESCRIPTION],
            [
                PaymentGateway::GATEWAY_RESPONSE  => json_encode($content),
                PaymentGateway::GATEWAY_KEYS      => $this->getGatewayData($content)
            ]
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
     *     This might realistically be about 10 mins,
     *
     *     CHECKPAYMENTSTATUS API also returns the gateway transaction timestamp
     *     which is needed during refund. This is not returned by STATUSQUERY API.
     *     So we always first make an attempt to verify using CHECKPAYMENTSTATUS API.
     *     If it fails, we fallback to STATUSQUERY API.
     */
    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        list($content, $response) = $this->verifyUsingCheckPaymentStatus($input);

        if ($this->canUseStatusQueryForVerify($verify) === true)
        {
            list($content, $response) = $this->verifyUsingStatusQuery($input);
        }

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

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'content'     => $content,
                'api_type'    => ApiName::STATUSQUERY,
                'payment_id'  => $input['payment']['id'],
                'gateway'     => $this->gateway,
                'terminal_id' => $input['terminal']['id'],
            ]);

        if ($this->validStatusQueryResponse($content) === true)
        {
            $this->verifiedUsingStatusQuery = true;
        }

        return [$content, $response];
    }

    protected function verifyUsingCheckPaymentStatus(array $input)
    {
        $checkPaymentStatusRequest = $this->getCheckPaymentStatusRequest($input);

        $checkPaymentStatusResponse = $this->sendGatewayRequest($checkPaymentStatusRequest);

        $response = $checkPaymentStatusResponse;

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'content'     => $content,
                'api_type'    => ApiName::CHECKPAYMENTSTATUS,
                'payment_id'  => $input['payment']['id'],
                'gateway'     => $this->gateway,
                'terminal_id' => $input['terminal']['id'],
            ]);

        $data = $content[ResponseFields::RESPONSE];

        if ((isset($data[ResponseFields::CHECKPAYMENTSTATUS]) === true) and
            ($data[ResponseFields::RESPONSE_HEADER][ResponseFields::STATUS] === ResponseCode::SUCCESS))
        {
            $this->verifiedUsingCheckPaymentStatus = true;
        }

        return [$content, $response];
    }

    protected function canUseStatusQueryForVerify($verify)
    {
        $input = $verify->input;

        $timeSincePaymentCreation = $this->getTimeSincePaymentCreation($input);

        // Jiomoney STATUSQUERY API returns valid response only till 3 hours post payment
        // So we don't use it as fallback after that interval
        return (($this->verifiedUsingCheckPaymentStatus === false) and
                ($timeSincePaymentCreation <= self::STATUSQUERY_API_FALLBACK_THRESHOLD_PERIOD));
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

        if ($this->validatePaymentVerificationSuccess($content) === true)
        {
            $verify->gatewaySuccess = true;
        }

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $timeSincePaymentCreation = $this->getTimeSincePaymentCreation($input);

        // Jiomoney verify API's dont return a valid response after 2 days. So
        // if for a payment we get verify STATUS_MISMATCH after 2 days we set it to
        // STATUS_MATCH considering this behaviour
        if (($verify->status === VerifyResult::STATUS_MISMATCH) and
                $timeSincePaymentCreation >= self::NUM_SECONDS_IN_TWO_DAYS)
        {
            $verify->status = VerifyResult::STATUS_MATCH;

            $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,[
                'msg'         => 'Jiomoney payment verification after 2 days',
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
                'gateway'     => $this->gateway,
            ]);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->verifyResponseContent = $this->getVerifyWalletAttributes($verify);
    }

    protected function getVerifyWalletAttributes($verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $gatewayResponseCode = $this->getGatewayResponseCode($content);

        $gatewayStatusCode = $this->getGatewayStatusCodeFromResponseCode($gatewayResponseCode);

        $contentToSave = [
            Entity::RESPONSE_CODE      => $gatewayResponseCode,
            Entity::DATE               => $this->getGatewayPaymentDate($content),
            Entity::GATEWAY_PAYMENT_ID => $this->getGatewayPaymentId($content)
        ];

        if ($gatewayPayment->getStatusCode() === null)
        {
            $contentToSave[Entity::STATUS_CODE] = $gatewayStatusCode;
        }

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

            return $txnFound;
        }

        return false;
    }

    protected function validatePaymentVerificationSuccess(array $content)
    {
        $responseCode = $this->getGatewayResponseCode($content);

        return ($responseCode === ResponseCode::SUCCESS);
    }

    protected function getGatewayResponseCode(array $content)
    {
        if ($this->verifiedUsingStatusQuery === true)
        {
            return $content[StatusQueryResponseFields::PAYLOAD_DATA][StatusQueryResponseFields::TXN_STATUS];
        }
        else if ($this->verifiedUsingCheckPaymentStatus === true)
        {
            $gatewayData = $this->getGatewayDataFromVerifyResponse($content);

            return $gatewayData[ResponseFields::TXN_STATUS];
        }

        return null;
    }

    public function getGatewayStatusCodeFromResponseCode($responseCode)
    {
        if ($responseCode !== null)
        {
            return ($responseCode === ResponseCode::SUCCESS) ? StatusCode::SUCCESS : StatusCode::INTERNAL_ERROR;
        }

        return null;
    }

    /**
     * Fetches the gateway payment date from the verify response
     * Jiomoney only returns the timestamp in CHECKPAYMENTSTATUS response and
     * not in STATUSQUERY response. So if verify response came through STATUSQUERY
     * API we return the payment created at timestamp, else we return null
     *
     * @param  array  $content verify response content
     * @return string          gateway payment timestamp
     */
    public function getGatewayPaymentDate(array $content)
    {
        if ($this->verifiedUsingCheckPaymentStatus === true)
        {
            $gatewayData = $this->getGatewayDataFromVerifyResponse($content);

            return $gatewayData[ResponseFields::TXN_TIME_STAMP];
        }

        return null;
    }

    protected function getGatewayPaymentId(array $content)
    {
        if ($this->verifiedUsingStatusQuery === true)
        {
            // In some cases like when the  payment status is INITIATED, Jiomoney
            // doesn't return a gateway payment id in the STATUSQUERY API response
            return $content[StatusQueryResponseFields::PAYLOAD_DATA]
                        [StatusQueryResponseFields::JM_TRAN_REF_NO] ?? null;
        }
        else if ($this->verifiedUsingCheckPaymentStatus === true)
        {
            $gatewayData = $this->getGatewayDataFromVerifyResponse($content);

            return $gatewayData[ResponseFields::JM_TRAN_REF_NO];
        }

        return null;
    }

    /**
     * We sometimes get an array of nested JSON objects from the Jiomoney verify API
     * This method returns the first object from the array
     * @param  array  $content verify response content
     * @return array
     */
    protected function getGatewayDataFromVerifyResponse(array $content)
    {
        $checkPaymentStatusData = $content[ResponseFields::RESPONSE][ResponseFields::CHECKPAYMENTSTATUS];

        if (is_associative_array($checkPaymentStatusData) === false)
        {
            $checkPaymentStatusData = array_shift($checkPaymentStatusData);
        }

        return $checkPaymentStatusData;
    }

    protected function getCheckPaymentStatusRequest(array $input)
    {
        $request = $this->getVerifyTransactionRequest(ApiName::CHECKPAYMENTSTATUS, $input['payment']['id']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'     => $request,
                'api_type'    => ApiName::CHECKPAYMENTSTATUS,
                'gateway'     => $this->gateway,
                'terminal_id' => $input['terminal']['id'],
                'payment_id'  => $input['payment']['id'],
            ]);

        return $request;
    }

    protected function getStatusQueryRequestArray(array $input)
    {
        $this->action = Action::PAYMENT_STATUS;

        $this->domainType = null;

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
            [
                'request'     => $request,
                'api_type'    => ApiName::STATUSQUERY,
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);

        $this->action = Action::VERIFY;

        return $request;
    }

    protected function getVerifyRefundRequest(array $input)
    {
        $wallet = $this->repo->findByPaymentIdAndAction($input['payment']['id'], Action::AUTHORIZE);

        $request = $this->getVerifyTransactionRequest(ApiName::GETREQUESTSTATUS, $wallet->getGatewayPaymentId());

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST,
            [
                'request'    => $request,
                'api_type'   => ApiName::GETREQUESTSTATUS,
                'refund_id'  => $input['refund']['id'],
                'payment_id' => $input['payment']['id'],
                'gateway'    => $this->gateway,
            ]);

        return $request;
    }

    protected function getVerifyTransactionRequest(string $apiName, string $txnId)
    {
        $this->domainType = $this->mode . '_' . $this->action;

        $content = [
            RequestFields::APINAME       => $apiName,
            RequestFields::MODE          => self::JSON_MODE,
            RequestFields::REQUEST_ID    => gen_uuid(self::JIOMONEY_UUID_FORMAT),
            RequestFields::STARTDATETIME => 'NA',
            RequestFields::ENDDATETIME   => 'NA',
            RequestFields::MERCHANT_ID   => $this->getMerchantId(),
            RequestFields::PAYMENT_ID    => $txnId,
        ];

        $hashString = $this->getStringToHash($content, '~');

        $content[RequestFields::CHECKSUM] = $this->getHashOfString($hashString);

        $content = implode('~', $content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = $this->getRequestHeaders($content);

        return $request;
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }

    protected function verifyRefundUsingGatewayResponse(array $content, array $input)
    {
        $scroogeResponse = new GatewayBase\ScroogeResponse();

        $scroogeResponse->setGatewayVerifyResponse($content)
                        ->setGatewayKeys($this->getGatewayData($content));

        if (isset($content[ResponseFields::RESPONSE][ResponseFields::GETREQUESTSTATUS]) === true)
        {
            $gatewayRefundData = $content[ResponseFields::RESPONSE][ResponseFields::GETREQUESTSTATUS];

            if ($this->isSuccessFullyRefundedOnGateway($gatewayRefundData, $input) === true)
            {
                return $scroogeResponse->setSuccess(true)
                                       ->toArray();
            }

            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                   ->toArray();
        }
        else if (isset($content[ResponseFields::RESPONSE][ResponseFields::RESPONSE_HEADER]) === true)
        {
            $responseHeader = $content[ResponseFields::RESPONSE][ResponseFields::RESPONSE_HEADER];

            if ($responseHeader[ResponseFields::API_MSG] === self::TRANSACTION_NOT_FOUND)
            {
                return $scroogeResponse->setSuccess(false)
                                       ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                       ->toArray();
            }
        }

        throw new Exception\LogicException(
            'Unrecognized verify refund gateway response',
            ErrorCode::GATEWAY_ERROR_UNEXPECTED_STATUS,
            [
                PaymentModel\Gateway::GATEWAY_VERIFY_RESPONSE  => json_encode($content),
                PaymentModel\Gateway::GATEWAY_KEYS      => [
                    'payment_id' => $input['refund']['payment_id'],
                    'refund_id'  => $input['refund']['id'],
                    'content'    => json_encode($content)
                ]
            ]);
    }

    protected function isSuccessFullyRefundedOnGateway(array $gatewayRefundData, array $input): bool
    {
        if (is_sequential_array($gatewayRefundData) === true)
        {
            foreach ($gatewayRefundData as $data)
            {
                if ($this->isSuccessFulRefund($data, $input) === true)
                {
                    return true;
                }
            }

            return false;
        }

        return ($this->isSuccessFulRefund($gatewayRefundData, $input) === true);
    }

    protected function isSuccessfulRefund(array $gatewayRefundData, array $input): bool
    {
        return (($gatewayRefundData['TRAN_REF_NO'] === $input['refund']['transaction_id']) and
                ($gatewayRefundData[ResponseFields::TXN_STATUS] === ResponseCode::SUCCESS) and
                ($input['refund']['amount'] === intval($gatewayRefundData[ResponseFields::REFUND_AMOUNT])));
    }

    //----------------------------Verify helper methods end--------------------------------

    protected function parseGatewayResponse(Response $response)
    {
        $content = $this->jsonToArray($response->body);

        return $this->parseResponseBody($content);
    }

    protected function parseResponseBody($content)
    {
        $responseFieldsArray = ResponseFields::getResponseFieldsArray();

        $gatewayResponseArray = explode('|', $content['response']);

        // In certain failure scenarions, such as request validation, Jiomoney doesn't
        // send a checksum in the response. To handle such cases, we have this condition
        // which conditionally removes the checksum attribute from the expected
        // gateway response attributes
        if (count($responseFieldsArray) > count($gatewayResponseArray))
        {
            array_pop($responseFieldsArray);
        }

        return array_combine($responseFieldsArray, $gatewayResponseArray);
    }

    protected function verifySecureHash(array $content)
    {
        $hashArray = $this->getResponseHashArray($content);

        $generated = $this->getHashOfArray($hashArray);

        $actual = $content[ResponseFields::CHECKSUM] ?? '';

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
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format($format);
    }

    protected function getFormattedAmount($amount)
    {
        // The amount should be in the format like 100.00, or 1500.00
        return number_format(($amount / 100), 2, '.', '');
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

    protected function getTimeSincePaymentCreation(array $input)
    {
        $now = Carbon::now()->getTimestamp();

        return ($now - $input['payment']['created_at']);
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        $this->trace->info($traceCode,
            [
                'request'     => $request,
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);
    }

    protected function traceGatewayPaymentResponse(
        $response,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_RESPONSE)
    {
        $this->trace->info($traceCode,
            [
                'response'    => $response,
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);
    }

    protected function getGatewayData(array $refundFields = [])
    {
        if (empty($refundFields) === false)
        {
            return [
                ResponseFields::DATE                  => $refundFields[ResponseFields::DATE] ?? null,
                ResponseFields::CLIENT_ID             => $refundFields[ResponseFields::CLIENT_ID] ?? null,
                ResponseFields::STATUS_CODE           => $refundFields[ResponseFields::STATUS_CODE] ?? null,
                ResponseFields::MERCHANT_ID           => $refundFields[ResponseFields::MERCHANT_ID] ?? null,
                ResponseFields::CUSTOMER_ID           => $refundFields[ResponseFields::CUSTOMER_ID] ?? null,
                ResponseFields::RESPONSE_CODE         => $refundFields[ResponseFields::RESPONSE_CODE] ?? null,
                ResponseFields::GATEWAY_PAYMENT_ID    => $refundFields[ResponseFields::GATEWAY_PAYMENT_ID] ?? null,
                ResponseFields::RESPONSE_DESCRIPTION  => $refundFields[ResponseFields::RESPONSE_DESCRIPTION] ?? null
            ];
        }
        return [];
    }
}
