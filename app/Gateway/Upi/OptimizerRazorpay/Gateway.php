<?php

namespace RZP\Gateway\Upi\OptimizerRazorpay;

use RZP\Exception\LogicException;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;
use RZP\Gateway\Upi\Base;
use RZP\Models\Payment;


class Gateway extends Base\Gateway{

    use CommonGatewayTrait;

    protected $gateway = Payment\Gateway::OPTIMIZER_RAZORPAY;
    public function authorize(array $input){
        parent::authorize($input);
        $method = $input['payment']['method'];
        if ($method === Payment\Method::UPI)
        {
            return $this->upiAuthorize($input);
        }
        throw new LogicException('Invalid Payment method, authorize request failed');
    }
}
