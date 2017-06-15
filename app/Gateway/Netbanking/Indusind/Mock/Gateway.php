<?php

namespace RZP\Gateway\Netbanking\Indusind\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Indusind;

class Gateway extends Indusind\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input): array
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth('mock_netbanking_payment',
                                                  ['bank' => $this->bank]);

        $request['url'] = $url;

        return $request;
    }
}
