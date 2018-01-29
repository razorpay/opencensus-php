<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{
    protected function authorize($input)
    {
        parent::authorize($input);

        sd("Mock server reached");
    }
}
