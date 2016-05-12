<?php

namespace Gateway\Wallet\Payumoney\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;
use Gateway\Wallet\Payumoney;

class Gateway extends Payumoney\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = \Http\Route::getUrlWithPublicAuth(
                                'mock_wallet_payment',
                                ['wallet' => $this->wallet]);

        $request['url'] = $url;

        return $request;
    }
}
