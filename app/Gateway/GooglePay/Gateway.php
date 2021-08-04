<?php

namespace RZP\Gateway\GooglePay;

use RZP\Constants;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Card\Network;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Method;
use RZP\Gateway\Upi\Base\IntentParams;

class Gateway extends Base\Gateway
{
    protected $gateway = Constants\Entity::MOZART;

    public $mozartClass = 'RZP\Gateway\Mozart\Gateway';

    const GATEWAY_NAME            = 'razorpayindia';
    const PAYMENT_TYPE            = 'CARD';
    const PAYMENT_TOKEN_TYPE      = 'PAYMENT_GATEWAY';
    const PRICE_STATUS            = 'FINAL';
    const SUPPORTED_CARD_NETWORKS = [Network::VISA, Network::MC];

    protected $map = [
        'data'      => Entity::RAW,
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function authenticate(array $input)
    {
        return $this->googlePayCoprotoData($input);
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
        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_REQUEST,
            [
                'request'     => $data,
                'application' => 'google_pay'
            ]);

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

        $decryptedResponseTrace = $this->getDecryptedResponseTrace($response);

        $this->trace->info(TraceCode::GATEWAY_DECRYPT_MOZART_RESPONSE,
            [
                'mozart_response' => $decryptedResponseTrace,
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

    protected function getDecryptedResponseTrace($data)
    {
        $traceData = $data;

        unset($traceData['data']['_raw']);

        $traceData['data']['decryptedMessage']['paymentMethodDetails']['pan'] = '*redacted*';
        $traceData['data']['decryptedMessage']['paymentMethodDetails']['3dsCryptogram'] = '*redacted*';

        return $traceData;
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

        $paymentId = $input[RequestFields::PAYMENT_ID];

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

    protected function googlePayCoprotoData($input)
    {
        $bundle = $this->getGooglePayBundle($input);

        $this->logBundle($bundle);

        $data['method']  = 'sdk';
        $data['content'] = [ $bundle ];

        return $data;
    }


    public function getPaymentIdFromServerCallback($input)
    {
        if (isset($input[RequestFields::PAYMENT_ID]))
        {
            $id = $input[RequestFields::PAYMENT_ID];

            return $id;
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
        $id = ($input['gateway'][RequestFields::PAYMENT_ID]);

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

    protected function getGooglePayBundle($input)
    {
        $payment = $input['payment'];

        $paymentDetails = [];

        $methods = $this->fetchMethod($payment);

        foreach ($methods as $method)
        {
            $paymentDetail = [
                'type'                          => strtoupper($method),
                'parameters'                    => $this->fetchMethodParameters($method, $input),
                'tokenizationSpecification'     => $this->fetchTokenizationSpecifications($method, $payment),
            ];

            array_push($paymentDetails, $paymentDetail);
        }

        $transactionInfo = [
            'currencyCode'     => $payment[Payment\Entity::CURRENCY],
            // GooglePay expects the amount/price in Rupees.
            'totalPrice'       => number_format(floatval($payment[Payment\Entity::AMOUNT] / 100), 2, '.', ''),
            'totalPriceStatus' => self::PRICE_STATUS,
        ];

        return [
                'apiVersion'            => 2,
                'apiVersionMinor'       => 0,
                'allowedPaymentMethods' => $paymentDetails,
                'transactionInfo'       => $transactionInfo,
            ];
    }

    public function fetchPaymentMethod($data, $callbackGateway)
    {
        if(isset($data) === false)
        {
            return;
        }

        if (isset($data[RequestFields::CARD_TYPE]) === true and
            isset($data[RequestFields::CARD_NETWORK]) === true)
        {
            return Method::CARD;
        }
        // If callback payload is not for cards, and callback gateway is UPI intent supported,
        // payment method will be UPI.
        elseif (Payment\Gateway::isUpiIntentFlowSupported($callbackGateway) === true)
        {
            return Method::UPI;
        }
    }
  
    protected function fetchMethodParameters($method, $input)
    {
        $payment = $input['payment'];

        $parameters = [];

        switch ($method)
        {
            case Payment\Method::UPI:

                $upiParams = $input['upi']['params'];

                $parameters = [
                    'payeeVpa'                  =>  $upiParams[IntentParams::PAYEE_ADDRESS],
                    'payeeName'                 =>  $upiParams[IntentParams::PAYEE_NAME],
                    'referenceUrl'              =>  $payment->merchant->getWebsite(),
                    'mcc'                       =>  $upiParams[IntentParams::MCC],
                    'transactionReferenceId'    =>  $upiParams[IntentParams::TXN_REF_ID],
                ];

                break;

            case Payment\Method::CARD:

                $parameters = [
                    'allowedCardNetworks'       =>  $this->fetchAllowedCardNetworks($payment),
                ];

                break;
        }

        return $parameters;
    }

    protected function fetchTokenizationSpecifications($method, $payment)
    {
        $tokenizationSpecifications = [];

        switch ($method)
        {
            case Payment\Method::UPI:

                $tokenizationSpecifications = [
                    'type'                      =>  "DIRECT"
                ];

                break;

            case Payment\Method::CARD:

                $gatewayParameters = [
                    'gateway'              => self::GATEWAY_NAME,
                    'gatewayMerchantId'    => $payment[Payment\Entity::MERCHANT_ID],
                    'gatewayTransactionId' => $payment[Payment\Entity::ID],
                ];

                $tokenizationSpecifications = [
                    'type'                      =>  self::PAYMENT_TOKEN_TYPE,
                    'parameters'                =>  $gatewayParameters,
                ];

                break;
        }

        return $tokenizationSpecifications;
    }

    protected function fetchMethod($payment)
    {
        $methods = [];

        if ($payment['method'] === Payment\Method::UNSELECTED)
        {
            $methods = $payment->fetchPaymentMethods();
        }
        else
        {
            array_push($methods, $payment['method']);
        }

        return $methods;
    }

    /**
     * @param $payment
     * @return array
     */
    protected function fetchAllowedCardNetworks($payment): array
    {
        $supportedCardNetworkNames = Network::getFullNames(self::SUPPORTED_CARD_NETWORKS);

        if ($payment['method'] === Payment\Method::UNSELECTED)
        {
            $googlePayCardNetworkNames = Network::getFullNames($payment->getGooglePayCardNetworks());

            $supportedCardNetworkNames = array_intersect($googlePayCardNetworkNames, $supportedCardNetworkNames);
        }

        $supportedCardNetworkNames = array_map( 'strtoupper', $supportedCardNetworkNames);

        return $supportedCardNetworkNames;
    }

    protected function logBundle($bundle)
    {
        $this->trace->info(
            TraceCode::GOOGLE_PAY_BUNDLE,
            [
                'bundle'    => $bundle,
            ]);
    }
}
