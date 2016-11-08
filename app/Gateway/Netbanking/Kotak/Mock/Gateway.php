<?php

namespace RZP\Gateway\Netbanking\Kotak\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Kotak;

class Gateway extends Kotak\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth(
                                'mock_netbanking_payment',
                                ['bank' => $this->bank]);

        $request['url'] = $url;

        return $request;
    }
}
