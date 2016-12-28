<?php

namespace RZP\Gateway\Wallet\Jiomoney;

use Carbon\Carbon;

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
use RZP\Gateway\Base\Entity as BaseGatewayEntity;
use RZP\Gateway\Wallet\Base\Entity as WalletEntity;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $canRunOtpFlow = false;

    protected $topup = false;

    const FORMAT = 'YmdHis';

    const DEFAULT_TXN_CHANNEL = 'WEB';

    const DEFAULT_CURRENCY_CODE = 'INR';

    const DEFAULT_CUSTOMER_NAME = 'Dummy Name';

    const JSON_MODE = '2';

    const STATUS_QUERY_API_VERSION = '1.0';

    protected $gateway = 'wallet_jiomoney';

     protected $sortRequestContent = false;

    protected $map = [
        RequestFields::MERCHANT_ID           => WalletEntity::GATEWAY_MERCHANT_ID,
        RequestFields::PAYMENT_ID            => WalletEntity::PAYMENT_ID,
        RequestFields::AMOUNT                => WalletEntity::AMOUNT,
        ResponseFields::STATUS_CODE          => WalletEntity::STATUS_CODE,
        ResponseFields::RESPONSE_CODE        => WalletEntity::RESPONSE_CODE,
        ResponseFields::RESPONSE_DESCRIPTION => WalletEntity::RESPONSE_DESCRIPTION,
        ResponseFields::GATEWAY_PAYMENT_ID   => WalletEntity::GATEWAY_PAYMENT_ID,
        ResponseFields::DATE                 => WalletEntity::DATE,
        WalletEntity::EMAIL                  => WalletEntity::EMAIL,
        WalletEntity::CONTACT                => WalletEntity::CONTACT,
        BaseGatewayEntity::RECEIVED          => BaseGatewayEntity::RECEIVED
    ];

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
            BaseGatewayEntity::RECEIVED => false
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

        $this->validateResponseChecksum($input['gateway']);

        if (StatusCode::isSuccessStatus($input['gateway'][ResponseFields::STATUS_CODE]) === false)
        {
            $this->callbackAuthFailureFlow($input);
        }
        else
        {
            $this->callbackAuthSuccessFlow($input);

            return $this->getCallbackResponseData($input);
        }
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

        $this->validateResponseChecksum($content);

        $this->createWalletRefundEntity($content, $input);

        if (StatusCode::isSuccessStatus($content[ResponseFields::STATUS_CODE]) === false)
        {
            $this->handleRefundFailure($content);
        }
    }

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
        elseif (($gatewayPayment['received'] === false) and
                 (($gatewayPayment['status_code'] === null) or
                    (StatusCode::isSuccessStatus($gatewayPayment[WalletEntity::STATUS_CODE]) !== true)))
        {
            $verify->apiSuccess = false;
        }
        elseif (StatusCode::isSuccessStatus($gatewayPayment[WalletEntity::STATUS_CODE]) === true)
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
            ResponseFields::AMOUNT             => $payment['amount'],
            RequestFields::MERCHANT_ID         => $this->getMerchantId(),
            WalletEntity::RECEIVED             => true,
            WalletEntity::EMAIL                => $payment['email'],
            WalletEntity::CONTACT              => $payment['contact'],
            ResponseFields::STATUS_CODE        => StatusCode::SUCCESS,
            ResponseFields::RESPONSE_CODE      => 'SUCCESS',
            ResponseFields::RESPONSE_DESCRIPTION => 'APPROVED',
            ResponseFields::GATEWAY_PAYMENT_ID => $this->getGatewayPaymentId($content)
        );

        return $contentToSave;
    }

    public function validStatusQueryResponse($content)
    {
        if (isset($content['response_header']) === true)
        {
            return $content['response_header']['api_status'] === '1';
        }

        return false;
    }

    protected function getGatewayTxnStatus(array $content)
    {
        if ($this->validStatusQueryResponse($content) === true)
        {
            return $content['payload_data']['txn_status'];
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
            return $content['payload_data']['txn_status'];
        }
        else
        {
            return $content[ResponseFields::RESPONSE][ResponseFields::CHECKPAYMENTSTATUS]
                    [ResponseFields::JM_TRAN_REF_NO];
        }
    }

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
            BaseGatewayEntity::RECEIVED          => true
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

    protected function handleCallbackFailure($content)
    {
        throw new Exception\GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
            $content[ResponseFields::RESPONSE_CODE],
            $content[ResponseFields::RESPONSE_DESCRIPTION]
        );
    }

    protected function handleRefundFailure(array $content)
    {
        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_REFUND_FAILED,
            $content[ResponseFields::RESPONSE_CODE],
            $content[ResponseFields::RESPONSE_DESCRIPTION]
        );
    }

    protected function getCheckPaymentStatusRequest(array $input)
    {
        $this->domainType = 'test_verify';          // TODO Change this later

        $content = [
            RequestFields::APINAME       => ApiName::CHECKPAYMENTSTATUS,
            RequestFields::MODE          => self::JSON_MODE,
            RequestFields::REQUEST_ID    => $this->genuuid(),
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
            RequestFields::REQUEST_HEADER => [
                RequestFields::VERSION => self::STATUS_QUERY_API_VERSION,
                RequestFields::API_NAME => 'STATUSQUERY',
            ],
            RequestFields::PAYLOAD_DATA => [
                'client_id' => $this->getClientId(),
                'merchant_id' => $this->getMerchantId(),
                'tran_ref_no' => $input['payment']['id']
            ]
        ];

        $hashArray = [
            $this->getClientId(),
            $this->getMerchantId(),
            'STATUSQUERY',
            $input['payment']['id']
        ];

        $hash = $this->getHashOfArray($hashArray);

        $content[RequestFields::CHECKSUM] = $hash;

        $content = json_encode($content);

        $this->action = 'payment_status';

        $request = $this->getStandardRequestArray($content);

        $this->action = Action::VERIFY;

        $request['headers'] = $this->getRequestHeaders($content);

        return $request;
    }

    protected function getVerifyResponseContent(array $content)
    {
        return $content[ResponseFields::RESPONSE][ResponseFields::CHECKPAYMENTSTATUS];
    }

    protected function getRefundRequest(array $input)
    {
        $wallet = $this->repo->fetchWalletByPaymentId($input['payment']['id']);

        $content = $this->getRefundRequestContent($input, $wallet);

        $content = json_encode($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = $this->getRequestHeaders($content);

        return $request;
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
                RequestFields::TXN_TYPE   => 'REFUND',
                RequestFields::AMOUNT     => $this->getFormattedAmount($input['amount']),
                RequestFields::CURRENCY   => 'INR',
            ],
            RequestFields::REFUND_INFO  => $refundInfo,
        ];

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

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($hashArray);

        return $content;
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

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }

    protected function getPurchaseRequestArray(array $input)
    {
        $payment = $input['payment'];

        $content = $this->getPurchaseRequestContent($payment, $input['callbackUrl']);

        return $this->getStandardRequestArray($content);
    }

    protected function getPurchaseRequestContent(array $payment, string $callbackUrl)
    {
        $timestamp = $this->getFormattedTimeStamp($payment['created_at'], self::FORMAT);

        $amount = $this->getFormattedAmount($payment['amount']);

        $content = [
            RequestFields::MERCHANT_ID                                     => $this->getMerchantId(),
            RequestFields::CLIENT_ID                                       => $this->getClientId(),
            RequestFields::CHANNEL                                         => self::DEFAULT_TXN_CHANNEL,
            RequestFields::CALLBACK_URL                                    => $callbackUrl,
            RequestFields::TOKEN                                           => '',
            RequestFields::TRANSACTION . '.' . RequestFields::PAYMENT_ID   => $payment['id'],
            RequestFields::TRANSACTION . '.' . RequestFields::TIMESTAMP    => $timestamp,
            RequestFields::TRANSACTION . '.' . RequestFields::TXN_TYPE     => Action::PURCHASE,
            RequestFields::TRANSACTION . '.' . RequestFields::AMOUNT       => $amount,
            RequestFields::TRANSACTION . '.' . RequestFields::CURRENCY     => self::DEFAULT_CURRENCY_CODE,
            RequestFields::SUBSCRIBER . '.' . RequestFields::CUSTOMER_NAME => self::DEFAULT_CUSTOMER_NAME,
            RequestFields::SUBSCRIBER . '.' . RequestFields::EMAIL         => $payment['email'],
            RequestFields::SUBSCRIBER . '.' . RequestFields::CONTACT       => $payment['contact']
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

    protected function parseResponseBody($content)
    {
        $responseFieldsArray = ResponseFields::getResponseFieldsArray();

        $gatewayResponseArray = $this->getGatewayResponseArray($content);

        return array_combine($responseFieldsArray, $gatewayResponseArray);
    }

    protected function parseGatewayResponse(\Requests_Response $response)
    {
        if ($response->body === '')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                '',
                'Invalid JSON in Response Body');
        }

        $content = $this->jsonToArray($response->body);

        return $this->parseResponseBody($content);
    }

    protected function getGatewayResponseArray($content)
    {
        return explode('|', $content['response']);
    }

    protected function validateResponseChecksum($content)
    {
        $responseCheckSum = $content[ResponseFields::CHECKSUM];

        $hashArray = $this->getResponseHashArray($content);

        $checksumCalculated = $this->getHashOfArray($hashArray);

        $checksumValid = ($checksumCalculated === $responseCheckSum);

        if ($checksumCalculated !== $responseCheckSum)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED
            );
        }
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
    }

    protected function getClientId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_client_id'];
        }
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

    protected function genuuid()
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}
