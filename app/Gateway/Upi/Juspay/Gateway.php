<?php

namespace RZP\Gateway\Upi\Juspay;

use RZP\Models\Payment;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{

    use AuthorizeFailed;

    use Base\CommonGatewayTrait;

    use Base\MozartTrait;

    const ACQUIRER = 'axis';

    protected $gateway = Payment\Gateway::UPI_JUSPAY;

    // As, UPI Juspay currently depends on mozart entity we will mark this flag as false
    // TODO: Mark this as true or remove it , when we move the upi entity creation to this class.
    protected $shouldMapLateAuthorized = false;

    protected $shouldUseMozartEntity = true;

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

        $this->refundRequest($input);
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

    protected function verifyPayment(Verify $verify)
    {
        return $this->upiVerifyPayment($verify);
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        return $this->upiPaymentIdFromServerCallback($response);
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
