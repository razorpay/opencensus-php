<?php

namespace RZP\Gateway\Netbanking\Oriental\Mock;

use RZP\Gateway\Netbanking\Oriental;

final class Gateway extends Oriental\Gateway
{
    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $request['url'] = $this->route->getUrlWithPublicAuth(
                            'mock_netbanking_payment',
                            ['bank' => $this->bank]);

        return $request;
    }
}
