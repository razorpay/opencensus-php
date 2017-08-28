<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Icici;

class Gateway extends Icici\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        if ($this->isSecondRecurringPayment($input) === true)
        {
            return $request;
        }

        $url = $this->route->getUrlWithPublicAuth('mock_netbanking_payment',
                                                  ['bank' => $this->bank]);

        $request['url'] = $url;

        return $request;
    }
}
