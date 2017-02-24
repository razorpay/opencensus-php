<?php

namespace RZP\Gateway\Netbanking\Federal\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Federal;

class Gateway extends Federal\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth(
            'mock_netbanking_payment', ['bank' => $this->bank]);

        $request['url'] = $url;

        return $request;
    }
}
