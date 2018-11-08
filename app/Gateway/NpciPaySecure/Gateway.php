<?php

namespace RZP\Gateway\NpciPaySecure;

use RZP\Gateway\Base;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;
    use RequestHandlerTrait;

    protected $gateway = 'npci_paysecure';

    public function authorize(array $input)
    {
        parent::authorize($input);

        // Todo: Don't trace card number and cvv
        $this->app['trace']->info(
            TraceCode::GATEWAY_REQUEST_INPUT_RECEIVED,
            $input
        );

        $checkBinResponse = $this->checkBin();
    }



    // ------------ General helpers -----------------
    protected function getRepository()
    {
        $gateway = $this->gateway;

        return $this->app['repo']->$gateway;
    }
}
