<?php

namespace RZP\Gateway\Wallet\Sbibuddy\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Sbibuddy;

class Gateway extends Sbibuddy\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth(
            'mock_wallet_payment_with_paymentid',
            [
                'wallet' => $input['payment']['wallet'],
                'paymentId' => $input['payment']['id']
            ]
        );

        $request['url'] = $url;

        $request['method'] = 'post';

        return $request;
    }
}
