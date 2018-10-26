<?php

namespace RZP\Gateway\Netbanking\Sbi\Mock;

use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateActionInput($input, 'auth');

    }

}
