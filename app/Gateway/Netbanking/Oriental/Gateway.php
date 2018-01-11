<?php

namespace RZP\Gateway\Netbanking\Oriental;

use RZP\Gateway\Netbanking\Base;

class Gateway extends Base\Gateway
{
    public function authorize(array $input)
    {
        parent::authorize($input);

        sd('Auth reached');
    }
}
