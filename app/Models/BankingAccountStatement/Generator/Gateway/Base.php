<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway;

use RZP\Models\Base\Core as BaseCore;

abstract class Base extends BaseCore
{
    protected $accountNumber;

    protected $channel;

    protected $fromDate;

    protected $toDate;

    abstract function getStatement();

    public function __construct(string $accountNumber, string $channel, int $fromDate, int $toDate)
    {
        parent::__construct();

        $this->accountNumber = $accountNumber;

        $this->channel = $channel;

        $this->fromDate = $fromDate;

        $this->toDate = $toDate;
    }
}
