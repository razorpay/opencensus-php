<?php

namespace RZP\Gateway\Sharp;

use Crypt;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BharatQr;
use RZP\Gateway\Upi\Base as UpiBase;
use RZP\Models\Customer\Token;

class Gateway extends Base\Gateway
{
    const DEFAULT_PAYEE_VPA = 'upi@razopay';

    protected $gateway = 'sharp';

    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($this->isBharatQrPayment() === true)
        {
            return null;
        }

        $this->failIfRequired($input);

        if ($this->isSecondRecurringPaymentRequest($input))
        {
            if (($input['payment']['method'] === 'card') and
                ($input['card']['iin'] === '400666') and
                ($input['card']['last4'] === '0007'))
            {

                //Soft Decline for recurring payments
                if ($input['payment']['amount'] === 4444)
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE);
                }

                //Hard Decline for recurring payments
                if ($input['payment']['amount'] === 5555)
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST );
                }
            }

            return;
        }

        if ($input['payment'][Payment\Entity::AUTH_TYPE] == Payment\AuthType::SKIP)
        {
            return;
        }

        $content = [
            'action'            => 'authorize',
            'amount'            => $input['payment']['amount'],
            'method'            => $input['payment']['method'],
            'payment_id'        => $input['payment']['id'],
            'callback_url'      => $input['callbackUrl'],
            // This need to be 0 because if it's `false`, frontend converts
            // to "false" and Sharp server treats "false" as `true`.
            'recurring'         => 0,
        ];

        if (isset($input['payment']['auth_type']) === true)
        {
            $content['auth_type'] = $input['payment']['auth_type'];
        }

        if (isset($input['payment']['recurring']) === true)
        {
            $content['recurring'] = boolval($input['payment']['recurring']) ? 1 : 0;
        }

        if ($content['method'] === 'card')
        {
            $content['card_number'] = $input['card']['number'];
        }

        if ($this->isEnrolled($content) === false)
        {
            return;
        }

        $request = $this->getRequestArray($content, $input);

        if ($input['payment']['method'] === Payment\Method::UPI)
        {
            $this->processTestUpiPayment($input['payment']);

            if ((isset($input['upi']['flow']) === true) and
                ($input['upi']['flow'] === 'intent'))
            {
                return $this->getIntentRequest($input);
            }

            $request = true;
        }

        return $request;
    }

    /**
     * Takes in S2S request as a body string
     * and returns the parsed response as an array
     *
     * @param  array $body Request body
     *
     * @param bool    $isBharatQr
     *
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    public function preProcessServerCallback($body, $isBharatQr = false): array
    {
        $response = $body;

        if ($isBharatQr === true)
        {
            $response = $this->getQrData($body);
        }

        return $response;
    }

    protected function getQrData(array $input)
    {
        (new Validator)->validateInput('test_bharatqr_payment', $input);

        $qrData = [
            BharatQr\GatewayResponseParams::AMOUNT                => $input[Fields::AMOUNT],
            BharatQr\GatewayResponseParams::METHOD                => $input[Fields::METHOD],
            BharatQr\GatewayResponseParams::GATEWAY_MERCHANT_ID   => Constants::SHARP_MERCHANT_ID,
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => $input[Fields::REFERENCE],
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => random_alphanum_string(10),
            BharatQr\GatewayResponseParams::SENDER_NAME           => 'Razorpay',
        ];

        switch ($input[Fields::METHOD])
        {
            case Payment\Method::CARD:
                $qrData[BharatQr\GatewayResponseParams::CARD_FIRST6] = Constants::CARD_FIRST_SIX;
                $qrData[BharatQr\GatewayResponseParams::CARD_LAST4]  = Constants::CARD_LAST_FOUR;
                break;

            case Payment\Method::UPI:
                $qrData[BharatQr\GatewayResponseParams::VPA] = Constants::BQR_VPA;
                break;

            default:
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD);
        }

        return [
            'callback_data' => $input,
            'qr_data'       => $qrData
        ];
    }

    public function getBharatQrResponse(bool $valid, $gatewayInput = null, $exception = null)
    {
        if ($exception !== null)
        {
            throw $exception;
        }

        //
        // This is a fairly useless response. But it's better for the
        // merchant than the one in base gateway, and keeping in empty
        // means we can add stuff later without breaking compatibility.
        //
        return [];
    }

    protected function getIntentRequest($input)
    {
        $content = [
            UpiBase\IntentParams::PAYEE_ADDRESS => self::DEFAULT_PAYEE_VPA,
            UpiBase\IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '', $input['merchant']->getFilteredDba()),
            UpiBase\IntentParams::TXN_REF_ID    => str_random(15),
            UpiBase\IntentParams::TXN_NOTE      => 'razorpay',
            UpiBase\IntentParams::TXN_AMOUNT    => $input['payment']['amount'] / 100,
            UpiBase\IntentParams::TXN_CURRENCY  => 'INR',
            UpiBase\IntentParams::MCC           => '5411',
        ];

        $query = str_replace(' ', '', urldecode(http_build_query($content)));

        return ['data' => ['intent_url' => 'upi://pay?' . $query]];
    }

    protected function processTestUpiPayment($payment)
    {
        $server = $this->app['gateway']->server('sharp');

        $input = $server->s2sRequestContent($payment);

        $paymentId = Payment\Entity::getSignedId($payment['id']);

        try
        {
            (new Payment\Service)->s2scallback($paymentId, $input);
        }
        catch (Exception\GatewayErrorException $ex)
        {
            $this->trace->info(
                TraceCode::PAYMENT_FAILED,
                [
                    'payment_id'        => $paymentId,
                    'message'           => $ex->getMessage(),
                ]
            );
        }
    }

    public function checkExistingUser(array $input)
    {
    }

    public function otpGenerate(array $input)
    {
        return $this->getOtpSubmitRequest($input);
    }

    public function topup(array $input)
    {
        return $this->authorize($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if (($input['payment']['method'] === 'card') and
            ($input['card']['iin'] === '501010') and
            ($input['card']['last4'] === '1015'))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE);
        }

        if ((isset($input['payment']['recurring']) === true) and
            ($input['payment']['recurring'] === true) and
            ($input['payment']['method'] === 'card') and
            ($input['card']['iin'] === '400666') and
            ($input['card']['last4'] === '0007'))
        {

            //Soft Decline for recurring payments
            if ($input['payment']['amount'] === 4444)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE);
            }

            //Hard Decline for recurring payments
            if ($input['payment']['amount'] === 5555)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST );
            }
        }

        $this->verifyPaymentCreateResponse($input);

        $acquirerData = $this->getAcquirerData($input, null);

        $this->addRecurringDataIfApplicable($input, $acquirerData);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function callbackOtpSubmit(array $input)
    {
        switch ($input['gateway']['otp'])
        {
            case '100000':
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE);

            case '200000':
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT);

            case '300000':
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED);

            case '400000':
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED);

            case '500000':
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST);
        }

        return [];
    }

    protected function getAcquirerData($input, $gatewayPayment)
    {
        $acquirer = [];

        if (($input['payment']['method'] === Payment\Method::NETBANKING) or
            ($input['payment']['method'] === Payment\Method::EMANDATE))
        {
            $acquirer = [
                'reference1' => (string) random_integer(7)
            ];
        }

        if (($input['payment']['method'] === Payment\Method::UPI) and
            (isset($input['gateway']['vpa']) === true))
        {
            $acquirer = [
                Payment\Entity::VPA => $input['gateway']['vpa'],
                Payment\Entity::REFERENCE16 => (string) random_integer(12),
            ];
        }

        if (($input['payment']['method'] === Payment\Method::CARD) or
            ($input['payment']['method'] === Payment\Method::EMI))
        {
            $acquirer = [
                'reference2' => (string) random_integer(6)
            ];
        }

        return [
            'acquirer' => $acquirer
        ];
    }

    public function capture(array $input)
    {
        parent::capture($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        if ((isset($input['payment'][Payment\Entity::GATEWAY]) === true) and
            (Payment\Gateway::isScroogeGatewayAndMerchant($input['payment'][Payment\Entity::GATEWAY]) === true))
        {
            return $this->getScroogeResponse($input, 'refund');
        }
    }

    public function validateVpa(array $input)
    {
        $vpa = $input['vpa'];

        if ($vpa === 'invalidvpa@razorpay')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA);
        }
    }

    protected function verifyPaymentCreateResponse($input)
    {
        if ((isset($input['gateway']['status']) === false) or
            ($input['gateway']['status'] !== 'authorized'))
        {
            if ($input['gateway']['status'] === 'gateway_down')
            {
                throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_FATAL_ERROR);
            }

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function isEnrolled($content)
    {
        $content['action'] = 'enroll';

        $server = new Server;

        $content = $server->action($content);

        return ($content !== 'N');
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attributes['TxnRefNo']);

        $payment->fill($attributes);

        $payment->saveOrFail();

        return $payment;
    }

    public function setMode($mode)
    {
        assertTrue ($mode === Mode::TEST);

        parent::setMode($mode);
    }

    protected function getRequestArray($content, $input)
    {
        $url = $this->route->getUrlWithPublicAuth('mock_sharp_payment_post');

        $method = 'post';

        if ($input['payment']['method'] === 'card')
        {
            $content['card_number'] = $this->encryptCardNumber($input['card']['number']);
            $content['encrypt'] = '1';
        }

        if (($input['payment']['method'] === 'card') and
            ($input['card']['number'] === '4111111111111111'))
        {
            $method = 'get';
            $url = $url . '&' . http_build_query($content);
            $content = [];
        }

        $request = [
            'url' => $url,
            'method' => $method,
            'content' => $content,
        ];

        return $request;
    }

    protected function encryptCardNumber($number)
    {
        return Crypt::encrypt($number);
    }

    protected function decryptCardNumber($encryptedCard)
    {
        return Crypt::decrypt($encryptedCard);
    }

    protected function isSecondRecurringPaymentRequest($input)
    {
        if (($input['payment']['recurring'] === true) and
            ($input['payment']['recurring_type'] === 'auto'))
        {
            return true;
        }

        return false;
    }

    protected function addRecurringDataIfApplicable(array $input, array & $acquirerData)
    {
        if (($input['payment']['recurring'] === true) and
            ($input['payment']['method'] === Payment\Method::EMANDATE))
        {
            $recurringData = $this->getRecurringData($input['gateway']);

            $acquirerData = array_merge($acquirerData, $recurringData);
        }
    }

    protected function getRecurringData($gatewayInput)
    {
        $recurringStatus = Token\RecurringStatus::CONFIRMED;

        if ((isset($gatewayInput['token_recurring_status']) === false) or
            ($gatewayInput['token_recurring_status'] !== Token\RecurringStatus::CONFIRMED))
        {
            $recurringStatus = Token\RecurringStatus::REJECTED;
        }

        $recurringData[Token\Entity::RECURRING_STATUS] = $recurringStatus;

        if ($recurringStatus === Token\RecurringStatus::REJECTED)
        {
            $recurringData[Token\Entity::RECURRING_FAILURE_REASON] = 'Rejected by bank';
        }

        return $recurringData;
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        if ((isset($input['payment'][Payment\Entity::GATEWAY]) === true) and
            (Payment\Gateway::isScroogeGatewayAndMerchant($input['payment'][Payment\Entity::GATEWAY]) === true))
        {
            return $this->getScroogeResponse($input, 'verify');
        }

        return false;
    }

    protected function getGatewayResponse(int $amount, int $attempts)
    {
        $response = [
            'amount'                => $amount,
            'action'                => 'refund',
            'received'              => true,
            'response_code'         => 300,
            'gateway_refund_id'     => '',
            'gateway_merchant_id'   => '10000000000000'
        ];

        switch ($amount)
        {
            // Validation failure
            case ($amount === 8888):

                $response['result']         = 'Your account does not have enough credits '.
                                                'to carry out the refund operation.';
                $response['status_code']    = ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS;
                break;

            // Hard failure
            case ($amount === 4444):

                $response['result']         = 'Payment failed because of risk score.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_DENIED_BY_RISK;
                break;

             // Soft failure
            case (($amount === 5555) or ($amount === 6666)):

                $response['result']         = 'Your account does not have enough balance to '.
                                                'carry out the refund operation.';
                $response['status_code']    = ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE;
                break;

            // Soft failure
            case ($amount === 2111):

                $response['result']         = 'Refund failed';
                $response['status_code']    = ErrorCode::BAD_REQUEST_REFUND_FAILED;
                break;

            // Soft failure
            case ($amount === 3111):

                $response['result']         = 'Gateway response code mapping not found.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE;
                break;

            // Soft failure
            case ($amount === 4111):

                $response['result']         = 'Gateway system is busy, please retry.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_SYSTEM_BUSY;
                break;

             // Request failure
            case ((($amount === 7777) or ($amount === 9999)) and ((int) $attempts === 0)):
                $response['result']         = 'Request Timeout. Please try again.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT;
                break;

            default:
                $response = [
                    'result'                => 'REFUND SUCCESSFUL',
                    'action'                => 'refund',
                    'amount'                => $amount,
                    'received'              => true,
                    'response_code'         => 200,
                    'status_code'           => 'REFUND_SUCCESSFUL',
                    'gateway_refund_id'     => '224343435454',
                    'gateway_merchant_id'   => '10000000000000'
                ];
        }

        return $response;
    }

    protected function getVerifyGatewayResponse(int $amount, int $amountRefunded, int $attempts)
    {
        $response = [
            'amount'                => $amount,
            'action'                => 'verify',
            'received'              => true,
            'response_code'         => 200,
            'gateway_refund_id'     => '',
            'gateway_merchant_id'   => '10000000000000'
        ];

        if (empty($amount) === true)
        {
            $response['result']         = 'Refund Failed';
            $response['status_code']    = ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT;

            return $response;
        }

        switch ($amount)
        {
            // intermediate failure - no retry - waiting for recon
            case ($amount === 1111):
                $response['result']         = 'Gateway verify refund unexpected response';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_UNEXPECTED_STATUS;

                break;

            // request failure
            case ($amount === 2222):
                $response['result']         = 'Request Timeout. Please try again.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT;

                break;

            case (($amount === 8888) and ((int) $attempts === 0) and ($amount === $amountRefunded)):
                $response['result']         = 'Request Timeout. Please try again.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT;

                break;

            // Hard failure. Added amount check to mock the case when refund for amount 5000 of payment 5000
            // will be failed at first time but if full 5000 is refunded, it will be successful
            case (($amount === 4444) and ($amount === $amountRefunded)):
                $response['result']         = 'Payment failed because of risk score.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_DENIED_BY_RISK;

                break;

            case (($amount === 4444) and ($amount !== $amountRefunded)):
                $response['result']         = 'REFUND_SUCCESSFUL';
                $response['status_code']    = 'REFUND_SUCCESSFUL';

                break;

            case (($amount === 5555) and ($amount == $amountRefunded)):
                $response['result']         = 'Refund Failed';
                $response['status_code']    = ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE;

                break;

            case (($amount === 5555) and ($amount != $amountRefunded)):
                $response['result']         = 'REFUND_SUCCESSFUL';
                $response['status_code']    = 'REFUND_SUCCESSFUL';

                break;

            // Soft failure. Added amount check to mock the case when verify for refund of 6666 of payment 7000
            // will be failed at first time but if full 7000 is refunded, it will be successful
            case (($amount === 6666) and ($amount !== $amountRefunded)):
                $response['result']         = 'REFUND_SUCCESSFUL';
                $response['status_code']    = 'REFUND_SUCCESSFUL';

                break;

            default:
                $response['result']         = 'Refund Failed';
                $response['status_code']    = ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT;
        }

        return $response;
    }

    protected function getScroogeResponse(array $input, string $action)
    {
        if ($action === 'refund')
        {
            $gatewayResponse['gateway_response'] = $this->getGatewayResponse($input['refund']['amount'], $input['refund']['attempts']);
        }
        else
        {
            $gatewayResponse['gateway_verify_response'] = $this->getVerifyGatewayResponse($input['refund']['amount'],
                                                                $input['payment']['amount_refunded'], $input['refund']['attempts']);
        }

        if (($action === 'refund') and ($gatewayResponse['gateway_response']['status_code'] !== 'REFUND_SUCCESSFUL'))
        {
            throw new Exception\GatewayErrorException($gatewayResponse['gateway_response']['status_code'],
                $gatewayResponse['gateway_response']['status_code'],
                $gatewayResponse['gateway_response']['result'],
                [
                    'gateway_response'      => json_encode($gatewayResponse['gateway_response']),
                    'gateway_keys'          => [
                        'gateway_refund_id'     => $gatewayResponse['gateway_response']['gateway_refund_id'],
                        'gateway_merchant_id'   => $gatewayResponse['gateway_response']['gateway_merchant_id']
                    ],
                    'refund_gateway'        => 'sharp',
                ]);
        }

        $gatewayResponseFinal = (($action === 'verify') ?
                            $gatewayResponse['gateway_verify_response'] :
                            $gatewayResponse['gateway_response']);


        $response = [
            'gateway_response'          => json_encode($gatewayResponse['gateway_response'] ?? ''),
            'gateway_verify_response'   => json_encode($gatewayResponse['gateway_verify_response'] ?? ''),
            'gateway_keys'              => [
                    'gateway_refund_id'     => $gatewayResponseFinal['gateway_refund_id'],
                    'gateway_merchant_id'   => $gatewayResponseFinal['gateway_merchant_id']
            ],
            'refund_gateway'            => 'sharp',
        ];

        if ($action === 'verify')
        {
            $response['success']        = ($gatewayResponseFinal['status_code'] === 'REFUND_SUCCESSFUL');
            $response['status_code']    = $gatewayResponseFinal['status_code'];
        }

        return $response;
    }
}
