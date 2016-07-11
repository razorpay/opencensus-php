<?php

namespace RZP\Gateway\Wallet\Olamoney\Mock;

use RZP\Http\Route;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Wallet\Olamoney;

class Gateway extends Olamoney\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $parts = parse_url($request['url']);

        $url = Route::getUrlWithPublicAuth('mock_wallet_payment_get',
                                                ['wallet' => $input['payment']['wallet'],
                                                 'paymentId' => $input['payment']['id']]);
        $url = $url . '&' .$parts['query'];

        $request['url'] = $url;

        return $request;
    }
}
