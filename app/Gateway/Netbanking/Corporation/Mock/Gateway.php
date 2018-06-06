<?php

namespace RZP\Gateway\Netbanking\Corporation\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Corporation;

class Gateway extends Corporation\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth(
                                'mock_netbanking_payment',
                                ['bank' => $this->bank]);

        $parts = parse_url($request['url']);

        parse_str($parts['query'], $query);

        $request['url'] = $url . '&' . http_build_query($query);

        return $request;
    }
}
