<?php

namespace RZP\Models\BankingAccountStatement\StatementGenerator\Gateway;

use RZP\Models\Base\Core as BaseCore;


abstract class Base extends BaseCore
{

    protected $accountNumber;
    protected $channel;

    public function __construct($accountNumber, $channel)
    {
        parent::__construct();

        $this->accountNumber = $accountNumber;
        $this->channel = $channel;
    }


    abstract function pdf();

    abstract function csv();

    abstract function xlsx();


}
