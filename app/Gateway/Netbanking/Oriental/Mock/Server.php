<?php

namespace RZP\Gateway\Netbanking\Oriental\Mock;

use RZP\Gateway\Base\Mock;

final class Server extends Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        sd('Mock server reached');
    }
}
