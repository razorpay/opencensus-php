<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use RZP\Gateway\Base\Mock\GatewayTrait;
use RZP\Models\Payment;
use RZP\Gateway\Netbanking\Csb;

class Gateway extends Csb\Gateway
{
    use GatewayTrait;

    /**
     * This variable should such that the corresponding entity name is of the format netbanking_{bank},
     * where {bank} must be mapped to the variable set below.
     *
     * @var string
     */
    private $bank;

    public function __construct()
    {
        parent::__construct();

        $this->bank = explode('_', $this->gateway)[1];
    }

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $request['url'] = $this->route->getUrlWithPublicAuth('mock_netbanking_payment', ['bank' => $this->bank]);

        return $request;
    }
}
