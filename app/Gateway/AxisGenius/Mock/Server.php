<?php

namespace RZP\Gateway\AxisGenius\Mock;

use RZP\Gateway\AxisMigs;
use RZP\Gateway\AxisGenius;

class Server extends AxisMigs\Mock\Server
{
    protected function addVpcMerchant(array & $content, $input)
    {
        $content['vpc_Merchant'] = $input['vpc_MerchantId'];
    }
}
