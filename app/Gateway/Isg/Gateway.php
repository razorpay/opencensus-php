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
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Base\VerifyResult;
use RZP\Constants\Entity as BaseEntity;
use RZP\Models\QrCode\Entity as QrcodeEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;

class Gateway extends Base\Gateway
{
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
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyCallback($input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $this->sendVerifyCallbackRequest($verify);

        $response = $verify->verifyResponseContent;

        $this->checkStatusCodeAndDescription($response, $input);

        $this->checkVerifyCallbackResponse($response, $input);
    }
    
    protected function sendVerifyCallbackRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getCallbackRequestArray($input);

        $this->traceGatewayPaymentRequest($request,
                                          $input,
                                         TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response,
                                           $input,
                                          TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $responseArray = $this->jsonToArray($response->body);

        $verify->verifyResponseContent = $responseArray;
    }

    protected function getCallbackRequestArray($input)
    {
        $this->determineAndSetModeForQr($input[Field::PRIMARY_ID], $this->gateway);

        $terminal = $this->app['repo']->terminal->findByGatewayMpan($input[Field::MERCHANT_PAN], $this->gateway);

        $attributes = [
            Field::TRANSACTION_ID     => $input[Field::TRANSACTION_ID],
            Field::PRIMARY_ID         => $input[Field::PRIMARY_ID],
            Field::TERMINAL_ID        => $terminal->getGatewayTerminalId(),
            Field::TRANSACTION_DATE   => $this->getFormattedDate($input[Field::TRANSACTION_DATE_TIME],
                                                                'Y-m-d'),
            Field::TRANSACTION_AMOUNT => $input[Field::TRANSACTION_AMOUNT],
        ];

        $this->getStandardRequestArray($attributes);
    }

    protected function checkStatusCodeAndDescription($response, $input)
    {
        if ($response[Field::STATUS_CODE] !== Status::APPROVED)
        {
            if ($response[Field::STATUS_CODE] === Status::FALLBACK)
            {
                $gatewayErrorCode = "E005";

                $errorCode = ResponseCode::getErrorCode($gatewayErrorCode);

                $errorMessage = ResponseCode::getResponseCodeMessage($gatewayErrorCode);
            }
            else
            {
                $gatewayErrorCode = "E006";

                $errorCode = ResponseCode::getErrorCode($gatewayErrorCode);

                $errorMessage = ResponseCode::getResponseCodeMessage($gatewayErrorCode);
            }

                throw new Exception\GatewayErrorException(
                    $errorCode,
                    $gatewayErrorCode,
                    $errorMessage,
                    [
                        'callback_response'        => $input,
                        'verify_callback_response' => $response,
                        'gateway'                  => $this->gateway,
                    ]);
        }
    }

    protected function checkVerifyCallbackResponse($response, $input)
    {
        $this->checkMismatch($response[Field::TRANSACTION_AMOUNT],
                             $input[Field::TRANSACTION_AMOUNT],
                             $input,
                             $response,
                             'E001'
        );

        $this->checkMismatch($response[Field::MERCHANT_PAN],
                             $input[Field::MERCHANT_PAN],
                             $input,
                             $response,
                            'E003'
        );

        $expectedConsumerPan = $this->getDecryptedString($response[Field::CONSUMER_PAN]);

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
        $gatewayPayment = $verify->payment;

        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input, $gatewayPayment);

        $this->traceGatewayPaymentRequest($request,
                                          $input,
                                         TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response,
                                           $input,
                                          TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $responseArray = $this->jsonToArray($response->body);

        $verify->verifyResponseContent = $responseArray;
    }

    protected function getVerifyRequestArray($input, $gatewayPayment = null)
    {
        $attributes = [
            Field::TRANSACTION_ID     => $gatewayPayment[Entity::TRANSACTION_ID],
            Field::PRIMARY_ID         => $gatewayPayment[Entity::MERCHANT_REFERENCE],
            Field::TRANSACTION_AMOUNT => $this->getFormattedAmount($gatewayPayment[Entity::TRANSACTION_AMOUNT]),
            Field::TRANSACTION_DATE   => $this->getFormattedDate($gatewayPayment[Entity::TRANSACTION_DATE_TIME],
                                                                'Y-m-d'),
            Field::TERMINAL_ID        => $input[BaseEntity::TERMINAL][TerminalEntity::GATEWAY_TERMINAL_ID],
            ];

        return  $this->getStandardRequestArray($attributes);
    }

    protected function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }

    protected function getDecryptedString($string)
    {
        $masterKey = hex2bin($this->getSecret());

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return $aes->decryptString(hex2bin($string));
    }

    protected function getEncryptedString($string)
    {
        $masterKey = hex2bin($this->getGatewayInstance()->getSecret());

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return bin2hex($aes->encryptString($string));
    }

    protected function getQrData(array $input)
    {
        $this->verifyCallback($input);

        $customerCardNumber = $this->getDecryptedString($input[Field::CONSUMER_PAN]);

        $qrData = [
            BharatQr\GatewayResponseParams::AMOUNT                => $this->getIntegerFormattedAmount($input[Field::TRANSACTION_AMOUNT]),
            BharatQr\GatewayResponseParams::CARD_FIRST6           => substr($customerCardNumber, 0, 6),
            BharatQr\GatewayResponseParams::CARD_LAST4            => substr($customerCardNumber, 12, 4),
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::CARD,
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => $input[Field::PRIMARY_ID],
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => strval($input[Field::TRANSACTION_ID]),
            BharatQr\GatewayResponseParams::MPAN                  => $input[Field::MERCHANT_PAN],
];
        return $qrData;
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
            Entity::MERCHANT_REFERENCE    => $input[Field::PRIMARY_ID],
            Entity::MERCHANT_PAN          => $input[Field::MERCHANT_PAN],
            Entity::TRANSACTION_ID        => $input[Field::TRANSACTION_ID],
            Entity::TRANSACTION_DATE_TIME => $input[Field::TRANSACTION_DATE_TIME],
            Entity::AUTH_CODE             => $input[Field::AUTH_CODE],
            Entity::RRN                   => $input[Field::RRN],
            Entity::CONSUMER_PAN          => $input[Field::CONSUMER_PAN],
            Entity::STATUS_CODE           => $input[Field::STATUS_CODE],
            Entity::NOTIFICATION_REF_NO   => $input[Field::TRANSACTION_ID],
        ];

        if (isset($input[Field::SECONDARY_ID]) === true)
        {
            $attributes[Entity::SECONDARY_ID] = $input[Field::SECONDARY_ID];
        }

        if (isset($input[Field::TIP_AMOUNT]) === true)
        {
            $attributes[Field::TIP_AMOUNT] = $input[Field::TIP_AMOUNT];
        }

        $statusDescription = Status::getStatusCodeDescription($input[Field::STATUS_CODE]);

        $attributes[Entity::STATUS_DESC] = $statusDescription;

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

    protected function getFormattedDate(string $dateTime, $format)
    {
        $date = Carbon::parse($dateTime)->format($format);

        $date = str_replace("-", "", $date);

        return $date;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', ',');
    }

    protected function makeJsonResponse(array $content)
    {
        $json = json_encode($content);

        $response = \Response::make($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function determineAndSetModeForQr(string $merchantReference, string $gateway)
    {
        // We are not using verifyIdAndSilentlyStripSign here because in case
        // of unexpected payments reference id will be random and this will throw
        // exception.
        (new QrcodeEntity())->stripSignWithoutValidation($merchantReference);

        if ($gateway === Payment\Gateway::SHARP)
        {
            $mode = Mode::TEST;
        }
        else
        {
            $mode = $this->app['repo']->determineLiveOrTestModeForEntity($merchantReference, Constants\Entity::QR_CODE);

            $mode = $mode ?? Mode::LIVE;
        }

        $this->mode = $mode;

        $this->app['basicauth']->setModeAndDbConnection($mode);
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input = null,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        if (isset($input['payment']) === true)
        {
            $this->trace->info(
                $traceCode,
                [
                    'request'    => $request,
                    'gateway'    => $this->gateway,
                    'payment_id' => $input['payment']['id'],
                ]);
        }
        else
        {
            $this->trace->info(
                $traceCode,
                [
                    'request'    => $request,
                    'gateway'    => $this->gateway,
                ]);
        }
    }

    protected function traceGatewayPaymentResponse(
        $response,
        $input = null,
        $traceCode = TraceCode::GATEWAY_PAYMENT_RESPONSE)
    {
        if (isset($input['payment']) === true)
        {
            $this->trace->info(
                $traceCode,
                [
                    'response' => $response,
                    'gateway' => $this->gateway,
                    'payment_id' => $input['payment']['id'],
                ]);
        }
        else
        {
            $this->trace->info(
                $traceCode,
                [
                    'request' => $response,
                    'gateway' => $this->gateway,
                ]);
        }
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
            Field::TRANSACTION_ID      => $input[Field::TRANSACTION_ID],
            Field::NOTIFICATION_REF_NO => $input[Field::TRANSACTION_ID],
        ];

        if ($valid === true)
        {
            $attributes[Field::STATUS_CODE] = Status::APPROVED;

            $attributes[Field::STATUS_DESC] = Status::SUCCESS;
        }
        else if (isset($ex) === true)
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

        $response = $this->makeJsonResponse($attributes);

        return $response;
    }
}
