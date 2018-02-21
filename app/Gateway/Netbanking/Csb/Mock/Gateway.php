<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use RZP\Gateway\Base\Mock\GatewayTrait;
use RZP\Models\Payment;
use RZP\Gateway\Netbanking\Csb;

final class Gateway extends Csb\Gateway
{
    use GatewayTrait;

    public final function authorize(array $input): array
    {
        $request = parent::authorize($input);

        $gatewayArray = explode('_', $this->gateway);

        // We pass in a variable, as only actual variables can be passed in by reference to the end method.
        $bank = end($gatewayArray);

        $request['url'] = $this->route->getUrlWithPublicAuth('mock_netbanking_payment', ['bank' => $bank]);

        return $request;
    }
}
