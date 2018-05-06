<?php

namespace RZP\Gateway\Netbanking\Obc\Mock;

use RZP\Gateway\Netbanking\Obc;
use RZP\Gateway\Base\Mock\GatewayTrait;

final class Gateway extends Obc\Gateway
{
    use GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $bank = 'obc';

        $request['url'] = $this->route->getUrlWithPublicAuth(
                            'mock_netbanking_payment',
                            ['bank' => $bank]);

        return $request;
    }
}
