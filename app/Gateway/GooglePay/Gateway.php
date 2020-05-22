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

        $validator->validateInput('google_pay_card_authorization', $data);

        $this->trace->info(TraceCode::GATEWAY_DECRYPT_MOZART_REQUEST,
            [
                'mozart_request' => $data[RequestFields::TOKEN],
            ]);

        $response = $this->decryptData($data[RequestFields::TOKEN]);

        $this->trace->info(TraceCode::GATEWAY_DECRYPT_MOZART_RESPONSE,
            [
                'mozart_response' => $response,
            ]);

        if (isset($response['data']['decryptedMessage']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DECRYPTION_FAILED);
        }

        $this->validateRequest($validator, $response);

        $data[RequestFields::TOKEN] = $response['data']['decryptedMessage'];

        return $data;
    }

    protected function validateRequest($validator, $response)
    {
        $validator->validateInput('google_pay_decrypted_message', $response['data']);

        $decryptedMessage = $response['data']['decryptedMessage'];

        $currentMilliSecond = millitime();

        if ($decryptedMessage[RequestFields::SIGNING_KEY_EXPIRY] <= $currentMilliSecond)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SIGNING_KEY_EXPIRED);
        }

        if ($decryptedMessage[RequestFields::MESSAGE_EXPIRY] <= $currentMilliSecond)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MESSAGE_EXPIRED);
        }
    }

    public function validateCallbackRequest($input, $payment)
    {
        if (is_null($payment) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND);
        }

        // Convert Rupee to Paise.
        $inputAmount = $this->getFormattedAmount($input[RequestFields::AMOUNT]);

        if ($payment->getAmount() !== $inputAmount)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_AMOUNT_MISMATCH);
        }

        if (($input[RequestFields::TOKEN][RequestFields::MERCHANT_ID] !== $payment->getMerchantId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_DOES_NOT_MATCH);
        }

        if ($payment->getStatus() !== Payment\Status::CREATED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);
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

        (new Validator)->validateInput('google_pay_card_verification', $input);

        $publicPaymentId = $input[RequestFields::PAYMENT_ID];

        $paymentId = $this->getUnsignedId($publicPaymentId);

        try {
            $payment = (new Payment\Repository)->findOrFail($paymentId);
        }
        catch (\Exception $e)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND);
        }

        if ($payment->getAuthenticationGateway() === Payment\Gateway::GOOGLE_PAY)
        {
            $response['status'] = $payment->getStatus();
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND);
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
        $paymentId = Payment\Entity::verifyIdAndStripSign($signedId);

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

        throw new Exception\BadRequestException($payment->getErrorCode());
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
