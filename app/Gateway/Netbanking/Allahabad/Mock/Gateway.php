<?php

namespace RZP\Gateway\Netbanking\Allahabad\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Allahabad;

class Gateway extends Allahabad\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth(
                              'mock_netbanking_payment',
                               ['bank' => $this->bank]);

        $parts = parse_url($request['url']);

        $str = $parts['query'];

        $request['url'] = $url .'&'. $str;

        return $request;
    }
}
