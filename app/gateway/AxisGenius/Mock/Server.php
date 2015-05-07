<?php

namespace Gateway\AxisGenius\Mock;

use Gateway\AxisMigs;
use Gateway\AxisGenius;

class Server extends AxisMigs\Mock\Server
{
    protected function getValidator()
    {
        if ($this->validator === null)
        {
            $this->validator = new Validator;
        }

        return $this->validator;
    }

    protected function addVpcMerchant(array & $content, $input)
    {
        $content['vpc_Merchant'] = $input['vpc_MerchantId'];
    }

    protected function generateHash($content)
    {
        return (new AxisGenius\Gateway)->generateHash($content);
    }
}
