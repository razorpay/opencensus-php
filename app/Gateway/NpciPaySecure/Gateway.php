<?php

namespace RZP\Gateway\NpciPaySecure;

use RZP\Gateway\Base;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    protected $gateway = 'npci_paysecure';

    public function authorize(array $input)
    {
        sd($input);
    }

    protected function getRepository()
    {
        $gateway = $this->gateway;

        return $this->app['repo']->$gateway;
    }
}
