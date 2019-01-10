<?php

namespace RZP\Models\Batch\Processor;

use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Customer;
use RZP\Models\BankAccount;
use RZP\Models\Batch\Header;
use RZP\Models\Payout as PayoutModel;
use RZP\Models\Batch\Helpers\Payout as Helper;

class Payout extends Base
{
    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);
    }

    protected function processEntry(array & $entry)
    {
        // Nothing here for now - TODO as a part of Razorpay X Bulk Payout
    }
}
