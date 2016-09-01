<?php

namespace RZP\Gateway\Wallet\Airtelmoney\Mock;

use RZP\Http\Route;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Wallet\Airtelmoney;

class Gateway extends Airtelmoney\Gateway
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
