<?php

namespace RZP\Models\BankingAccountStatement\Pool\Rbl;

use RZP\Models\BankingAccountStatement\Pool\Base\Core as BaseCore;

class Core extends BaseCore
{
    public function __construct()
    {
        parent::__construct();

        $this->channelRepo = $this->repo->banking_account_statement_pool_rbl;
    }
}
