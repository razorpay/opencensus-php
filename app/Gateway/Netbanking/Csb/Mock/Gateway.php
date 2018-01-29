<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use RZP\Models\Bank\IFSC;
use RZP\Gateway\Netbanking\Csb;

class Gateway extends Csb\Gateway
{
    private $bank = IFSC::CSBK;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $request['url'] = $this->route->getUrlWithPublicAuth('mock_netbanking_payment', ['bank' => $this->bank]);

        return $request;
    }
}
