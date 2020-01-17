<?php

namespace RZP\Gateway\GooglePay;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\GooglePay\RequestFields;

class Gateway extends Base\Gateway
{
    protected $gateway = Constants\Entity::MOZART;

    public $mozartClass = 'RZP\Gateway\Mozart\Gateway';

    const GATEWAY_NAME            = 'razorpay';
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
        $response = $this->decryptData($data[RequestFields::TOKEN]);

        $data[RequestFields::TOKEN] = $response['data']['decryptedMessage'];

        return $data;
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

        $payment = (new Payment\Repository)->findOrFail($paymentId);

        if ($payment->getAuthenticationGateway() === Payment\Gateway::GOOGLE_PAY)
        {
            $status = $payment->getStatus();

            switch($status)
            {
                case Payment\Status::CAPTURED:
                    $response['STATUS'] = 'SUCCESS';
                    break;
                case Payment\Status::AUTHORIZED:
                case Payment\Status::CREATED:
                    $response['STATUS'] = 'IN PROCESS';
                    break;
                case Payment\Status::FAILED:
                    $response['STATUS'] = 'FAILED';
                    break;
                case Payment\Status::REFUNDED:
                    $response['STATUS'] = 'REFUNDED';
                    break;
            }
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

        if (($payment->getStatus() === Payment\Status::AUTHORIZED) or
            ($payment->getStatus() === Payment\Status::CAPTURED))
        {
            return ['status' => 'SUCCESS'];
        }

        return ['status' => 'FAILED'];
    }

    protected function getGooglePayBundle($payment)
    {
        $gatewayParameters = json_encode([
            'gateway'              => self::GATEWAY_NAME,
            'gatewayMerchantId'    => $payment[Payment\Entity::MERCHANT_ID],
            'gatewayTransactionId' => $payment[Payment\Entity::PUBLIC_ID],
        ]);

        $paymentDetail = [];
        $paymentDetail['type'] = self::PAYMENT_TYPE;
        $paymentDetail['parameters'] = json_encode([
            'allowedCardNetworks' => self::SUPPORTED_CARD_NETWORKS,
        ]);
        $paymentDetail['tokenizationSpecification'] = json_encode([
            'type'       => self::PAYMENT_TOKEN_TYPE,
            'parameters' => $gatewayParameters,
        ]);

        $transactionInfo = json_encode([
            'currencyCode'     => $payment[Payment\Entity::CURRENCY],
            // GooglePay expects the amount/price in Rupees.
            'totalPrice'       => number_format(floatval($payment[Payment\Entity::AMOUNT] / 100), 2, '.', ''),
            'totalPriceStatus' => self::PRICE_STATUS,
        ]);

        return json_encode(
            [
                'apiVersion' => '1.0',
                'allowedPaymentMethods' => [
                    json_encode($paymentDetail)
                ],
                'transactionInfo' => $transactionInfo,
            ]
        );
    }
}
