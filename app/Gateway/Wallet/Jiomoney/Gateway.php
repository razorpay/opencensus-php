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
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Entity as BaseGatewayEntity;
use RZP\Gateway\Wallet\Base\Entity as WalletEntity;
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

    protected $gateway = 'wallet_jiomoney';

    protected $map = [
        RequestFields::MERCHANT_ID           => WalletEntity::GATEWAY_MERCHANT_ID,
        RequestFields::PAYMENT_ID            => WalletEntity::PAYMENT_ID,
        RequestFields::AMOUNT                => WalletEntity::AMOUNT,
        ResponseFields::STATUS_CODE          => WalletEntity::STATUS_CODE,
        ResponseFields::RESPONSE_CODE        => WalletEntity::RESPONSE_CODE,
        ResponseFields::RESPONSE_DESCRIPTION => WalletEntity::RESPONSE_DESCRIPTION,
        ResponseFields::GATEWAY_PAYMENT_ID   => WalletEntity::GATEWAY_PAYMENT_ID,
        ResponseFields::DATE                 => WalletEntity::DATE,
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
            WalletEntity::ACTION               => $this->action,
            WalletEntity::AMOUNT               => $input['refund']['amount'],
            WalletEntity::RECEIVED             => true,
            WalletEntity::WALLET               => $input['payment']['wallet'],
            WalletEntity::GATEWAY_REFUND_ID    => $content[ResponseFields::GATEWAY_PAYMENT_ID],
            WalletEntity::REFUND_ID            => $input['refund']['id'],
            WalletEntity::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            WalletEntity::RESPONSE_CODE        => $content[ResponseFields::RESPONSE_CODE],
            WalletEntity::RESPONSE_DESCRIPTION => $content[ResponseFields::RESPONSE_DESCRIPTION]
        ];

        return $refundAttributes;
    }

    protected function getCheckPaymentStatusRequestArray(array $input)
    {

    }

    protected function getCaptureResponse()
    {
        return null;
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

    protected function getStringToHash($content, $glue = '|')
    {
        return implode($glue, $content);
    }

    protected function getHashOfString($hashString)
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
}
