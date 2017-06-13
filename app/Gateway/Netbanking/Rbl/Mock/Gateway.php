<?php

namespace RZP\Gateway\Netbanking\Rbl\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Rbl;

class Gateway extends Rbl\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth('mock_netbanking_payment',
                                                  ['bank' => $this->bank]);

        $request['url'] = $url;

        return $request;
    }
}
