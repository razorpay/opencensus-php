<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway;

use RZP\Models\Base\Core as BaseCore;

abstract class Base extends BaseCore
{

    protected $accountNumber;

    protected $channel;

    protected $fromDate;

    protected $toDate;

    public function __construct($accountNumber, $channel, $fromDate, $toDate)
    {
        parent::__construct();

        $this->accountNumber = $accountNumber;

        $this->channel       = $channel;

        $this->fromDate      = $fromDate;

        $this->toDate        = $toDate;
    }

    abstract function pdf();

    abstract function csv();

    abstract function xlsx();

}
