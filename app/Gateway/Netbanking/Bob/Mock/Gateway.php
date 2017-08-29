<?php

namespace RZP\Gateway\Netbanking\Bob\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Bob;

class Gateway extends Bob\Gateway
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
