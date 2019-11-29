<?php

namespace RZP\Gateway\Netbanking\Equitas\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Equitas;

class Gateway extends Equitas\Gateway
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
