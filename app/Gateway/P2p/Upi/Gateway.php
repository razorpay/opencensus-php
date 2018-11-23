<?php

namespace RZP\Gateway\P2p\Upi;

use RZP\Gateway\P2p\Base;
use RZP\Gateway\P2p\Base\Factory;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Models\P2p\Base\Libraries\Context;

class Gateway extends Base\Gateway
{
    public function device(Context $context)
    {
        $gateway = Factory::make($context, Contracts\DeviceGateway::class);

        return $gateway->response();
    }

    public function bankAccount(Context $context)
    {
        $response = Factory::response($context, 'bank_account');

        return $response;
    }

    public function vpa(Context $context)
    {
        $response = Factory::response($context, 'vpa');

        return $response;
    }

    public function transaction(Context $context)
    {
        $response = Factory::response($context, 'transaction');

        return $response;
    }
}
