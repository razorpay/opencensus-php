<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\BankTransfer\Entity as E;
use RZP\Models\Batch\Helpers\BankTransfer as Helper;
use RZP\Models\BankTransfer\Core as BankTransferCore;
use RZP\Models\VirtualAccount\Provider as VirtualAccountProvider;
use RZP\Models\BankTransferRequest\Core as BankTransferRequestCore;

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

        if (in_array($provider, VirtualAccountProvider::LIVE_PROVIDERS, true) === false)
        {
            $entry[Header::STATUS]            = Status::FAILURE;
            $entry[Header::ERROR_DESCRIPTION] = 'Invalid entry';

            return;
        }

        $bankTransferRequest = (new BankTransferRequestCore())->create($bankTransferInsertInput,
                                                                       $provider,
                                                                       $bankTransferInsertInput,
                                                                       [
                                                                           'source'       => 'file',
                                                                           'request_from' => 'admin',
                                                                       ]
        );

        if ($bankTransferRequest !== null and $bankTransferRequest->getPayeeAccount() !== null)
        {
            $valid = $this->core->processBankTransfer($bankTransferRequest);
        }

        $entry[Header::STATUS] = $valid ? Status::SUCCESS : Status::FAILURE;
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }

    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[E::PAYEE_ACCOUNT]);
        unset($payloadEntry[E::PAYER_ACCOUNT]);
    }
}
