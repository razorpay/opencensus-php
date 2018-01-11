<?php

namespace RZP\Gateway\Netbanking\Oriental\Mock;

use RZP\Gateway\Netbanking\Oriental;

final class Gateway extends Oriental\Gateway
{
    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        sd('Reaching the request part of the code');
    }
}
