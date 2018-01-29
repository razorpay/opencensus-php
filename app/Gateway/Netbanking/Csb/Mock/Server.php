<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        sd("Mock server reached");
    }
}
