<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Axis;

class Gateway extends Axis\Gateway
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
