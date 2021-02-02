<?php

namespace RZP\Gateway\Upi\Juspay;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Mozart\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\UpiMetadata\Flow;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    use Base\MozartTrait;

    const ACQUIRER = 'axis';

    protected $gateway = Payment\Gateway::UPI_JUSPAY;

    // As, UPI Juspay currently depends on mozart entity we will mark this flag as false
    // TODO: Mark this as true or remove it , when we move the upi entity creation to this class.
    protected $shouldMapLateAuthorized = false;

    protected $map = [];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = [];

        $flow = $input['upi']['flow'];

        if (Flow::isFlowCollect($flow) === true)
        {
            $attributes[Base\Entity::TYPE] = Base\Type::COLLECT;
        }
        else if (Flow::isFlowIntent($flow) === true)
        {
            $attributes[Base\Entity::TYPE] = Base\Type::PAY;
        }

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes, $this->action, false);

        $mozart = $this->getUpiMozartGatewayWithModeSet();

        // Note: We are doing this because currently upi juspay recon is dependent on mozart entity
        $mozartEntity = $mozart->createMozartEntity([
            'raw' => []
        ], $input, Action::AUTHORIZE);

        $response = $mozart->sendUpiMozartRequest(
            $input,
            TraceCode::GATEWAY_AUTHORIZE_REQUEST,
            Action::PAY_INIT);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $mozart->updateMozartEntity($mozartEntity, $response, true, Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $response['data']['upi'] ?? [], false);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        // For intent
        if (Flow::isFlowIntent($flow) === true)
        {
            $data = [
                'intent_url' => $response['next']['redirect']['url'],
            ];

            return ['data' => $data];
        }

        return [
            'data'   => [
                Payment\Entity::VPA => $input['terminal']['vpa'],
            ]
        ];
    }

    public function preProcessServerCallback($input): array
    {
        return $input;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        return $this->callbackRequest($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $this->refundRequest($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        return $this->verifyMozart($input);
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        return $this->getPaymentIdFromServerCallbackRequest($response, $gateway);
    }

    /**
     * Function to postprocess the response of callback. In case of success, return true.
     * However in case of exception, suppress the error and return failure response.
     * @param  array  $input request array
     * @param  exception  $exception exception object
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
