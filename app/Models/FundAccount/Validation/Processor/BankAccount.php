<?php

namespace RZP\Models\FundAccount\Validation\Processor;


use RZP\Models\Pricing\Fee;
use RZP\Models\FundAccount\Validation\Status;
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

    public function processValidation()
    {
        $this->repo->assertTransactionActive();

        $fundTransferAttempt = $this->createFundTransferAttempt();

        // TODO: Add Trace FTA
        // TODO: Initiate FTA here.
        // postFundTransfer will be called when its done execution.
    }

    public function postFundTransfer(array $input)
    {
        $this->repo->assertTransactionActive();

        $bankAccount = $this->getAccount();

        $beneficiaryName = $input[Entity::REGISTERED_BENEFICIARY_NAME] ?? '';

        $bankAccount->setRegisteredBeneficiaryName($beneficiaryName);

        $this->repo->saveOrFail($bankAccount);

        $fundAccountData = [
            Validation::STATUS              => Status::VALIDATED,
            Validation::ERROR_CODE          => 'lala',
            Validation::ERROR_DESCRIPTION   => 'lala',
            Validation::INTERNAL_ERROR_CODE => 'lala',
        ];

        $this->validation->edit($fundAccountData, 'postProcess');

        // 2. update instrument status and fees

        // 3. create transaction
    }

    protected function createFundTransferAttempt(): FundTransferAttempt\Entity
    {
        // TODO: Mode, Narration?
        $fundTransferAttemptInput = [
            FundTransferAttempt\Entity::PURPOSE   => FundTransferAttempt\Purpose::PENNY_TESTING,
            FundTransferAttempt\Entity::NARRATION => self::PENNY_TESTING_NARRATION,
        ];

        return (new FundTransferAttempt\Core)->createWithBankAccount(
            $this->validation,
            $this->account,
            $fundTransferAttemptInput);
    }
}
