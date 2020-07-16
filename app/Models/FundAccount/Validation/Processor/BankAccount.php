<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use Carbon\Carbon;

use RZP\Exception;
use Monolog\Logger;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Illuminate\Support\Arr;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as Table;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundAccount\Validation\Status;
use RZP\Models\FundAccount\Validation\Constants;
use RZP\Models\FundAccount\Validation\AccountStatus;
use RZP\Models\FundAccount\Validation\Entity as Validation;

class BankAccount extends Base
{
    public function __construct(Validation $validation)
    {
        parent::__construct($validation);
    }

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

    /**
     * @throws Exception\BadRequestException
     */
    public function validateRetry()
    {
        if ($this->validation->getStatus() !== Status::CREATED)
        {
            $e = [
                'validation'    => $this->validation->getId(),
                'status'        => $this->validation->getStatus()
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_ALREADY_PROCESSED, null, $e);
        }

        $notFailedFTAs = $this->repo->fund_transfer_attempt->getAttemptBySourceIdAndNotFailed($this->validation->getId(), Table::FUND_ACCOUNT_VALIDATION);

        if ($notFailedFTAs->count() !== 0)
        {
            $e = [
                'validation'    => $this->validation->getId(),
                'active_ftas'   => $notFailedFTAs->toArray(),
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_HAS_ACTIVE_FTA, null, $e);
        }
    }

    public function preProcessValidation()
    {
        // TODO: right now we are fetching only completed FAV
        // so, user can still send simultaneous request to send money to same account number
        // will add mutex and validation over merchant, account_number later to solve this.
        $result = $this->repo->fund_account_validation
                       ->fetchCompletedFAVByAccountNumber(
                           $this->account->getAccountNumber(),
                           Carbon::now()->subMonth(1)->getTimestamp());

        // If same account detail was already processed and it is active account
        // copy and return
        if (($result != null) and
            ($result->getAccountStatus() === AccountStatus::ACTIVE))
        {
            $beneficiaryName = $result->getRegisteredName();

            // if beneficiary Name exist then only copy details
            // Also, not checking for empty because older beneficiary Names
            // can still have names from $benificiaryNameNotAllowedArray
            if ($this->isBeneficiaryNamePresent($beneficiaryName) === true)
            {
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
            // If for any reason we failed to create fund account validation.
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

        // We need to retry after some time.
        // This will be done by creating another FTA from retry CRON.
        $this->setRetryAt();
    }

    protected function setRetryAt()
    {
        // Calculate Retry At value
        $nextAttempt = $this->validation->getAttempts() + 1;

        $retryAfter = Arr::get(self::$attemptToRetryAfterSecondsMap, $nextAttempt);

        if ($retryAfter == null)
        {
            $retryAfter = self::$attemptToRetryAfterSecondsMap[7];
        }

        $retryAt = Carbon::now(Timezone::IST)->addSeconds($retryAfter)->getTimestamp();

        $this->validation->setRetryAt($retryAt);

        $this->repo->saveOrFail($this->validation);
    }
}
