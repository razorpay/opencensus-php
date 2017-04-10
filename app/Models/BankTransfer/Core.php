<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($input)
    {
        $bankTransfer = (new Entity)->build($input);

        return $bankTransfer;
    }
}
