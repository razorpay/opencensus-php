<?php

namespace RZP\Gateway\Mozart;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Mozart\Entity as MozartEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;


class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'mozart';

    protected $map = [
        'data'      => Entity::RAW,
    ];

    public function authorize(array $input)
    {
        parent::action($input, Action::PAY_INIT);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_AUTHORIZE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $attributes = $this->getMappedAttributes($response);

        $this->gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input, Action::AUTHORIZE);

        if ($input['payment']['method'] === 'upi')
        {
            return [
                'data'   => [
                    Payment\Entity::VPA => $input['terminal']['gateway_merchant_id2']
                ]
            ];
        }

        return $response['next']['redirect'] ?? null;
    }

    public function otpGenerate(array $input)
    {
        return $this->authorize($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->verifyOtpAttempts($input['payment']);

        return $this->callback($input);
    }

    public function callback(array $input)
    {
        parent::action($input, Action::PAY_VERIFY);

        if ($this->fullyEncryptedFlow($input['payment']['gateway']) === false)
        {
            $gateway = $input['gateway'];

            $traceRes = $this->getRedactedData($gateway);

            $this->traceGatewayPaymentRequest($traceRes, $input, TraceCode::PAYMENT_CALLBACK_REQUEST );

            unset($input['gateway']);

            $input['gateway']['redirect'] = $gateway;

            $request = $this->getMozartRequestArray($input);

            $traceReq = [
                'method' => $request['method'],
                'url' => $request['url'],
            ];

            $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_PAYMENT_REQUEST);

            $response = $this->sendGatewayRequest($request);

            $traceRes = $this->getRedactedData($response);

            $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_PAYMENT_RESPONSE);
        }
        else
        {
            $response = json_decode($input['gateway']['preProcessServerCallbackResponse'], true);
        }


        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->gatewayPayment = $this->updateGatewayPaymentEntityWithAction(
            $gatewayPayment,
            $response,
            true,
            Action::AUTHORIZE
        );

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $this->runCallbackValidationsIfApplicable($input, $response);

        $gatewayName = $input['payment']['gateway'];

        if ($this->immediateVerifyApplicable($gatewayName) === true)
        {
            $this->verifyCallback($input);
        }

        return $this->getResponseData($input, $response);
    }

    public function immediateVerifyApplicable($gatewayName)
    {
        $immediateVerificationGateways = [
            Payment\Gateway::WALLET_PHONEPE,
            Payment\Gateway::BAJAJFINSERV,
            Payment\Gateway::NETBANKING_YESB,
            Payment\Gateway::NETBANKING_SIB,
        ];

        return in_array($gatewayName, $immediateVerificationGateways);
    }

    public function preProcessServerCallback($input, $gateway = null): array
    {
        switch ($gateway)
        {
            case 'upi_airtel':
                return json_decode($input[0], true);
            case Payment\Gateway::NETBANKING_YESB:
                return $this->preProcessServerCallbackForYesb($input);
            default :
                throw new Exception\LogicException(
                    'Invalid gateway passed for prcessing S2S callback');
        }
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        switch ($gateway)
        {
            case 'upi_airtel':
                return $response[UpiAirtelResponseFields::PAYMENT_ID];
            case Payment\Gateway::NETBANKING_YESB:
                return $response['data']['paymentId'];
            default :
                throw new Exception\LogicException(
                    'Invalid gateway passed for getting payment id from S2S callback');
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $this->gatewayPayment = $this->createGatewayRefundEntity($attributes, $input, $this->action);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $request = $this->getMozartRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);
    }

    public function verifyRefund(array $input)
    {
        $this->input = $input;
        $this->action = Action::VERIFY_REFUND;

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_REFUND_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE);
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

        $verifyResponseContent = $this->sendPaymentVerifyRequest($verify);

        //
        // If the status in callback and verify does not match
        //
        if ($verifyResponseContent['success'] !== true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                $verifyResponseContent['error']['gateway_error_code'] ?? 'gateway_error_code',
                $verifyResponseContent['error']['gateway_error_description'] ?? 'gateway_error_desc',
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $input['payment']['gateway']
                ]);
        }
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $verify->verifyResponseContent = $response;

        $verify->verifyResponse = null;

        $verify->verifyResponseBody = null;

        return $verify->verifyResponseContent;
    }

    protected function verifyPayment($verify)
    {
        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        $verify->gatewaySuccess = $content['success'];

        $this->checkApiSuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        if ($input['payment']['gateway'] === Payment\Gateway::NETBANKING_SIB)
        {
            $content = $this->updateBankPaymentIdFromResponse($content, $verify->payment);
        }

        $this->updateGatewayPaymentEntityWithAction($verify->payment, $content, true, Action::AUTHORIZE);

        return $verify->status;
    }

    protected function getMozartRequestArray($input)
    {
        if (($input['terminal'] instanceof TerminalEntity) === true)
        {
            $input['terminal'] = $input['terminal']->toArrayWithPassword();
        }

        $prevStepName = $this->getPreviousStepName($input['payment']['gateway']);

        $prevStepDB = $this->getPreviousStepForDB($input['payment']['gateway']);

        if ($prevStepName != null)
        {
            $input['gateway'][$prevStepName] = $this->getPreviousData($input, $prevStepDB);
        }

        $content['entities'] = $input;

        $baseUrl = $this->app['config']->get('applications.mozart.url');

        $url =  $baseUrl . 'payments/' . $input['payment']['gateway'] . '/v1/' . $this->action;

        $authentication = [
            'api',
            $this->app['config']->get('applications.mozart.password')
        ];

        return [
            'url' => $url,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Task-ID'    => $this->app['request']->getTaskId(),
            ],
            'content' => json_encode($content),
            'options' => [
                'auth' => $authentication
            ]
        ];
    }

    protected function getPreviousStepName($gateway)
    {
        $previousActionForStep = [
            Payment\Gateway::BAJAJFINSERV => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY => Action::PAY_VERIFY,
                Action::REFUND => Action::PAY_VERIFY,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
            Payment\Gateway::NETBANKING_YESB => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => Action::PAY_VERIFY,
            ],
            Payment\Gateway::WALLET_PHONEPE => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => null,
                Action::REFUND => null,
                Action::VERIFY_REFUND => null,
            ],
            Payment\Gateway::UPI_AIRTEL => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => Action::PAY_VERIFY,
                Action::REFUND => Action::PAY_VERIFY,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
            Payment\Gateway::NETBANKING_SIB => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY => Action::PAY_VERIFY,
            ],
        ];

        return $previousActionForStep[$gateway][$this->action];
    }

    protected function getPreviousStepForDB($gateway)
    {
        $previousActionForData = [
            Payment\Gateway::BAJAJFINSERV => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY => Action::AUTHORIZE,
                Action::REFUND => Action::AUTHORIZE,
                Action::VERIFY_REFUND => Action::REFUND,
            ],

            Payment\Gateway::NETBANKING_YESB => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => Action::AUTHORIZE,
            ],

            Payment\Gateway::WALLET_PHONEPE => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => null,
                Action::REFUND => null,
                Action::VERIFY_REFUND => null,
            ],

            Payment\Gateway::UPI_AIRTEL => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => Action::AUTHORIZE,
                Action::REFUND => Action::AUTHORIZE,
                Action::VERIFY_REFUND => Action::REFUND,
            ],

            Payment\Gateway::NETBANKING_SIB => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY     => Action::AUTHORIZE,
            ],
        ];

        return $previousActionForData[$gateway][$this->action];
    }

    protected function getPreviousData($input, $prevActionForData)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], $prevActionForData);

        $jsonRaw = json_decode($gatewayPayment['raw'], true);

        return $jsonRaw;
    }

    protected function sendGatewayRequest($request)
    {
        $response = parent::sendGatewayRequest($request);

        return $this->jsonToArray($response->body, true);
    }

    protected function getRedactedData($data)
    {
        unset($data['data']['Key']);

        unset($data['data']['enqinfo']['0']['Key']);

        unset($data['data']['enqinfo']['0']['MOBILENO']);

        unset($data['data']['MobileNo']);

        unset($data['data']['valkey']);

        unset($data['otp']);

        unset($data['data']['_raw']);

        return $data;
    }

    protected function createGatewayPaymentEntity($attributes, $input, $action)
    {
        $redactedRaw = $this->getRedactedData($attributes['raw']);
        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $amount    = $input['payment']['amount'];
        $gateway   = $input['payment']['gateway'];

        $gatewayPayment->setAction($action);
        $gatewayPayment->setAmount($amount);
        $gatewayPayment->setPaymentId($paymentId);
        $gatewayPayment->setGateway($gateway);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function createGatewayRefundEntity($attributes, $input, $action)
    {
        $redactedRaw = $this->getRedactedData($attributes['raw']);
        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $refundId  = $input['refund']['id'];
        $refundAmount = $input['refund']['amount'];
        $gateway      = $input['payment']['gateway'];

        $gatewayPayment->setAction($action);
        $gatewayPayment->setRefundId($refundId);
        $gatewayPayment->setAmount($refundAmount);
        $gatewayPayment->setPaymentId($paymentId);
        $gatewayPayment->setGateway($gateway);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function updateGatewayPaymentEntityWithAction(
        MozartEntity $gatewayPayment,
        array $attributes,
        bool $mapped = true,
        $action = null)
    {
        if ($mapped === true)
        {
            $attributes = $this->getMappedAttributes($attributes);
        }

        $action = $action ?: $this->action;

        $redactedRaw = $this->getRedactedData($attributes['raw']);

        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment->setAction($action);

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function updateBankPaymentIdFromResponse($content, $gatewayPayment)
    {
        // temporary change - will go after conditional operation implementation on mozart
        $rawResponse = $content['data']['_raw']['BODY'];

        $gatewayPaymentRaw = json_decode($gatewayPayment['raw'], true);

        if ($rawResponse === 'Transaction Completed Successfully')
        {
            $content['data']['bank_payment_id'] = $gatewayPaymentRaw['bank_payment_id'];
        }

        if (strpos($rawResponse, 'Transaction Completed Successfully. Bank Reference Number is') !== false)
        {
            $splitRawResponse = explode(' ', $rawResponse);

            $content['data']['bank_payment_id'] = $splitRawResponse[count($splitRawResponse) - 1];
        }

        return $content;
    }

    protected function getPaymentToVerify(Verify $verify)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $verify->input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $gatewayPayment;

        return $gatewayPayment;
    }

    public function preProcessServerCallbackForYesb($input): array
    {
        $this->action = Action::PAY_VERIFY;

        $content['gateway']['redirect'] = $input;

        $content['payment']['gateway'] = Payment\Gateway::NETBANKING_YESB;

        $content['terminal']['gateway_secure_secret'] = $this->config['netbanking_yesb']['gateway_secure_secret'];

        $request = $this->getMozartRequestArray($content);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        // Till here response was fully encrypted and we did not know the payment id
        $paymentDetails['payment']['id'] = $response['data']['paymentId'];

        $this->traceGatewayPaymentResponse($traceRes, $paymentDetails, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        return $response;
    }

    protected function runCallbackValidationsIfApplicable($input, $response)
    {
        if ($this->shouldRunCallbackValidations($input['payment']['gateway']) === true)
        {
            if (isset($response['data']['paymentId']) === true)
            {
                $this->assertPaymentId($input['payment']['id'], $response['data']['paymentId']);
            }
            else
            {
                throw new Exception\LogicException(
                    'Payment Id should have been passed for validation');
            }

            if (isset($response['data']['amount']) === true)
            {
                // Adding this check as all the mozart gateways do not support formatted amount response
                // They will have to be migrated eventually as well to this flow. When all gateways are
                // migrated, this check should be removed
                if ($this->formattedResponseAmountGateway($input['payment']['gateway']))
                {
                    $dbAmount      = number_format($input['payment']['amount'] / 100, 2, '.', '');
                    $gatewayAmount = number_format($response['data']['amount'], 2, '.', '');
                }
                else
                {
                    $dbAmount      = $input['payment']['amount'];
                    $gatewayAmount = $response['data']['amount'];
                }

                $this->assertAmount($dbAmount, $gatewayAmount);
            }
            else
            {
                throw new Exception\LogicException(
                    'Amount should have been passed for validation');
            }
        }
    }

    protected function shouldRunCallbackValidations($gateway)
    {
        $validationGateways = [
            Payment\Gateway::UPI_AIRTEL,
            Payment\Gateway::WALLET_PHONEPE,
            Payment\Gateway::NETBANKING_YESB,
            Payment\Gateway::NETBANKING_SIB,
        ];

        return in_array($gateway, $validationGateways, true);
    }

    protected function fullyEncryptedFlow($gateway)
    {
        $fullyEncryptedInputGateways = [
          Payment\Gateway::NETBANKING_YESB
        ];

        return in_array($gateway, $fullyEncryptedInputGateways, true);
    }

    protected function formattedResponseAmountGateway($gateway)
    {
        $formattedAmountGateways = [
            Payment\Gateway::NETBANKING_YESB,
            Payment\Gateway::NETBANKING_SIB,
        ];

        return in_array($gateway, $formattedAmountGateways, true);
    }

    protected function getResponseData($input, $mozartResponse)
    {
        if ($input['payment']['method'] === Payment\Method::UPI)
        {
            $response = [
                'acquirer' => [
                    Payment\Entity::VPA         => $mozartResponse['responseBody']['data']['vpa'] ?? $input['terminal']['gateway_merchant_id2'],
                    Payment\Entity::REFERENCE16 => $mozartResponse['responseBody']['data']['rrn'] ?? null,
                ]
            ];
        }
        elseif ($input['payment']['method'] === Payment\Method::NETBANKING)
        {
            $response = $this->getCallbackResponseData($input);
        }
        else
        {
            $response = $mozartResponse;
        }

        return $response;
    }
}
