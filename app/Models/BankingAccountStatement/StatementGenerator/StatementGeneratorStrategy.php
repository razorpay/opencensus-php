<?php

namespace RZP\Models\BankingAccountStatement\StatementGenerator;

use RZP\Models\Base;




abstract class StatementGeneratorStrategy extends Base\Core
{
    protected $account_number;
    protected $channel;
    protected $format;

    public function __construct($account_number, $channel, $format)
    {
        parent::__construct();
        $this->account_number = $account_number;
        $this->channel = $channel;
        $this->format = $format;
    }





}
