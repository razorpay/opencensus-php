<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use RZP\Gateway\Base\Mock\GatewayTrait;
use RZP\Models\Payment;
use RZP\Gateway\Netbanking\Csb;

class Gateway extends Csb\Gateway
{
    use GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $gatewayArray = explode('_', $this->gateway);

        // We pass in a variable, as only actual variables can be passed in by reference to the end method.
        $bank = end($gatewayArray);

        $request['url'] = $this->route->getUrlWithPublicAuth('mock_netbanking_payment', ['bank' => $bank]);

        return $request;
    }
}
