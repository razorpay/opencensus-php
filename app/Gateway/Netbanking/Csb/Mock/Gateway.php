<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use RZP\Gateway\Netbanking\Csb;

class Gateway extends Csb\Gateway
{
    public function authorize(array $input)
    {
        parent::authorize($input);

        sd('Mock Gateway');
    }
}
