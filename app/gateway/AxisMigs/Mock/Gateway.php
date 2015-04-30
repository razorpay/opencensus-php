<?php

namespace Gateway\AxisMigs\Mock;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\AxisMigs;
use Models\Card;
use Requests_Response;

class Gateway extends AxisMigs\Gateway
{
    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = \Http\Route::getUrlWithPublicAuth('mockaxis_payment');
        $request['url'] = $url;

        return $request;
    }
}
