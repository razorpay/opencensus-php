<?php

namespace RZP\Gateway\Netbanking\Corporation\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Paytm;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);
    }
}
