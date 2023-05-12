<?php

namespace RZP\Gateway\Upi\Yesbank\Mock;

use RZP\Exception;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Yesbank;

class Gateway extends Yesbank\Gateway
{
    use Base\Mock\GatewayTrait;

    public function getQrRefId($input): string
    {
        if ($input['terminal']['merchant_id'] === 'LiveAccountMer')
        {
            return throw new Exception\RuntimeException('Invalid Response from Mozart');
        }
        return '107611570997';
    }
}
