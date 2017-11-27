<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Helpers\BankTransfer as Helper;
use RZP\Models\BankTransfer\Core as BankTransferCore;

class BankTransfer extends Base
{
    /**
     * @var BankTransferCore
     */
    protected $core;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->core = new BankTransferCore;
    }

    protected function processEntry(array & $entry)
    {
        $bankTransferInsertInput = Helper::getBankTransferInsertInput($entry);

        $provider = $entry[Header::PROVIDER];

        $valid = $this->core->process($bankTransferInsertInput, $provider);

        $entry[Header::STATUS] = $valid ? Status::SUCCESS : Status::FAILURE;
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
