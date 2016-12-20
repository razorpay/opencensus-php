<?php

namespace RZP\Gateway\Wallet\Jiomoney\Mock;

use RZP\Constants\HashAlgo;
use RZP\Gateway\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Gateway\Wallet\Jiomoney;
use RZP\Gateway\Wallet\Jiomoney\ResponseFields;
use RZP\Gateway\Wallet\Jiomoney\RequestFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        sd($input);
        // $this->validateActionInput($input, 'authorize');

        // $this->verifyHash($input);
    }

    public function refund($input)
    {
        # code...
    }
}
