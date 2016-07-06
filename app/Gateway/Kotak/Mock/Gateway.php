<?php

namespace Gateway\Kotak\Mock;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Kotak;
use Gateway\Base;
use Models\Card;

class Gateway extends Kotak\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        // The key thing now is to replace redirectUrl from kotak's to ours!
        $parts = parse_url($request['url']);

        $url = \Http\Route::getUrlWithPublicAuth('mock_kotak_payment');
        $url = $url . '&' .$parts['query'];

        $request['url'] = $url;

        return $request;
    }
}