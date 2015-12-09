<?php

namespace Gateway\Wallet\Payzapp\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;
use Gateway\Wallet\Payzapp;

class Gateway extends Payzapp\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        if ($this->testing)
        {
            $url = \Http\Route::getUrlWithPublicAuth('mock_wallet_payment', ['wallet' => 'payzapp']);

            $request['content'] .= '***'.$url.'***';
        }

        return $request;
    }
}
