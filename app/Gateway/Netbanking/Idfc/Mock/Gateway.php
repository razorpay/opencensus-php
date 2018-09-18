<?php

namespace RZP\Gateway\Netbanking\Idfc\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Idfc;

class Gateway extends Idfc\Gateway
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
