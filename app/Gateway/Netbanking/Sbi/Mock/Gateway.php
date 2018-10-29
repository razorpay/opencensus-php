<?php

namespace RZP\Gateway\Netbanking\Sbi\Mock;

use RZP\Gateway\Netbanking\Sbi;
use RZP\Gateway\Base\Mock\GatewayTrait;

class Gateway extends Sbi\Gateway
{
    use GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $bank = 'sbi';

        $request['url'] = $this->route->getUrlWithPublicAuth(
            'mock_netbanking_payment',
            ['bank' => $bank]);

        return $request;
    }
}
