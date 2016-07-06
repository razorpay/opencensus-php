<?php

namespace Gateway\Netbanking\Hdfc\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;
use Gateway\Netbanking\Hdfc;

class Gateway extends Hdfc\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = \Http\Route::getUrlWithPublicAuth(
                                'mock_netbanking_payment',
                                ['bank' => $this->bank]);

        $request['url'] = $url;

        return $request;
    }
}
