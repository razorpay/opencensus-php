<?php

namespace Gateway\AxisGenius\Mock;

use Gateway\AxisMigs;
use Gateway\AxisGenius;

class Server extends AxisMigs\Mock\Server
{
    protected function addVpcMerchant(array & $content, $input)
    {
        $content['vpc_Merchant'] = $input['vpc_MerchantId'];
    }
}
