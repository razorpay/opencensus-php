<?php

namespace RZP\Gateway\Upi\Juspay;

use RZP\Models\Payment;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\AuthorizeFailed;

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

        return $this->authorizeRequest($input);
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
}
