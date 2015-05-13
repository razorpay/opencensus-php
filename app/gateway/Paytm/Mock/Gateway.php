<?php

namespace Gateway\Paytm\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;
use Gateway\Paytm;

class Gateway extends Paytm\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = \Http\Route::getUrlWithPublicAuth('mock_paytm_payment');
        $request['url'] = $url;

        return $request;
    }
}
