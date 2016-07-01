<?php

namespace Gateway\Wallet\Olamoney\Mock;

use Http\Route;
use EE\Exception;
use Gateway\Base;
use EE\Error\ErrorCode;
use Gateway\Wallet\Olamoney;

class Gateway extends Olamoney\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize($input)
    {
        $request = parent::authorize($input);

        $url = Route::getUrlWithPublicAuth(
                    'mock_wallet_payment_get',
                    ['wallet' => $input['payment']['wallet'],
                     'paymentId' => $input['payment']['id']]);

        $request['url'] = $url;
        s($url);
        return $request;
    }
}
