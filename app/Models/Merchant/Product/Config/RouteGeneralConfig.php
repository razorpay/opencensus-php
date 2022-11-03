<?php

namespace RZP\Models\Merchant\Product\Config;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Product\Util;

class RouteGeneralConfig extends PaymentsGeneralConfig
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getConfig(Merchant\Entity $merchant)
    {
        $response = [];

        $response[Util\Constants::BANK_DETAILS] = $this->getBankDetails($merchant);

        return $response;
    }
}
