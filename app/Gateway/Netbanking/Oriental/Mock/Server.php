<?php

namespace RZP\Gateway\Netbanking\Oriental\Mock;

use RZP\Gateway\Base\Mock;

final class Server extends Mock\Server
{
    protected function authorize($input)
    {
        parent::authorize($input);
    }
}
