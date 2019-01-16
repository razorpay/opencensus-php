<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\FundAccount\Validation\Entity as Validation;

class BankAccount extends Base
{
    public function __construct(Validation $validation)
    {
        parent::__construct($validation);
    }

    public function preProcessValidation()
    {
        $this->repo->assertTransactionActive();

        $fundTransferAttempt = $this->createFundTransferAttempt();

        // TODO: Add Trace FTA
        // TODO: Dispatch FTA for processing
    }

    public function processValidation()
    {
        $this->repo->assertTransactionActive();

        $fundTransferAttempt = $this->createFundTransferAttempt();

        // TODO: Add Trace FTA
        // TODO: Initiate FTA here.
        // postFundTransfer will be called when its done execution.
    }

    public function postFundTransfer()
    {
        // inside transaction

        // 1. update bank account

        // 2. update instrument status and fees

        // 3. create transaction
    }

    protected function createFundTransferAttempt(): FundTransferAttempt\Entity
    {
        // Mode, Narration?
        $fundTransferAttemptInput = [
            FundTransferAttempt\Entity::PURPOSE => FundTransferAttempt\Purpose::PENNY_TESTING,
        ];

        return (new FundTransferAttempt\Core)->createWithBankAccount(
            $this->validation,
            $this->account,
            $fundTransferAttemptInput);
    }
}
