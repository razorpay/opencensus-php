<?php

namespace RZP\Gateway\Upi\Cashfree;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Exception\LogicException;
use RZP\Gateway\Upi\Cashfree\Fields;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;

class Gateway extends Base\Gateway
{

    use AuthorizeFailed;

    use CommonGatewayTrait;

    const ACQUIRER = 'cashfree';

    protected $gateway = Payment\Gateway::CASHFREE;

    protected $shouldMapLateAuthorized = false;

    protected $map = [];

    public function authorize(array $input)
    {
        /**
         * Processing authorize requests for upi payment method cashfree with upi common trait.
         * Checking method if upi then send to Mozart else throw exception.
         */
        parent::authorize($input);

        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiAuthorize($input);
        }
        throw new LogicException('Payment method is not upi, request unable to processed via upiAuthorize');
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            $mozart = $this->getUpiMozartGatewayWithModeSet();

            $result = $mozart->sendUpiMozartRequest($input, TraceCode::GATEWAY_PAYMENT_CALLBACK, 'pay_verify');

            $input['gateway'] = $result;

            return $this->upiCallback($input);
        }
        throw new LogicException('Payment method is not upi, request unable to processed via upiCallback');
    }

    public function preProcessServerCallback($input): array
    {
        return $input;
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        return $response[Fields::ORDER_ID];
    }

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
