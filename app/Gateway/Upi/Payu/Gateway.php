<?php

namespace RZP\Gateway\Upi\Payu;

use RZP\Models\Payment;
use RZP\Gateway\Upi\Base;
use RZP\Exception\LogicException;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{

    use AuthorizeFailed;

    use Base\CommonGatewayTrait;

    const ACQUIRER = 'payu';

    protected $gateway = Payment\Gateway::PAYU;

    protected $map = [];

    public function authorize(array $input)
    {
        /**
         * Processing authorize requests for upi payment method payu with upi common trait.
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
