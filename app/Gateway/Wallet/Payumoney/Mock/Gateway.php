<?php

namespace Gateway\Wallet\Payumoney\Mock;

use Http\Route;
use EE\Exception;
use Gateway\Base;
use EE\Error\ErrorCode;
use Gateway\Wallet\Payumoney;

class Gateway extends Payumoney\Gateway
{
    use Base\Mock\GatewayTrait;

    public function topup($input)
    {
        $request = parent::topup($input);

        $url = Route::getUrlWithPublicAuth(
                    'mock_wallet_payment_with_paymentid',
                    ['wallet' => $input['payment']['wallet'],
                     'paymentId' => $input['payment']['id']]);

        $request['url'] = $url;

        return $request;
    }
}
