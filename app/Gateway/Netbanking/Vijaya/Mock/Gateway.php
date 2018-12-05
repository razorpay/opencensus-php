<?php

namespace RZP\Gateway\Netbanking\Vijaya\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Vijaya;

class Gateway extends Vijaya\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input): array
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth(
            'mock_netbanking_payment',
            ['bank' => $this->bank]);

        $request['url'] = $url;

        return $request;
    }
}
