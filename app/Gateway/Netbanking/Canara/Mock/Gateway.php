<?php

namespace RZP\Gateway\Netbanking\Canara\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Canara;

class Gateway extends Canara\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth(
            'mock_netbanking_payment',
            ['bank' => $this->bank]);

        $urlcomponents = parse_url($request['url']);

        parse_str($urlcomponents['query'], $query);

        $request['url'] = $url . '&' . http_build_query($query);

        return $request;
    }
}
