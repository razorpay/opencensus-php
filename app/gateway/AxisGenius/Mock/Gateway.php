<?php

namespace Gateway\AxisGenius\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\AxisGenius;
use Gateway\Base;

class Gateway extends AxisGenius\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = \Http\Route::getUrlWithPublicAuth('mock_axis_genius_payment');
        $request['url'] = $url;

        return $request;
    }
}
