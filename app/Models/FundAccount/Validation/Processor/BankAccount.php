<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use Carbon\Carbon;

use RZP\Exception;
use Monolog\Logger;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\BadRequestException;
use RZP\Models\FundAccount\Validation\Status;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundAccount\Validation\Constants;
use RZP\Models\Feature\Constants as MerchantFeature;
use RZP\Models\FundAccount\Validation\AccountStatus;
use RZP\Models\FundAccount\Validation\Entity as Validation;

class BankAccount extends Base
{
    public function __construct(Validation $validation)
    {
        parent::__construct($validation);
    }

    protected static $blockedBankCodesForFundAccountValidation = [

    ];

    protected static $attemptToRetryAfterSecondsMap = [
        2 => 1800,       // 30 Minutes
        3 => 5400,       // 90 Minutes
        4 => 12600,      // 3 Hours 30 Minutes
        5 => 27000,      // 7 Hours 30 Minutes
        6 => 55800,      // 15 Hours 30 Minutes
        7 => 86400,      // 24 Hours
        // Thereafter 24 Hours
    ];

    protected static $benificiaryNameNotAllowedArray = [
        'Unregistered',
        'UNREGISTERED',
        'RBL BANK',
        'ICICI BANK NODAL A',
        'IMPS CUSTOMER',
    ];

    public function preProcessValidation()
    {
        // TODO: right now we are fetching only completed FAV
        // so, user can still send simultaneous request to send money to same account number
        // will add mutex and validation over merchant, account_number, status later to solve this.
        $result = $this->repo->fund_account_validation
                       ->fetchCompletedFAVByAccountNumber(
                           $this->account->getAccountNumber(),
                           Carbon::now()->subMonth(1)->getTimestamp());

        $doNotRetry = $this->validation->merchant->isFeatureEnabled(MerchantFeature::EXPOSE_FA_VALIDATION_UTR);

        // If merchant is expecting utr, we can not return same utr, so no retry
        // Now, If same account detail was already processed and it is active account
        // copy and return
        if (($doNotRetry === false) and
            ($result != null) and
            ($result->getAccountStatus() === AccountStatus::ACTIVE))
        {
            $beneficiaryName = $result->getRegisteredName() ?? '';

            // if beneficiary Name exist then only copy details
            // Also, not checking for empty because older beneficiary Names
            // can still have names from $benificiaryNameNotAllowedArray
            if ($this->isBeneficiaryNamePresent($beneficiaryName) === true)
            {
                $this->trace->info(
                    TraceCode::FUND_ACCOUNT_ALREADY_VALIDATED,
                    $result->toArrayPublic()
                );

                $this->copyFundAccountDetailsAndMarkAsCompleted($result);

                return;
            }
        }

        // initiate a fund transfer if this account number is new.
        $this->initiateFundTransfer();
    }

    protected function copyFundAccountDetailsAndMarkAsCompleted(Entity $result)
    {
        $this->validation->setRegisteredName($result->getRegisteredName());

        $this->validation->setAttempts($this->validation->getAttempts() - 1);

        $this->validation->setUtr($result->getUtr());

        $this->markValidationAsCompleted(AccountStatus::ACTIVE, null);
    }

    protected function initiateFundTransfer()
    {
        try
        {
            $this->createFundTransferAttempt();
        }
        catch (\Throwable $e)
        {
            // If for any reason we failed to create fund transfer attempt.
            // We should not revert the created Fund Account Validation.
            // Rather we should retry creating FTA.

            $traceArray = [
                'fund_account_validation_id'    => $this->validation->getId()
            ];

            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FUND_ACCOUNT_VALIDATION_FTA_CREATION_FAILED,
                $traceArray
            );

            $this->slack->queue(
                TraceCode::FUND_ACCOUNT_VALIDATION_FTA_CREATION_FAILED,
                $traceArray,
                Constants::slackSettings()
            );
        }
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
        if (Status::hasFinalStatus($this->validation) === true)
        {
            // We should not have reached here
            // but it is possible that we manually
            // marked FAV as failed and later FTA succeeded
            // or, FTS webhooks were being called multiple times.
            $this->trace->info(
                TraceCode::FUND_ACCOUNT_VALIDATION_ALREADY_PROCESSED,
                [
                    'fav_id' => $this->validation->getId(),
                ]);

            return;
        }

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
        if (empty( $this->validation->getRegisteredName()) === false)
        {
            // Registered Name is already set.
            // We might have reached here because of status check API call on FTA.
            return;
        }

        $beneficiaryName = $input['beneficiary_name'] ?? '';

        if ($this->isBeneficiaryNamePresent($beneficiaryName) === true)
        {
            $this->validation->setRegisteredName($beneficiaryName);

            $this->repo->saveOrFail($this->validation);

            return;
        }
    }

    public function isBeneficiaryNamePresent(string $beneficiaryName)
    {
        if ((empty($beneficiaryName) === true) or
            (array_search(
                $beneficiaryName,
                self::$benificiaryNameNotAllowedArray)) === true)
        {
            return false;
        }

        return true;
    }

    /**
     * Updates validation entity after FTA recon
     *
     * @param array $input
     * @throws Exception\LogicException
     */
    public function updateStatusAfterFtaRecon(array $input)
    {
        if (Status::hasFinalStatus($this->validation) === true)
        {
            // We should not have reached here
            // but it is possible that we manually
            // marked FAV as failed and later FTA succeeded
            // or, AfterFtaRecon is being called multiple times.
            $this->trace->info(
                TraceCode::FUND_ACCOUNT_VALIDATION_ALREADY_PROCESSED,
                [
                    'fav_id' => $this->validation->getId(),
                    'fta_status' => $input['fta_status'],
                    'fav_status' => $this->validation->getStatus(),
                ]);

            return;
        }

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
        $this->markValidationAsCompleted(AccountStatus::ACTIVE, $input[Validation::UTR]);

        if ($this->validation->getRegisteredName() === null)
        {
            $traceArray = [
                'input'             => $input,
                'validation_status' => $this->validation->getStatus(),
            ];

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
            $this->markValidationAsCompleted(AccountStatus::INVALID, $input[Validation::UTR]);

            return;
        }

        $traceArray = [
            'input'             => $input,
            'validation_status' => $this->validation->getStatus(),
        ];

        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_FAILED_CRITICAL_ERROR, $traceArray);

        $this->markValidationAsFailed();

        (new Reversal\Core)->reverseForFundAccountValidation($this->validation);
    }

    public function validateFundAccountBeforeCreating()
    {
        $bankCode = $this->account->getBankCode();

        if (in_array($bankCode, self::$blockedBankCodesForFundAccountValidation) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_BANK_NOT_ALLOWED,
                null,
                [
                    'bank' => $bankCode,
                ]);
        }
    }
}
