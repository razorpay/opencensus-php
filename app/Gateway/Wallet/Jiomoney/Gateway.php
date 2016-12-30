<?php

namespace RZP\Gateway\Wallet\Jiomoney;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Terminal;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Base\Entity as WalletEntity;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Models\Payment\Currency;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $canRunOtpFlow = false;

    protected $topup = false;

    const FORMAT = 'YmdHis';

    const DEFAULT_TXN_CHANNEL = 'WEB';

    const DEFAULT_CUSTOMER_NAME = 'Dummy Name';

    const JSON_MODE = '2';

    const STATUS_QUERY_API_VERSION = '1.0';

    protected $gateway = 'wallet_jiomoney';

    protected $sortRequestContent = false;

    protected $map = [
        RequestFields::MERCHANT_ID           => WalletEntity::GATEWAY_MERCHANT_ID,
    ];

    /**
     * Returns JioMoney request content to be redirewcted to from checkout
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
            RequestFields::MERCHANT_ID  => $this->getMerchantId(),
            RequestFields::PAYMENT_ID   => $input['payment']['id'],
            RequestFields::AMOUNT       => $input['payment']['amount'],
            WalletEntity::EMAIL         => $input['payment']['email'],
            WalletEntity::CONTACT       => $input['payment']['contact'],
            WalletEntity::RECEIVED      => false
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

    //-------------------------------Authorize helper methods begin-------------------------------
    protected function getPurchaseRequestArray(array $input)
    {
        $payment = $input['payment'];

        $content = $this->getPurchaseRequestContent($payment, $input['callbackUrl']);

        return $this->getStandardRequestArray($content);
    }

    protected function getPurchaseRequestContent(array $payment, string $callbackUrl)
    {
        $timestamp = $this->getFormattedTimeStamp($payment[Payment::CREATED_AT], self::FORMAT);

        $amount = $this->getFormattedAmount($payment[Payment::AMOUNT]);

        $content = [
            RequestFields::MERCHANT_ID                                     => $this->getMerchantId(),
            RequestFields::CLIENT_ID                                       => $this->getClientId(),
            RequestFields::CHANNEL                                         => self::DEFAULT_TXN_CHANNEL,
            RequestFields::CALLBACK_URL                                    => $callbackUrl,
            RequestFields::TOKEN                                           => '',
            RequestFields::TRANSACTION . '.' . RequestFields::PAYMENT_ID   => $payment[Payment::ID],
            RequestFields::TRANSACTION . '.' . RequestFields::TIMESTAMP    => $timestamp,
            RequestFields::TRANSACTION . '.' . RequestFields::TXN_TYPE     => strtoupper(Action::PURCHASE),
            RequestFields::TRANSACTION . '.' . RequestFields::AMOUNT       => $amount,
            RequestFields::TRANSACTION . '.' . RequestFields::CURRENCY     => Currency::INR,
            RequestFields::SUBSCRIBER . '.' . RequestFields::CUSTOMER_NAME => $payment[Payment::EMAIL],
            RequestFields::SUBSCRIBER . '.' . RequestFields::EMAIL         => $payment[Payment::EMAIL],
            RequestFields::SUBSCRIBER . '.' . RequestFields::CONTACT       => $payment[Payment::CONTACT]
        ];

        $hashArray = $this->getPurchaseRequestArrayToHash($content);

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($hashArray);

        return $content;
    }

    protected function getPurchaseRequestArrayToHash($content)
    {
        return [
            $content[RequestFields::CLIENT_ID],
            $content[RequestFields::TRANSACTION . '.' . RequestFields::AMOUNT],
            $content[RequestFields::TRANSACTION. '.' . RequestFields::PAYMENT_ID],
            $content[RequestFields::CHANNEL],
            $content[RequestFields::MERCHANT_ID],
            $content[RequestFields::TOKEN],
            $content[RequestFields::CALLBACK_URL],
            $content[RequestFields::TRANSACTION . '.' .RequestFields::TIMESTAMP],
            $content[RequestFields::TRANSACTION . '.' . RequestFields::TXN_TYPE]
        ];
    }

    //-------------------------------Authorize helper methods end---------------------------------

    //-------------------------------Callback helper methods begin--------------------------------

    protected function callbackAuthSuccessFlow(array $input)
    {
        $content = $input['gateway'];

        $date = $this->getEpochTime($content[ResponseFields::DATE], self::FORMAT);

        $contentToSave = [
            ResponseFields::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            ResponseFields::RESPONSE_CODE        => $content[ResponseFields::RESPONSE_CODE],
            ResponseFields::RESPONSE_DESCRIPTION => $content[ResponseFields::RESPONSE_DESCRIPTION],
            ResponseFields::GATEWAY_PAYMENT_ID   => $content[ResponseFields::GATEWAY_PAYMENT_ID],
            ResponseFields::DATE                 => $date,
            WalletEntity::RECEIVED               => true
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

        $wallet = $this->repo->findByPaymentIdAndAction($input['payment']['id'],
                                                                Action::AUTHORIZE);

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

        $timestamp = Carbon::now('Asia/Kolkata')->format(self::FORMAT);

        $content = [
            RequestFields::CLIENT_ID    => $this->getClientId(),
            RequestFields::MERCHANT_ID  => $this->getMerchantId(),
            RequestFields::CHANNEL      => self::DEFAULT_TXN_CHANNEL,
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
            $this->getFormattedTimeStamp($wallet['date'], self::FORMAT),
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
            WalletEntity::PAYMENT_ID           => $input['payment']['id'],
            WalletEntity::GATEWAY_MERCHANT_ID  => $this->getMerchantId(),
            WalletEntity::ACTION               => $this->action,
            WalletEntity::AMOUNT               => $input['refund']['amount'],
            WalletEntity::RECEIVED             => true,
            WalletEntity::WALLET               => $input['payment']['wallet'],
            WalletEntity::GATEWAY_REFUND_ID    => $content[ResponseFields::GATEWAY_PAYMENT_ID],
            WalletEntity::REFUND_ID            => $input['refund']['id'],
            WalletEntity::EMAIL                => $input['payment']['email'],
            WalletEntity::CONTACT              => $input['payment']['contact'],
            WalletEntity::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            WalletEntity::RESPONSE_CODE        => $content[ResponseFields::RESPONSE_CODE],
            WalletEntity::RESPONSE_DESCRIPTION => $content[ResponseFields::RESPONSE_DESCRIPTION]
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
    //-------------------------------Refund helper functions end----------------------------------

    //-------------------------------Verify helper functions begin--------------------------------

    /**
     * JioMoney payment verification is weird. They have 2 Apis
     * 1. STATUSQUERY - Cache based api, returns transaction status stored in a cache and cache is wiped after 3 hours
     * 2. CHECKPAYMENTSTATUS - DB based api, As per the documentation this will return transaction data 3-4 mins
     * post transaction time. This might realistically be about 10 mins so for our verify use case,
     * we first make a call to the STATUSQUERY API. The response has a flag to indicate if data was found in cache.
     * If found, we proceed with the verify flow, else we make a call to CHECKPAYMENTSTATUS API
     * and proceed with its response
     */
    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $statusQueryRequest = $this->getStatusQueryRequest($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $statusQueryRequest);

        $statusQueryResponse = $this->sendGatewayRequest($statusQueryRequest);

        $this->response = $statusQueryResponse;

        $content = $this->jsonToArray($this->response->body);

        if ($this->validStatusQueryResponse($content) === false)
        {
            $checkPaymentStatusRequest = $this->getCheckPaymentStatusRequest($input);

            $checkPaymentStatusResponse = $this->sendGatewayRequest($checkPaymentStatusRequest);

            $this->response = $checkPaymentStatusResponse;

            $content = $this->jsonToArray($this->response->body);
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content'    => $content,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function verifyPayment($verify)
    {
        $gatewayPayment = $verify->payment;
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($this->getGatewayTxnStatus($content) === 'SUCCESS')
        {
            $this->verifyStatusOnGatewaySuccess($gatewayPayment, $input, $verify);
        }
        else
        {
            $this->verifyStatusOnGatewayFailure($gatewayPayment, $input, $verify);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        if ($verify->match === false)
        {
            $verify->payment = $this->saveVerifyContent($gatewayPayment,
                                                        $verify);
        }

        return $verify->status;
    }

    protected function verifyStatusOnGatewaySuccess($gatewayPayment, $input, $verify)
    {
        $verify->gatewaySuccess = true;

        $content = $verify->verifyResponseContent;

        if (($input['payment']['status'] !== PaymentStatus::CREATED) and
                ($input['payment']['status'] !== PaymentStatus::FAILED))
        {
            $verify->apiSuccess = true;
        }
        else
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;

            $verify->apiSuccess = false;
        }
    }

    public function verifyStatusOnGatewayFailure($gatewayPayment, array $input, $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if (($gatewayPayment === null) or
            (($input['payment']['status'] === 'failed') or
                ($input['payment']['status'] === 'created')))
        {
            $verify->apiSuccess = false;
        }
        else if (($gatewayPayment['received'] === false) and
                 (($gatewayPayment['status_code'] === null) or
                    ($gatewayPayment[WalletEntity::STATUS_CODE] !== StatusCode::SUCCESS)))
        {
            $verify->apiSuccess = false;
        }
        else if ($gatewayPayment[WalletEntity::STATUS_CODE] === StatusCode::SUCCESS)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = true;
        }
    }

    protected function saveVerifyContent($gatewayPayment, $verify)
    {
        $this->action = Action::AUTHORIZE;
        if ($verify->gatewaySuccess === true)
        {
            $walletAttributes = $this->getVerifyWalletCreateAttributes($verify);
            if ($gatewayPayment === null)
            {
                $gatewayPayment = $this->createGatewayPaymentEntity($walletAttributes);
            }
            else if (($gatewayPayment['received'] === false) or
                     ($gatewayPayment['status_code'] !== StatusCode::SUCCESS))
            {
                $gatewayPayment->fill($walletAttributes);
                $gatewayPayment->saveOrFail();
            }
        }

        $this->action = Action::VERIFY;

        return $gatewayPayment;
    }

    protected function getVerifyWalletCreateAttributes($verify)
    {
        $payment = $this->input['payment'];

        $content = $verify->verifyResponseContent;

        $contentToSave = array(
            ResponseFields::AMOUNT               => $payment[Payment::AMOUNT],
            RequestFields::MERCHANT_ID           => $this->getMerchantId(),
            WalletEntity::RECEIVED               => true,
            WalletEntity::EMAIL                  => $payment[Payment::EMAIL],
            WalletEntity::CONTACT                => $payment[Payment::CONTACT],
            ResponseFields::STATUS_CODE          => StatusCode::SUCCESS,
            ResponseFields::RESPONSE_CODE        => 'SUCCESS',
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
            return $content[StatusQueryResponseFields::RESPONSE_HEADER][StatusQueryResponseFields::API_STATUS] === '1';
        }

        return false;
    }

    protected function getGatewayTxnStatus(array $content)
    {
        if ($this->validStatusQueryResponse($content) === true)
        {
            return $content[StatusQueryResponseFields::PAYLOAD_DATA][StatusQueryResponseFields::TXN_STATUS];
        }
        else
        {
            return $content[ResponseFields::RESPONSE][ResponseFields::CHECKPAYMENTSTATUS]
                    [ResponseFields::TXN_STATUS];
        }
    }

    protected function getGatewayPaymentId(array $content)
    {
        if ($this->validStatusQueryResponse($content) === true)
        {
            return $content[StatusQueryResponseFields::PAYLOAD_DATA][StatusQueryResponseFields::JM_TRAN_REF_NO];
        }
        else
        {
            return $content[ResponseFields::RESPONSE][ResponseFields::CHECKPAYMENTSTATUS]
                    [ResponseFields::JM_TRAN_REF_NO];
        }
    }

    protected function getCheckPaymentStatusRequest(array $input)
    {
        $this->domainType = $this->mode . '_' . $this->action;

        $content = [
            RequestFields::APINAME       => ApiName::CHECKPAYMENTSTATUS,
            RequestFields::MODE          => self::JSON_MODE,
            RequestFields::REQUEST_ID    => Uuid::uuid4()->toString(),
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

    protected function getStatusQueryRequest(array $input)
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
            $input['payment'][Payment::ID]
        ];

        $hash = $this->getHashOfArray($hashArray);

        $content[RequestFields::CHECKSUM] = $hash;

        $content = json_encode($content);

        $this->action = Action::PAYMENT_STATUS;

        $request = $this->getStandardRequestArray($content);

        $this->action = Action::VERIFY;

        $request['headers'] = $this->getRequestHeaders($content);

        return $request;
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }
    //----------------------------Verify helper methods end--------------------------------

    protected function parseResponseBody($content)
    {
        $responseFieldsArray = ResponseFields::getResponseFieldsArray();

        $gatewayResponseArray = $this->getGatewayResponseArray($content);

        return array_combine($responseFieldsArray, $gatewayResponseArray);
    }

    protected function parseGatewayResponse(\Requests_Response $response)
    {
        $content = $this->jsonToArray($response->body);

        if ($content === null)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                '',
                'Invalid JSON in Response Body');
        }

        return $this->parseResponseBody($content);
    }

    protected function getGatewayResponseArray($content)
    {
        return explode('|', $content['response']);
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
            'Content-Length' => strlen($content)
        ];
    }

    public function getStringToHash($content, $glue = '|')
    {
        return implode($glue, $content);
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

    protected function getFormattedTimeStamp($timestamp, $format)
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
