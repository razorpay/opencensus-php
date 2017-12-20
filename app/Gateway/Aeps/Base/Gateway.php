<?php

namespace RZP\Gateway\Aeps\Base;

use RZP\Gateway\Base\Gateway as BaseGateway;

class Gateway extends BaseGateway
{
    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }

    protected function getRepository()
    {
        $gateway = 'aeps';

        return $this->app['repo']->$gateway;
    }
}
