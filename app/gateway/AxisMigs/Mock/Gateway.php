<?php

namespace Gateway\AxisMigs\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\AxisMigs;
use Gateway\Base;

class Gateway extends AxisMigs\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = \Http\Route::getUrlWithPublicAuth('mock_axis_migs_payment');
        $request['url'] = $url;

        return $request;
    }
}
