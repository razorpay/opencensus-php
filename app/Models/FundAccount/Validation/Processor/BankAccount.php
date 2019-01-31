<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Trace\TraceCode;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\FundAccount\Validation\Entity as Validation;

class BankAccount extends Base
{
    const PENNY_TESTING_NARRATION = 'here take 1 rupee lol';

    public function __construct(Validation $validation)
    {
        parent::__construct($validation);
    }

    public function getAccount(): BankAccountEntity
    {
        return $this->account;
    }

    public function preProcessValidation()
    {
        $this->repo->assertTransactionActive();

        $fundTransferAttempt = $this->createFundTransferAttempt();

        // TODO: Add Trace FTA
        // TODO: Dispatch FTA for processing
    }

    /**
     * Unused right now, everything is async
     * @return [type] [description]
     */
    public function processValidation()
    {
        $this->repo->assertTransactionActive();

        $fundTransferAttempt = $this->createFundTransferAttempt();

        // TODO: Add Trace FTA
        // TODO: Initiate FTA here.l
        // postFundTransfer will be called when its done execution.
    }

    protected function createFundTransferAttempt(): FundTransferAttempt\Entity
    {
        $fundTransferAttemptInput = [
            FundTransferAttempt\Entity::PURPOSE   => FundTransferAttempt\Purpose::PENNY_TESTING,
            FundTransferAttempt\Entity::NARRATION => self::PENNY_TESTING_NARRATION,
        ];

        $fta = (new FundTransferAttempt\Core)->createWithBankAccount(
            $this->validation,
            $this->account,
            $fundTransferAttemptInput);

        $this->trace->info(TraceCode::FUND_TRANSFER_ATTEMPT_CREATED, $fta->toArrayPublic());

        return $fta;
    }
}
