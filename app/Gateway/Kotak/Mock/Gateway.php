<?php

namespace RZP\Gateway\Kotak\Mock;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Kotak;
use RZP\Gateway\Base;
use RZP\Models\Card;

class Gateway extends Kotak\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        // The key thing now is to replace redirectUrl from kotak's to ours!
        $parts = parse_url($request['url']);

        $url = $this->route->getUrlWithPublicAuth('mock_kotak_payment');
        $url = $url . '&' .$parts['query'];

        $request['url'] = $url;

        return $request;
    }
}
