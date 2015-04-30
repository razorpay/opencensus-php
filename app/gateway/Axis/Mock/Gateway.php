<?php

namespace Gateway\Axis\Mock;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Axis;
use Models\Card;
use Requests_Response;

class Gateway extends Axis\Gateway
{
    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = \Http\Route::getUrlWithPublicAuth('mockaxis_payment');
        $request['url'] = $url;

        return $request;
    }
}
