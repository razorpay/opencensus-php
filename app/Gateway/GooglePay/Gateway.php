<?php

namespace RZP\Gateway\GooglePay;

use RZP\Constants;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class Gateway extends Base\Gateway
{
    protected $gateway = Constants\Entity::MOZART;

    public $mozartClass = 'RZP\Gateway\Mozart\Gateway';

    const GATEWAY_NAME            = 'razorpayindia';
    const PAYMENT_TYPE            = 'CARD';
    const PAYMENT_TOKEN_TYPE      = 'PAYMENT_GATEWAY';
    const PRICE_STATUS            = 'FINAL';
    const SUPPORTED_CARD_NETWORKS = ['VISA', 'MASTERCARD'];

    protected $map = [
        'data'      => Entity::RAW,
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function authenticate(array $input)
    {
        $payment = $input['payment'];

        return $this->googlePayCardCoprotoData($payment);
    }

    public function omniPay(array $input)
    {
        $action = Action::OMNI_PAY;

        $action = camel_case($action);

        $class = $this->mozartClass;

        $gateway = new $class;

        $gateway->setMode($this->mode);

        return $gateway->call($action, $input);
    }

    public function preProcessServerCallback($data): array
    {
        $validator = new Validator();

        $validator->internalInputValidation('google_pay_card_authorization', $data);

        $this->trace->info(TraceCode::GATEWAY_DECRYPT_MOZART_REQUEST,
            [
                'mozart_request' => $data[RequestFields::TOKEN],
            ]);

        try
        {
            $response = $this->decryptData($data[RequestFields::TOKEN]);
        }
        catch (\Exception $e)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DECRYPTION_FAILED,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }

        $this->trace->info(TraceCode::GATEWAY_DECRYPT_MOZART_RESPONSE,
            [
                'mozart_response' => $response,
            ]);

        if (isset($response['data']['decryptedMessage']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DECRYPTION_FAILED,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }

        $this->validateRequest($validator, $response);

        $data[RequestFields::TOKEN] = $response['data']['decryptedMessage'];

        return $data;
    }

    protected function validateRequest($validator, $response)
    {
        $validator->internalInputValidation('google_pay_decrypted_message', $response['data']);

        $decryptedMessage = $response['data']['decryptedMessage'];

        $currentMilliSecond = millitime();

        if ($decryptedMessage[RequestFields::SIGNING_KEY_EXPIRY] <= $currentMilliSecond)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SIGNING_KEY_EXPIRED,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }

        if ($decryptedMessage[RequestFields::MESSAGE_EXPIRY] <= $currentMilliSecond)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MESSAGE_EXPIRED,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }
    }

    public function validateCallbackRequest($input, $payment)
    {
        if ((is_null($payment) === true) or ($payment->getAuthenticationGateway() !== Payment\Gateway::GOOGLE_PAY))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }

        // Convert Rupee to Paise.
        $inputAmount = $this->getFormattedAmount($input[RequestFields::AMOUNT]);

        if ($payment->getAmount() !== $inputAmount)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_AMOUNT_MISMATCH,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }

        if (($input[RequestFields::TOKEN][RequestFields::MERCHANT_ID] !== $payment->getMerchantId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_DOES_NOT_MATCH,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }

        if ($payment->getStatus() !== Payment\Status::CREATED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }
    }

    protected function getFormattedAmount($amount)
    {
        $amount = str_replace(',', '', $amount);

        $amountToBeFormatted = floatval($amount) * 100;

        return abs(intval(number_format($amountToBeFormatted, 2, '.', '')));
    }

    public function verify(array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'input'             => $input,
                'gateway'           => 'google_pay',
            ]);

        $response = [];

        (new Validator)->internalInputValidation('google_pay_card_verification', $input);

        $publicPaymentId = $input[RequestFields::PAYMENT_ID];

        $paymentId = $this->getUnsignedId($publicPaymentId);

        $paymentRepo = $this->app['repo']->payment;

        $mode = $paymentRepo->determineLiveOrTestModeForEntityWithNotNullGateway($paymentId, 'google_pay');

        if (is_null($mode) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }

        $this->app['basicauth']->setModeAndDbConnection($mode);

        try
        {
            $payment = $paymentRepo->findOrFail($paymentId);
        }
        catch (\Exception $e)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }

        if ($payment->getAuthenticationGateway() === Payment\Gateway::GOOGLE_PAY)
        {
            $status = $payment->getStatus();

            switch ($status)
            {
                case Payment\Status::CAPTURED:
                case Payment\Status::AUTHORIZED:
                case Payment\Status::REFUNDED:
                    $response['status'] = 'success';
                    break;
                case Payment\Status::CREATED:
                    $response['status'] = 'unknown';
                    break;
                case Payment\Status::FAILED:
                    $response['status'] = 'failed';
                    break;
            }

            $response['error'] = [
                'reason_code' => $payment->getReference13(),
            ];
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response'      => $response,
            ]);

        return $response;
    }

    protected function googlePayCardCoprotoData($payment)
    {
        $bundle = $this->getGooglePayBundle($payment);

        $data['method']  = 'sdk';
        $data['content'] = [ $bundle ];

        return $data;
    }


    public function getPaymentIdFromServerCallback($input)
    {
        if (isset($input[RequestFields::PAYMENT_ID]))
        {
            $id = $input[RequestFields::PAYMENT_ID];

            return $this->getUnsignedId($id);
        }

        return null;
    }

    public function getUnsignedId($signedId)
    {
        try
        {
            $paymentId = Payment\Entity::verifyIdAndStripSign($signedId);
        }
        catch (\Exception $e)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }

        return $paymentId;
    }

    public function decryptData($encryptedData)
    {
        $gatewayInput = [
            'gateway'   => Payment\Gateway::GOOGLE_PAY,
            'content'   => $encryptedData,
        ];

        $action = Action::DECRYPT;

        $action = camel_case($action);

        $class = $this->mozartClass;

        $gateway = new $class;

        $gateway->setMode($this->mode);

        return $gateway->call($action, $gatewayInput);
    }

    public function postProcessServerCallback($input)
    {
        $id = $this->getUnsignedId($input['gateway'][RequestFields::PAYMENT_ID]);

        $payment = (new Payment\Repository())->findOrFail($id);

        if ($payment->getStatus() !== Payment\Status::FAILED)
        {
            return ['status' => $payment->getStatus()];
        }

        throw new Exception\BadRequestException(
            $payment->getInternalErrorCode(),
            null,
            [
                'method'      => 'card',
                'application' => 'google_pay'
            ]);
    }

    protected function getGooglePayBundle($payment)
    {
        $gatewayParameters = [
            'gateway'              => self::GATEWAY_NAME,
            'gatewayMerchantId'    => $payment[Payment\Entity::MERCHANT_ID],
            'gatewayTransactionId' => $payment[Payment\Entity::PUBLIC_ID],
        ];

        $paymentDetail = [];
        $paymentDetail['type'] = self::PAYMENT_TYPE;
        $paymentDetail['parameters'] = [
            'allowedCardNetworks' => self::SUPPORTED_CARD_NETWORKS,
        ];
        $paymentDetail['tokenizationSpecification'] = [
            'type'       => self::PAYMENT_TOKEN_TYPE,
            'parameters' => $gatewayParameters,
        ];

        $transactionInfo = [
            'currencyCode'     => $payment[Payment\Entity::CURRENCY],
            // GooglePay expects the amount/price in Rupees.
            'totalPrice'       => number_format(floatval($payment[Payment\Entity::AMOUNT] / 100), 2, '.', ''),
            'totalPriceStatus' => self::PRICE_STATUS,
        ];

        return [
                'apiVersion'            => 2,
                'apiVersionMinor'       => 0,
                'allowedPaymentMethods' => [
                    $paymentDetail
                ],
                'transactionInfo'       => $transactionInfo,
            ];
    }
}
