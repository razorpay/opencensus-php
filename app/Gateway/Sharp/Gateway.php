<?php

namespace RZP\Gateway\Sharp;

use Crypt;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'sharp';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $this->failIfRequired($input);

        if ($this->isSecondRecurringPaymentRequest($input))
        {
            return;
        }

        $content = array(
            'action'        => 'authorize',
            'amount'        => $input['payment']['amount'],
            'method'        => $input['payment']['method'],
            'payment_id'    => $input['payment']['id'],
            'callback_url'  => $input['callbackUrl'],
        );

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

            $request = true;
        }

        return $request;
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

        $this->verifyPaymentCreateResponse($input);

        $acquirerData = $this->getAcquirerData($input, null);

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

        if ($input['payment']['method'] === Payment\Method::NETBANKING)
        {
            $acquirer = [
                'reference1' => (string) random_integer(7)
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
    }

    protected function verifyPaymentCreateResponse($input)
    {
        if ((isset($input['gateway']['status']) === false) or
            ($input['gateway']['status'] !== 'authorized'))
        {
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
        assert ($mode === Mode::TEST);

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

        $request = array(
            'url' => $url,
            'method' => $method,
            'content' => $content,
        );

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
            ($input['token'] !== null) and
            ($input['token']->isRecurring() === true))
        {
            return true;
        }

        return false;
    }
}
