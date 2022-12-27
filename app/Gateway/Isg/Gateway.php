<?php

namespace RZP\Gateway\Isg;

use Carbon\Carbon;
use phpseclib\Crypt\AES;

use RZP\Exception;
use RZP\Constants;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\BharatQr;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\ScroogeResponse;
use RZP\Constants\Entity as BaseEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;
use ApiResponse;

class Gateway extends Base\Gateway
{
    // adding the below prefix in refund id as bank expects the id to be atleast 16 characters.
    const REFUND_ID_PREFIX = 'razorrfnd';

    protected $gateway = 'isg';

    public function preProcessServerCallback($input, $isBharatQr = false): array
    {
        if ($isBharatQr === true)
        {
            $qrData = $this->getQrData($input);

            return [
                'qr_data'       => $qrData,
                'callback_data' => $input,
            ];
        }

        return $input;
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($this->isBharatQrPayment() === true)
        {
            $this->createGatewayPaymentEntityForQr($input);

            return null;
        }

        throw new Exception\LogicException(
            'Not a Bharat Qr Payment',
            null,
            [
                'input'     => $input,
                'gateway'   => $this->gateway
            ]);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function verifyRefund(array $input)
    {
        $scroogeResponse = new ScroogeResponse();

        return $scroogeResponse->setSuccess(false)
                               ->setStatusCode(ErrorCode::GATEWAY_ERROR_VERIFY_REFUND_NOT_SUPPORTED)
                               ->toArray();
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $gatewayEntity = $this->repo->findByPaymentIdAndAction($input['payment']['id'], Base\Action::AUTHORIZE);

        $refundDataToSave = [
            Entity::MERCHANT_PAN => $gatewayEntity[Entity::MERCHANT_PAN]
        ];

        $attributes = $this->getRefundRequestData($gatewayEntity);

        $gatewayPayment = $this->createGatewayPaymentEntity($input, $refundDataToSave);

        $request = $this->getStandardRequestArray($attributes);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        $responseContent = $this->jsonToArray($response->body);

        $refundAttributesToSave = $this->getRefundAttributes($responseContent, $gatewayEntity);

        $this->updateGatewayPaymentEntity($gatewayPayment, $refundAttributesToSave, false);

        $this->assertRefundId(self::REFUND_ID_PREFIX . $input['refund']['id'],
                               $responseContent[Field::RFD_TXN_ID]);

        $gatewayData = [
            Payment\Gateway::GATEWAY_RESPONSE => $response->body,
            Payment\Gateway::GATEWAY_KEYS     => $this->getGatewayData($responseContent),
        ];

        if ($this->isRefundSuccessful($responseContent) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                $gatewayData
            );
        }

        return $gatewayData;
    }

    protected function getRefundRequestData($gatewayEntity)
    {
        $transactionDate = Carbon::createFromTimestamp($this->input['payment']['created_at'])
                                                        ->timezone(Timezone::IST)->format('Ymd');

        $refundTimeStamp = Carbon::now(Timezone::IST)->format('YmdHis');

        $content = [
            Field::RFD_TXN_ID         => self::REFUND_ID_PREFIX . $this->input['refund']['id'],
            Field::TXN_ID             => $gatewayEntity[Entity::BANK_REFERENCE_NUMBER],
            Field::MERCHANT_PAN       => $gatewayEntity[Entity::MERCHANT_PAN],
            Field::TXN_DATE           => $transactionDate,
            Field::TXN_AMOUNT         => $this->getFormattedAmount($gatewayEntity['amount']),
            Field::RFD_TXN_DATE_TIME  => $refundTimeStamp,
            Field::RFD_TXN_AMOUNT     => $this->getFormattedAmount($this->input['refund']['amount']),
            Field::AUTH_CODE          => $gatewayEntity[Entity::AUTH_CODE],
            Field::RRN                => $gatewayEntity[Entity::RRN],
        ];

        return $content;
    }

    protected function getRefundAttributes($response, $gatewayEntity)
    {
        $attributes = [
            Entity::RECEIVED                    => true,
            Entity::AMOUNT                      => $this->input['refund']['amount'],
            Entity::REFUND_ID                   => $this->input['refund']['id'],
            Entity::MERCHANT_PAN                => $gatewayEntity[Entity::MERCHANT_PAN],
            Entity::BANK_REFERENCE_NUMBER       => $response[Field::RFD_TXN_ID],
            Entity::AUTH_CODE                   => $gatewayEntity[Entity::AUTH_CODE],
            Entity::STATUS_CODE                 => $response[Field::STATUS_CODE],
        ];

        return $attributes;
    }

    protected function assertRefundId($actualRefundId, $expectedRefundId)
    {
        if ($actualRefundId !== $expectedRefundId)
        {
            throw new Exception\LogicException(
                'Data tampering found.', null, [
                'expected' => $expectedRefundId,
                'actual'   => $actualRefundId
            ]);
        }
    }

    protected function isRefundSuccessful($response) : bool
    {
        // need to confirm with the bank once, what will the error codes. Till then throwing exception using the
        // exception returned by gateway
        return ($response[Field::STATUS_CODE] !== Status::APPROVED) ? false : true;
    }

    protected function getVerifyCallbackRequestArray($input)
    {
        $attributes = [
            Field::TRANSACTION_ID     => $input[Field::TRANSACTION_ID],
            Field::PRIMARY_ID         => $input[Field::PRIMARY_ID],
            Field::TERMINAL_ID        => $input[TerminalEntity::TERMINAL_ID],
            Field::TRANSACTION_DATE   => $this->getFormattedDate($input[Field::TRANSACTION_DATE_TIME]),
            Field::TRANSACTION_AMOUNT => $input[Field::TRANSACTION_AMOUNT],
        ];

        return $attributes;
    }

    protected function getVerifyRequestArray($input, $gatewayPayment)
    {
        $attributes = [
            Field::TRANSACTION_ID     => $gatewayPayment[Entity::BANK_REFERENCE_NUMBER],
            Field::PRIMARY_ID         => $gatewayPayment[Entity::MERCHANT_REFERENCE],
            Field::TRANSACTION_AMOUNT => $this->getFormattedAmount($gatewayPayment[Entity::AMOUNT]),
            Field::TRANSACTION_DATE   => $this->getFormattedDate($gatewayPayment[Entity::TRANSACTION_DATE_TIME]),
            Field::TERMINAL_ID        => $input[BaseEntity::TERMINAL][TerminalEntity::GATEWAY_TERMINAL_ID],
        ];

        return $attributes;
    }

    protected function checkStatusCode($response, $input)
    {
        if ($response[Field::STATUS_CODE] === Status::APPROVED)
        {
            return;
        }

        switch ($response[Field::STATUS_CODE])
        {
            case Status::FALLBACK:
                $gatewayErrorCode = 'E006';
                break;

            case Status::NO_RECORDS:
                $gatewayErrorCode = 'E005';
                break;

            default:
                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                    null,
                    null,
                    [
                        'gateway'   => $this->gateway,
                        'request'   => $input,
                        'response'  => $response,
                    ]);
        }

        $this->handleGatewayError($gatewayErrorCode, $input, $response);
    }

    protected function handleGatewayError($gatewayErrorCode, $input, $response)
    {
        $errorCode = ResponseCode::getErrorCode($gatewayErrorCode);

        $errorMessage = ResponseCode::getResponseCodeMessage($gatewayErrorCode);

        throw new Exception\GatewayErrorException(
            $errorCode,
            $gatewayErrorCode,
            $errorMessage,
            [
                'input'       => $input,
                'response'    => $response,
                'gateway'     => $this->gateway,
            ]);
    }

    protected function checkVerifyCallbackResponse($response, $input)
    {
        $this->checkMismatch($this->getFormattedAmount($response[Field::TRANSACTION_AMOUNT]),
            $this->getFormattedAmount($input[Field::TRANSACTION_AMOUNT]),
            $input,
            $response,
            'E001'
        );

        $this->checkMismatch(trim($response[Field::MERCHANT_PAN]),
            trim($input[Field::MERCHANT_PAN]),
            $input,
            $response,
            'E003'
        );

        $expectedConsumerPan = $this->getDecryptedString($response[Field::CONSUMER_PAN]);

        $this->checkDecryptionFailure($response[Field::CONSUMER_PAN], $expectedConsumerPan);

        $actualConsumerPan = $this->getDecryptedString($input[Field::CONSUMER_PAN]);

        $this->checkMismatch($expectedConsumerPan,
            $actualConsumerPan,
            $input,
            $response,
            'E002'
        );

        $this->checkMismatch($response[Field::STATUS_CODE],
            $input[Field::STATUS_CODE],
            $input,
            $response,
            'E004'
        );
    }

    protected function checkMismatch($expected, $actual, $input, $response, $responseCode)
    {
        if ($expected !== $actual)
        {
            $errorCode = ResponseCode::getErrorCode($responseCode);

            $errorMessage = ResponseCode::getResponseCodeMessage($responseCode);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $responseCode,
                $errorMessage,
                [
                    'callback_response'        => $input,
                    'verify_callback_response' => $response,
                    'gateway'                  => $this->gateway,
                ]);
        }
    }

    protected function verifyPayment($verify)
    {
        $this->setVerifyStatus($verify);

        $this->setVerifyAmountMismatch($verify);

        $this->saveVerifyContent($verify);
    }

    protected function setVerifyAmountMismatch(Verify $verify)
    {
        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        $verify->amountMismatch = true;

        $expectedAmount = $this->getFormattedAmount($input['payment']['amount']);

        $actualAmount = $content[Field::TRANSACTION_AMOUNT];

        if ($expectedAmount === $actualAmount)
        {
            $verify->amountMismatch = false;
        }
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

    protected function saveVerifyContent($verify)
    {
        $content = $verify->verifyResponseContent;

        $gatewayPayment = $verify->payment;

        $gatewayPayment->fill($content);

        $this->repo->saveorFail($gatewayPayment);
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        if ((isset($content[Field::STATUS_CODE]) === true) and
            ($content[Field::STATUS_CODE] === Status::APPROVED))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        if (isset($verify->payment) === true)
        {
            // called once a payment is made to run verify on that
            $gatewayPayment = $verify->payment;

            $attributes = $this->getVerifyRequestArray($input, $gatewayPayment);
        }
        else
        {
            // called before making payment entity, to check whether the notifcation was sent by gateway only
            $attributes = $this->getVerifyCallbackRequestArray($input);
        }

        $request = $this->getStandardRequestArray($attributes);

        $this->traceGatewayPaymentRequest($request,
            $input,
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response->body,
            $input,
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $responseArray = $this->jsonToArray($response->body);

        $verify->verifyResponseContent = $responseArray;
    }

    public function getSecret()
    {
        return $this->config['bharat_qr_secret'];
    }

    protected function getDecryptedString($string)
    {
        $masterKey = hex2bin($this->getSecret());

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        if (ctype_xdigit($string) === true)
        {
            return $aes->decryptString(hex2bin($string));
        }

        return null;
    }

    protected function getEncryptedString($string)
    {
        $masterKey = hex2bin($this->getGatewayInstance()->getSecret());

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return bin2hex($aes->encryptString($string));
    }

    public function getQrData(array $input)
    {
        $customerCardNumber = $this->getDecryptedString($input[Field::CONSUMER_PAN]);

        $this->checkDecryptionFailure($input[Field::CONSUMER_PAN], $customerCardNumber);

        $qrData = [
            BharatQr\GatewayResponseParams::AMOUNT                => $this->getIntegerFormattedAmount(
                                                                                    $input[Field::TRANSACTION_AMOUNT]),
            BharatQr\GatewayResponseParams::CARD_FIRST6           => substr($customerCardNumber, 0, 6),
            BharatQr\GatewayResponseParams::CARD_LAST4            => substr($customerCardNumber, -4),
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::CARD,
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => $input[Field::PRIMARY_ID],
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => strval($input[Field::TRANSACTION_ID]),
            BharatQr\GatewayResponseParams::MPAN                  => $input[Field::MERCHANT_PAN],
            ];

        return $qrData;
    }

    public function verifyBharatQrNotification($input)
    {
        $input = $input['callback_data'];

        $terminalId = $this->terminal->getGatewayTerminalId();

        $input[TerminalEntity::TERMINAL_ID] = $terminalId;

        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $this->sendPaymentVerifyRequest($verify);

        $response = $verify->verifyResponseContent;

        $this->checkStatusCode($response, $input);

        $this->checkVerifyCallbackResponse($response, $input);
    }

    protected function checkDecryptionFailure($encryptedString, $decryptedString)
    {
        if (empty($decryptedString) === true)
        {
            $this->trace->error(
              TraceCode::PAYMENT_CALLBACK_FAILURE,
              [
                  'encryptedString' => $encryptedString,
                  'gateway'         => $this->gateway,
              ]);

            $this->handleGatewayError('E007', $encryptedString, $decryptedString);
        }
    }

    protected function createGatewayPaymentEntityForQr($input)
    {
        $attributes = $this->getAttributesFromQrResponse($input);

        $attributes[Entity::RECEIVED] = true;

        $payment = $this->createGatewayPaymentEntity($input, $attributes);

        return $payment;
    }

    protected function getAttributesFromQrResponse(array $input)
    {
        $attributes = [
            Entity::MERCHANT_REFERENCE          => $input[Field::PRIMARY_ID],
            Entity::BANK_REFERENCE_NUMBER       => $input[Field::TRANSACTION_ID],
            Entity::TRANSACTION_DATE_TIME       => $input[Field::TRANSACTION_DATE_TIME],
            Entity::AUTH_CODE                   => $input[Field::AUTH_CODE],
            Entity::RRN                         => $input[Field::RRN],
            Entity::STATUS_CODE                 => $input[Field::STATUS_CODE],
            Entity::MERCHANT_PAN                => $input[Field::MERCHANT_PAN],
        ];

        if (isset($input[Field::SECONDARY_ID]) === true)
        {
            $attributes[Entity::SECONDARY_ID] = $input[Field::SECONDARY_ID];
        }

        if (isset($input[Field::TIP_AMOUNT]) === true)
        {
            $attributes[Entity::TIP_AMOUNT] = $this->getIntegerFormattedAmount($input[Field::TIP_AMOUNT]);
        }

        $statusDescription = Status::getStatusCodeDescription($input[Field::STATUS_CODE]);

        $attributes[Entity::STATUS_DESC] = $statusDescription;

        $attributes[Entity::RECEIVED] = true;

        return $attributes;
    }

    protected function createGatewayPaymentEntity(array $input, array $attributes = [], $action = null)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $action = $action ?: $this->action;

        $gatewayPayment->setAction($action);

        if ($action === Base\Action::REFUND)
        {
            $gatewayPayment->setRefundId($input['refund']['id']);
        }

        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->setAmount($input['payment']['amount']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getFormattedDate(string $dateTime)
    {
        $date = Carbon::parse($dateTime)->format('Ymd');

        return $date;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', ',');
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input = null,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        $paymentId = $input['payment']['id'] ?? null;

        $this->trace->info(
            $traceCode,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $paymentId,
            ]);
    }

    protected function traceGatewayPaymentResponse(
        $response,
        $input = null,
        $traceCode = TraceCode::GATEWAY_PAYMENT_RESPONSE)
    {
        $paymentId = $input['payment']['id'] ?? null;

        $this->trace->info(
            $traceCode,
            [
                'request'    => $response,
                'gateway'    => $this->gateway,
                'payment_id' => $paymentId,
            ]);
    }

    /*
     * in case of any payment notification received , we need to send status to Isg gateway, about the payment.
     * We get to know about the payment state by flag $valid which is set true in case of success
     * If the payment fails due to getting a mismatch in callback , we send them the reason through exception object
     * If for any other reason the BharatQr Payment is failing,  we send them generic status code as Failed.
     */

    public function getBharatQrResponse(bool $valid, $input = null, $ex = null)
    {
        $attributes = [
            Field::TRANSACTION_ID                   => $input[Field::TRANSACTION_ID],
            Field::NOTIFICATION_REF_NO              => null,
        ];

        if ($valid === true)
        {
            $gatewayEntity = $this->repo->fetchByBankReferenceNumber(strval($input[Field::TRANSACTION_ID]));

            $attributes[Field::NOTIFICATION_REF_NO] = $gatewayEntity[Entity::PAYMENT_ID];

            $attributes[Field::STATUS_CODE] = Status::APPROVED;

            $attributes[Field::STATUS_DESC] = Status::SUCCESS;
        }
        else if ((isset($ex) === true) and ($ex instanceOf Exception\GatewayErrorException))
        {
            $attributes[Field::STATUS_CODE] = Status::NO_RECORDS;

            $error = $ex->getError()->getAttributes();

            $attributes[Field::STATUS_DESC] = $error['gateway_error_desc'];
        }
        else
        {
            $attributes[Field::STATUS_CODE] = Status::NO_RECORDS;

            $attributes[Field::STATUS_DESC] = Status::FAILED;
        }

        return $attributes;
    }

    protected function getGatewayData(array $response = [])
    {
        if (empty($response) === false)
        {
            return [
                Field::STATUS_CODE => $response[Field::STATUS_CODE] ?? null,
                Field::STATUS_DESC => $response[Field::STATUS_DESC] ?? null,
                Field::RFD_TXN_ID  => $response[Field::RFD_TXN_ID] ?? null,
            ];
        }

        return [];
    }
}
