<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundAccount\Validation\Status;
use RZP\Models\FundAccount\Validation\Constants;
use RZP\Models\FundAccount\Validation\AccountStatus;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\FundAccount\Validation\Entity as Validation;

class BankAccount extends Base
{
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

        $this->createFundTransferAttempt();
    }

    /**
     * Unused right now, everything is async
     * @return void [type] [description]
     */
    public function processValidation()
    {
        $this->repo->assertTransactionActive();

        $fundTransferAttempt = $this->createFundTransferAttempt();

        // TODO: Add Trace FTA
        // TODO: Initiate FTA here.
    }

    protected function createFundTransferAttempt(): Attempt\Entity
    {
        $fundTransferAttemptInput = [
            Attempt\Entity::PURPOSE   => Attempt\Purpose::PENNY_TESTING,
            Attempt\Entity::NARRATION => $this->validation->merchant->getName(),
        ];

        $fta = (new Attempt\Core)->createWithBankAccount(
            $this->validation,
            $this->account,
            $fundTransferAttemptInput,
            true);

        $this->trace->info(TraceCode::FUND_TRANSFER_ATTEMPT_CREATED, $fta->toArrayPublic());

        return $fta;
    }

    public function setDefaultValuesForValidation()
    {
        if ($this->validation->getAmount() === null)
        {
            $this->validation->setAmount(Constants::DEFAULT_PENNY_TESTING_AMOUNT);
        }

        if ($this->validation->getCurrency() === null)
        {
            $this->validation->setCurrency(Constants::DEFAULT_PENNY_TESTING_CURRENCY);
        }
    }

    // ------------ Overridden Functions ---------
    /**
     * Updates validation entity when FTA is initiated
     * @param Attempt\Entity $fta
     */
    public function updateStatusAfterFtaInitiated(Attempt\Entity $fta)
    {
        $this->validation->batchFundTransfer()->associate($fta->batchFundTransfer);

        $this->repo->saveOrFail($this->validation);
    }

    /**
     * Updates validation entity before FTA recon
     *
     * @param array $input
     */
    public function updateWithDetailsBeforeFtaRecon(array $input)
    {
        if ($this->validation->getStatus() === Status::COMPLETED)
        {
            // Validation is already processed.
            // We might have reached here because of status check API call on FTA.
            return;
        }

        $beneficiaryName = $input['beneficiary_name'];

        if ((empty($beneficiaryName) === false) and ($beneficiaryName !== 'NA'))
        {
            $this->validation->setRegisteredName($beneficiaryName);

            $this->repo->saveOrFail($this->validation);

            return;
        }
    }

    /**
     * Updates validation entity after FTA recon
     *
     * @param array $input
     * @throws Exception\LogicException
     */
    public function updateStatusAfterFtaRecon(array $input)
    {
        $ftaStatus = $input['fta_status'];

        switch ($ftaStatus)
        {
            case Attempt\Status::PROCESSED:
                $this->updateValidationAfterFtaProcessed($input);
                break;

            case Attempt\Status::FAILED:
                $this->updateValidationAfterFtaFailed($input);
                break;

            case Attempt\Status::INITIATED:
                $this->trace->info(
                    TraceCode::FUND_TRANSFER_ATTEMPT_STILL_INITIATED,
                    [
                        'input' => $input,
                        'validation_status' => $this->validation->getStatus(),
                    ]);

                break;

            default:
                throw new Exception\LogicException(
                    'Unknown FTA status after recon. Should be either Processed or Failed',
                    null,
                    [
                        'input' => $input,
                        'validation_status' => $this->validation->getStatus(),
                    ]
                );
        }
    }

    // ------------ Helper Functions ---------

    /**
     * @param array $input
     */
    protected function updateValidationAfterFtaProcessed(array $input)
    {
        $this->markValidationAsCompleted(AccountStatus::ACTIVE);

        if ($this->validation->getRegisteredName() === null)
        {
            $traceArray = [
                'input'             => $input,
                'validation_status' => $this->validation->getStatus(),
            ];

            $this->slack->queue(
                TraceCode::BENEFICIARY_NAME_NOT_PRESENT,
                $traceArray,
                Constants::slackSettings()
            );

            $this->trace->warn(TraceCode::BENEFICIARY_NAME_NOT_PRESENT, $traceArray);
        }
    }

    /**
     * @param array $input
     */
    protected function updateValidationAfterFtaFailed(array $input)
    {
        if ($input['internal_error'] === false)
        {
            $this->markValidationAsCompleted(AccountStatus::INVALID);

            return;
        }

        $traceArray = [
            'input'             => $input,
            'validation_status' => $this->validation->getStatus(),
        ];

        $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_FAILED_WITH_CRITICAL_ERROR, $traceArray);

        $this->slack->queue(
            TraceCode::FUND_ACCOUNT_VALIDATION_FAILED_WITH_CRITICAL_ERROR,
            $traceArray,
            Constants::slackSettings()
        );

    }
}
