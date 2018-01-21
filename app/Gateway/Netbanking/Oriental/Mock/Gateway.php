<?php

namespace RZP\Gateway\Netbanking\Oriental\Mock;

use RZP\Gateway\Netbanking\Oriental;
use RZP\Gateway\Base\Mock\GatewayTrait;

final class Gateway extends Oriental\Gateway
{
    use GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $request['url'] = $this->route->getUrlWithPublicAuth(
                            'mock_netbanking_payment',
                            ['bank' => $this->bank]);

        return $request;
    }
}
