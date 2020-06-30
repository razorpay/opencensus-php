<?php

namespace RZP\Gateway\Worldline;

use RZP\Gateway\Base;
use RZP\Gateway\Mozart;
use RZP\Exception;
use RZP\Models\BharatQr;
use RZP\Gateway\Base\Verify;
use phpseclib\Crypt\AES;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Constants\Entity as BaseEntity;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Reconciliator\Base\Reconciliate;

class Gateway extends Base\Gateway
{
    protected $gateway = Payment\Gateway::WORLDLINE;

    protected $map = [
        Entity::MID              => Fields::MID,
        Entity::REF_NO           => Fields::REF_NO,
        Entity::BANK_CODE        => Fields::BANK_CODE,
        Entity::AUTH_CODE        => Fields::AUTH_CODE,
        Entity::PRIMARY_ID       => Fields::PRIMARY_ID,
        Entity::TXN_AMOUNT       => Fields::TXN_AMOUNT,
        Entity::SECONDARY_ID     => Fields::SECONDARY_ID,
        Entity::CUSTOMER_VPA     => Fields::CUSTOMER_VPA,
        Entity::TXN_CURRENCY     => Fields::TXN_CURRENCY,
        Entity::AGGREGATOR_ID    => Fields::AGGREGATOR_ID,
        Entity::TRANSACTION_TYPE => Fields::TRANSACTION_TYPE,
    ];

    public function preProcessServerCallback($input, $isBharatQr = false): array
    {
        $this->addDefaultInputsIfRequired($input);

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

    protected function getVerifyCallbackRequestArray($input)
    {
        $attributes = [
            Fields::PARM => json_encode([
                Fields::FROM_ENTITY         => $input[Fields::AGGREGATOR_ID],
                Fields::VERIFY_BANK_CODE    => $input[Fields::BANK_CODE],
                Fields::DATA    => [
                        Fields::TID     => $input[TerminalEntity::TERMINAL_ID],
                        Fields::AMOUNT  => $input[Fields::TXN_AMOUNT],
                        Fields::TXN_ID  => $input[Fields::PRIMARY_ID],
                        Fields::TR_ID   => $input[Fields::SECONDARY_ID],
                ]
            ])
        ];

        return $attributes;
    }

    protected function getVerifyRequestArray($input, $gatewayPayment)
    {
        $attributes = [
            Fields::PARM => json_encode([
                Fields::FROM_ENTITY         => $gatewayPayment[Fields::AGGREGATOR_ID],
                Fields::VERIFY_BANK_CODE    => $gatewayPayment[Fields::BANK_CODE],
                Fields::DATA    => [
                    Fields::TID     => $input[BaseEntity::TERMINAL][TerminalEntity::GATEWAY_TERMINAL_ID],
                    Fields::AMOUNT  => $gatewayPayment[Fields::TXN_AMOUNT],
                    Fields::TXN_ID  => $gatewayPayment[Fields::PRIMARY_ID],
                    Fields::TR_ID   => $gatewayPayment[Fields::SECONDARY_ID],
                ]
            ])
        ];

        return $attributes;
    }

    protected function checkStatusCode($response, $input)
    {
        if ($response[Fields::STATUS] === Status::SUCCESS)
        {
            return;
        }

        switch ($response[Fields::STATUS])
        {
            case Status::PENDING:
                $gatewayErrorCode = 'E006';
                break;

            case Status::FAILED:
                $gatewayErrorCode = 'E008';
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

    protected function checkVerifyCallbackResponse($response, $input)
    {
        $this->checkMismatch(
            $this->getFormattedAmount($response[Fields::RESPONSE_OBJECT][Fields::TXN_AMOUNT]),
            $this->getFormattedAmount($input[Fields::TXN_AMOUNT]),
            $input,
            $response,
            'E001'
        );

        switch ($response[Fields::RESPONSE_OBJECT][Fields::TRANSACTION_TYPE])
        {
            case 1:
                $this->checkMismatch(
                    trim($response[Fields::RESPONSE_OBJECT][Fields::M_PAN]),
                    trim($input[Fields::M_PAN]),
                    $input,
                    $response,
                    'E003'
                );

                $expectedConsumerPan = $this->decryptAes($response[Fields::RESPONSE_OBJECT][Fields::CONSUMER_PAN]);

                $this->checkDecryptionFailure($response[Fields::RESPONSE_OBJECT][Fields::CONSUMER_PAN], $expectedConsumerPan);

                $actualConsumerPan = $this->decryptAes($input[Fields::CONSUMER_PAN]);

                $this->checkMismatch(
                    $expectedConsumerPan,
                    $actualConsumerPan,
                    $input,
                    $response,
                    'E002'
                );

                break;

            case 2:
                $this->checkMismatch(
                    $response[Fields::RESPONSE_OBJECT][Fields::CUSTOMER_VPA],
                    $input[Fields::CUSTOMER_VPA],
                    $input,
                    $response,
                    'E005'
                );

                break;
        }
    }

    protected function checkMismatch($expected, $actual, $input, $response, $responseCode)
    {
        if ($expected !== $actual)
        {
            $errorCode = ErrorCodeMap::getErrorCode($responseCode);

            $errorMessage = ErrorCodeMap::getResponseCodeMessage($responseCode);

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

        $actualAmount = $content[Fields::RESPONSE_OBJECT][Fields::TXN_AMOUNT];

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
        $content = $this->getMappedAttributes($verify->verifyResponseContent);

        $gatewayPayment = $verify->payment;

        $gatewayPayment->fill($content);

        $this->repo->saveorFail($gatewayPayment);
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        if ((isset($content[Fields::STATUS]) === true) and
            ($content[Fields::STATUS] === Status::SUCCESS))
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

        $request['options']['verify'] = false;

        $request['headers']['Content-Type'] = 'application/x-www-form-urlencoded';

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

    protected function createCryptoIfNotCreated()
    {
        $this->aesCrypto = new AESCrypto(AES::MODE_ECB, $this->getSecret());
    }

    public function decryptAes(string $stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->decryptString($stringToDecrypt);
    }

    public function getSecret()
    {
        return $this->config['aes_encryption_key'];
    }

    protected function getQrData(array $input)
    {
        $qrData = [
            BharatQr\GatewayResponseParams::AMOUNT                  => $this->getIntegerFormattedAmount(
                $input[Fields::TXN_AMOUNT]),
            BharatQr\GatewayResponseParams::GATEWAY_MERCHANT_ID     => $input[Fields::MID],
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE      => $input[Fields::PRIMARY_ID],
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID   => $input[Fields::REF_NO],
        ];

        switch ($input[Fields::TRANSACTION_TYPE])
        {
            case 1:

                if (Reconciliate::$isReconRunning === false)
                {
                    $customerCardNumber = $this->decryptAes($input[Fields::CONSUMER_PAN]);

                    $this->checkDecryptionFailure($input[Fields::CONSUMER_PAN], $customerCardNumber);

                    $qrData[BharatQr\GatewayResponseParams::CARD_FIRST6]    = substr($customerCardNumber, 0, 6);
                    $qrData[BharatQr\GatewayResponseParams::CARD_LAST4]     = substr($customerCardNumber, 12, 4);
                }
                else
                {
                    // We are in the flow of recon, trying to create unexpected payment
                    // We get card number directly in MIS file, which has been passed in callback data
                    // Take from that directly.
                    $qrData[BharatQr\GatewayResponseParams::CARD_FIRST6]    = $input[BharatQr\GatewayResponseParams::CARD_FIRST6];
                    $qrData[BharatQr\GatewayResponseParams::CARD_LAST4]     = $input[BharatQr\GatewayResponseParams::CARD_LAST4];
                }

                $qrData[BharatQr\GatewayResponseParams::SENDER_NAME]    = $input[Fields::CUSTOMER_NAME];
                $qrData[BharatQr\GatewayResponseParams::METHOD]         = Payment\Method::CARD;
                $qrData[BharatQr\GatewayResponseParams::MPAN]           = $input[Fields::M_PAN];

                break;

            case 2:
                $qrData[BharatQr\GatewayResponseParams::METHOD]         = Payment\Method::UPI;
                $qrData[BharatQr\GatewayResponseParams::VPA]            = $input[Fields::CUSTOMER_VPA];

                break;

            default:
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD);
        }

        return $qrData;
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

    protected function handleGatewayError($gatewayErrorCode, $input, $response)
    {
        $errorCode = ErrorCodeMap::getErrorCode($gatewayErrorCode);

        $errorMessage = ErrorCodeMap::getResponseCodeMessage($gatewayErrorCode);

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

    /**
     * if bharatQr payment is not successful $valid will be set to false in BharatQr Service,in
     * that case the reason of the failure is shared with gateway using exception thrown
     * else the value of $valid will be true and we will send the respective response to gateway.
     */
    public function getBharatQrResponse(bool $valid, $gatewayInput = null, $exception = null)
    {
        if ($valid === true)
        {
            $response = [
                'status'    => 'SUCCESS',
                'errorMsg'  => '',
            ];
        }
        else
        {
            $response = [
                'status'    => 'Failure',
                'errorMsg'  => '',
            ];
        }

        return $response;
    }

    protected function createGatewayPaymentEntityForQr($input)
    {
        $attributes = $this->getAttributesFromQrResponse($input);

        $payment = $this->createGatewayPaymentEntity($input, $attributes);

        return $payment;
    }

    protected function getAttributesFromQrResponse(array $input)
    {
        $attributes = [
            Entity::MID                 => $input[Fields::MID],
            Entity::TXN_CURRENCY        => $input[Fields::TXN_CURRENCY],
            Entity::TXN_AMOUNT          => $input[Fields::TXN_AMOUNT],
            Entity::AUTH_CODE           => $input[Fields::AUTH_CODE] ?? '',
            Entity::REF_NO              => $input[Fields::REF_NO],
            Entity::TRANSACTION_TYPE    => ($input[Fields::TRANSACTION_TYPE] === '1') ? 'CARD' : 'UPI',
            Entity::BANK_CODE           => $input[Fields::BANK_CODE],
            Entity::AGGREGATOR_ID       => $input[Fields::AGGREGATOR_ID],
            Entity::CUSTOMER_VPA        => $input[Fields::CUSTOMER_VPA] ?? '',
            Entity::PRIMARY_ID          => $input[Fields::PRIMARY_ID] ?? '',
            Entity::SECONDARY_ID        => $input[Fields::SECONDARY_ID] ?? '',
        ];

        return $attributes;
    }

    protected function createGatewayPaymentEntity(array $input, array $attributes = [], $action = null)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $action = $action ?: $this->action;

        $gatewayPayment->setAction($action);

        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->setAmount($input['payment']['amount']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
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

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', ',');
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Base\Action::AUTHORIZE);

        $request = $this->getRefundRequestArray($input, $gatewayPayment);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $request['options']['verify'] = false;

        $request['headers']['Content-Type'] = 'application/x-www-form-urlencoded';

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        $responseBody = json_decode($response->body,true);

        $attributes = $this->getAttributesFromRefundReverseResponse($responseBody);

        $this->createGatewayRefundEntity($input, $attributes);

        $this->checkErrorsAndThrowException($responseBody);
    }

    protected function getRefundRequestArray(array $input, Entity $gatewayPayment)
    {
        $attributes = [
            Fields::PARM => json_encode([
                Fields::FROM_ENTITY         => $gatewayPayment[Fields::AGGREGATOR_ID],
                Fields::REFUND_BANK_CODE    => $gatewayPayment[Fields::BANK_CODE],
                Fields::DATA    => [
                    Fields::TXN_TYPE            => ($gatewayPayment[Entity::TRANSACTION_TYPE] === Fields::CARD) ? '1' : '2',
                    Fields::TID                 => $input[BaseEntity::TERMINAL][TerminalEntity::GATEWAY_TERMINAL_ID],
                    Fields::RRN                 => $gatewayPayment[Fields::REF_NO],
                    Fields::REFUND_AUTH_CODE    => $gatewayPayment[Fields::AUTH_CODE],
                    Fields::REFUND_AMOUNT       => $this->getFormattedAmount($input['refund']['amount']),
                    Fields::REFUND_REASON       => 'NA',
                    Fields::MOBILE_NUMBER       => $input['terminal']['gateway_terminal_password'],
                    Fields::REFUND_ID           => $input['refund']['id'],
                ]
            ])
        ];

        return $this->getStandardRequestArray($attributes);
    }

    protected function getAttributesFromRefundReverseResponse(array $response) : array
    {
        if($response[Fields::STATUS] === Status::REFUND_SUCCESS)

        $attributes = [
            Entity::REF_NO           => $response[Fields::RESPONSE_OBJECT][Fields::RRN],
            Entity::REFUND_ID        => $response[Fields::RESPONSE_OBJECT][Fields::REFUND_ID]
        ];

        return $attributes;
    }

    protected function createGatewayRefundEntity(array $input, array $attributes = [])
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->setRefundId($input['refund']['id']);

        $gatewayPayment->setCurrency($input['payment']['currency']);

        $gatewayPayment->setAmount($input['refund']['amount']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function checkErrorsAndThrowException(array $response)
    {
        $respCode = '';

        if (isset($response[Fields::STATUS]) === true)
        {
            $respCode = $response[Fields::STATUS];
        }

        if ($respCode !== Status::REFUND_SUCCESS)
        {
            throw new Exception\GatewayErrorException(
               $response, $this->gateway, $this->paymentId);
        }
    }

    public function createTerminal(array $input)
    {
        return $this->app['gateway']->call(BaseEntity::MOZART, Mozart\Action::CREATE_TERMINAL, $input, $this->getMode());
    }

    public function disableTerminal(array $input)
    {
        return $this->app['gateway']->call(BaseEntity::MOZART, Mozart\Action::DISABLE_TERMINAL, $input, $this->getMode());
    }

    public function enableTerminal(array $input)
    {
        return $this->app['gateway']->call(BaseEntity::MOZART, Mozart\Action::ENABLE_TERMINAL, $input, $this->getMode());
    }

    /**
     * We are generating and adding primary_id in callback becuase we cannot remove validtion on merchant reference
     * and we need qrcode in BQR payments flow
     */
    protected function addDefaultInputsIfRequired(& $input)
    {
        if (empty($input[Fields::PRIMARY_ID]) === true)
        {
            $input[Fields::PRIMARY_ID] = UniqueIdEntity::generateUniqueId();
        }
    }

}
