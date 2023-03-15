<?php

namespace RZP\Gateway\Upi\Juspay;

use Request;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Gateway\Utility;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use Illuminate\Support\Str;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Trace\ApiTraceProcessor;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;
use RZP\Models\Payment\Entity as PaymentEntity;

class Gateway extends Base\Gateway
{

    use AuthorizeFailed;

    use Base\CommonGatewayTrait;

    const ACQUIRER = 'axis';

    protected $gateway = Payment\Gateway::UPI_JUSPAY;

    // As, UPI Juspay currently depends on mozart entity we will mark this flag as false
    // TODO: Mark this as true or remove it , when we move the upi entity creation to this class.
    protected $shouldMapLateAuthorized = false;

    protected $map = [];

    public function authorize(array $input)
    {
        parent::authorize($input);

        return $this->upiAuthorize($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

       return $this->upiCallback($input);
    }

    public function preProcessServerCallback($input): array
    {
        return $this->upiPreProcess($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $this->upiRefund($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        return $this->upiSendPaymentVerifyRequest($verify);
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

        unset($data['_raw']);

        unset($data['data']['account_number']);

        return $data;
    }


    /**
     * @param $verify
     * @return array
     * @throws Exception\GatewayErrorException
     * This is used for verifying the unexpected payments.
     * In normal payments verify we have upi entity but for unexpected payments we don't have, because of which used this.
     */
    public function sendPaymentVerifyRequestv2($verify)
    {
        $result               = $this->upiSendGatewayRequest(
            $verify->input,
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            'verify'
        );
        $traceRes = $this->getRedactedData($result);

        $this->traceGatewayPaymentResponse($traceRes, $result, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $verify->verifyResponseContent = $result;

        $verify->verifyResponse = null;

        $verify->verifyResponseBody = null;

        return $verify->verifyResponseContent;
    }

    protected function verifyPayment(Verify $verify)
    {
        return $this->upiVerifyPayment($verify);
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        return $this->upiPaymentIdFromServerCallback($response);
    }

    public function getParsedDataFromUnexpectedCallback($input)
    {
        return $this->upiGetParsedDataFromUnexpectedCallback($input);
    }

    /**
     * Check if its a valid Unexpected Payment
     * @param array $callbackData
     * @throws Exception\LogicException
     * @throws GatewayErrorException
     */
    protected function upiIsValidUnexpectedPaymentV2($callbackData)
    {
        //
        // Verifies if the payload specified in the server callback is valid.
        //
        $input = [
            'payment'       => [
                'id'             => $callbackData['upi']['merchant_reference'],
                'gateway'        => $callbackData['terminal']['gateway'],
                'vpa'            => $callbackData['upi']['vpa'],
                'amount'         => (int) ($callbackData['payment']['amount']),
            ],
            'terminal'      => $this->terminal,
            'gateway'       => [
                'cps_route'     => Payment\Entity::UPI_PAYMENT_SERVICE,
            ]
        ];
        $this->action = Action::VERIFY;

        $verify = new Verify($input['payment']['gateway'], $input);

        $this->sendPaymentVerifyRequestv2($verify);

        $paymentAmount = $verify->input['payment']['amount'];

        $content = $verify->verifyResponseContent;

        $actualAmount = $content['data']['payment']['amount_authorized'];

        $this->assertAmount($paymentAmount, $actualAmount);

        $status = $content['success'];

        $this->checkUnexpectedPaymentResponseStatus($status);
    }

    public function validatePush($input)
    {
        parent::action($input, Action::VALIDATE_PUSH);

        // It checks if the version is V2,which is request from art
        if ((empty($input['meta']['version']) === false) and
            ($input['meta']['version'] === 'api_v2'))
        {
            $this->isDuplicateUnexpectedPaymentV2($input);

            $this->upiIsValidUnexpectedPaymentV2($input);

            return;
        }

        $this->upiValidatePush($input);
    }

    /** Checks if duplicate unexpected payment for the recon through ART
     * @param $input
     * @throws Exception\LogicException
     */
    protected function isDuplicateUnexpectedPaymentV2($input)
    {
        $upiEntity = $this->upiGetRepository()->fetchByNpciReferenceIdAndGateway($input['upi']['npci_reference_id'], $this->gateway);

        if (empty($upiEntity) === false)
        {
            if ($upiEntity->getAmount() === (int) ($input['payment']['amount']))
            {
                throw new Exception\LogicException(
                    'Duplicate Unexpected payment with same amount',
                    null,
                    [
                        'callbackData' => $input
                    ]
                );
            }
        }
    }

    /**
     * @param $status
     * @return void
     * @throws Exception\GatewayErrorException
     * Checks the status of unexpected payments
     */
    protected function checkUnexpectedPaymentResponseStatus($status)
    {
        if ($status !== true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            );
        }
    }

    public function authorizePush($input)
    {
        list($paymentId , $callbackData) = $input;

        // It checks if the version is V2,which is request from art
        if ((empty($callbackData['meta']['version']) === false) and
            ($callbackData['meta']['version'] === 'api_v2'))
        {
            return $this->authorizePushV2($input);
        }
        return $this->upiAuthorizePush($input);
    }

    /**
     * AuthorizePushV2 is triggered for reconciliation happening via ART
     * @param array $input
     * @return array[]
     * @throws Exception\LogicException
     */
    protected function authorizePushV2($input)
    {
        list ($paymentId, $content) = $input;

        // Create attributes for upi entity.
        $attributes = [
            Entity::TYPE                => Base\Type::PAY,
            Entity::RECEIVED            => 1,
        ];

        $attributes = array_merge($attributes, $content['upi']);

        $payment  = $content['payment'];

        $upi      = $content['upi'];

        $gateway = $this->gateway;

        // Create input structure for upi entity.
        $input = [
            'payment'    => [
                'id'       => $paymentId,
                'gateway'  => $gateway,
                'vpa'      => $upi['vpa'],
                'amount'   => $payment['amount'],
            ],
        ];

        // Call to set the input in gateway
        parent::action($input, Action::AUTHORIZE);

        $gatewayPayment = $this->upiCreateGatewayEntity($input, $attributes);

        return [
            'acquirer' => [
                PaymentEntity::VPA           => $gatewayPayment->getVpa(),
                PaymentEntity::REFERENCE16   => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }

    /**
     * Function to postprocess the response of callback. In case of success, return true.
     * However in case of exception, suppress the error and return failure response.
     * @param  array  $input request array
     * @param \Exception $exception exception object
     * @return array success/failure response
     */
    public function postProcessServerCallback($input, $exception = null)
    {
        if ($exception === null)
        {
            return [
                'success' => true,
            ];
        }

        return [
            'success' => false,
        ];

    }


}
