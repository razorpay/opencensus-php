<?php

namespace RZP\Gateway\Upi\Cashfree;

use RZP\Exception\LogicException;
use RZP\Models\Payment;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{

    use AuthorizeFailed;

    use Base\CommonGatewayTrait;

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
}
