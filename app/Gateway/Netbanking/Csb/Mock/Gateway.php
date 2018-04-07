<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use RZP\Gateway\Netbanking\Csb;
use RZP\Gateway\Base\Mock\GatewayTrait;

final class Gateway extends Csb\Gateway
{
    use GatewayTrait;

    public final function authorize(array $input): array
    {
        $request = parent::authorize($input);

        $request['url'] = $this->route->getUrlWithPublicAuth('mock_netbanking_payment', ['bank' => 'csb']);

        return $request;
    }
}
