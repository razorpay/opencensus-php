<?php

namespace RZP\Gateway\Enach\Npci\Netbanking\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Enach\Npci\Netbanking;

class Gateway extends Netbanking\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        if ($this->isSecondRecurringPaymentRequest($input) === true)
        {
            return null;
        }

        $url = $this->route->getUrlWithPublicAuth('mock_emandate_payment', ['authType' => 'netbanking']);

        $request['url'] = $url;

        return $request;
    }
}
