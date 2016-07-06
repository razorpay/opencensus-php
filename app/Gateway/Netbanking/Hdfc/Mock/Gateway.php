<?php

namespace RZP\Gateway\Netbanking\Hdfc\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Hdfc;

class Gateway extends Hdfc\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = RZP\Http\Route::getUrlWithPublicAuth(
                                'mock_netbanking_payment',
                                ['bank' => $this->bank]);

        $request['url'] = $url;

        return $request;
    }
}
