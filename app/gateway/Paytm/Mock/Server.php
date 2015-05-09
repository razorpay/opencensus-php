<?php

namespace Gateway\Paytm\Mock;

use Gateway\Paytm;
use Gateway\Base;

class Server extends Base\Mock\Server
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
}
