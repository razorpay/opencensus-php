<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use Carbon\Carbon;

use RZP\Exception;
use Monolog\Logger;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Jobs\FavQueueForFTS;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\BadRequestException;
use RZP\Models\FundAccount\Validation\Core;
use RZP\Models\FundAccount\Validation\Status;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\BankAccount\OldNewIfscMapping;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\FundAccount\Validation\Constants;
use RZP\Models\Feature\Constants as MerchantFeature;
use RZP\Models\FundAccount\Validation\AccountStatus;
use RZP\Models\FundAccount\Validation\ErrorCodesMapping;
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

    public function preProcessValidation()
    {
        // Push to ledger sns when Fund Account Validation is created.
        // Only processing BankAccount because transaction is created only for this and not VPA.
        // Calling ledger at the start because validation is already created upto this stage,
        // and it's ledger entry can be created irrespective of creating the FTA
        // This function doesn't do anything when the merchant is on the reverse shadow flow
        (new Core)->processLedgerFav($this->validation);

        // If merchant is expecting utr, we can not return same utr, so need to hit fresh request
        $isUtrExposeEnabled = $this->validation->merchant->isFeatureEnabled(MerchantFeature::EXPOSE_FA_VALIDATION_UTR);

        if($isUtrExposeEnabled === false)
        {
            // TODO: right now we are fetching only completed FAV
            // so, user can still send simultaneous request to send money to same account number
            // will add mutex and validation over merchant, account_number, status later to solve this.
            $result = $this->repo->fund_account_validation
                ->fetchCompletedFAVByAccountNumber(
                    $this->account->getAccountNumber(),
                    Carbon::now()->subMonth(1)->getTimestamp());

            $ifscCode = $this->account->getIfscCode();

            $isDifferentBankIfsc = false;

            //if with the same account number and different ifscs an fav is attempted
            //adding a filter for ifsc on top of existing account number check to decide whether to pick from cache or hit fresh

            if ($result != null)
            {
                $resultIfsc = $result->getAttribute(Constants::IFSC_CODE);

                $resultIfscBank = strtoupper(substr($resultIfsc,0,4));

                $ifscCodeBank = strtoupper(substr($ifscCode,0,4));

                $isDifferentBankIfsc = ($resultIfscBank !== $ifscCodeBank);

                if ($isDifferentBankIfsc === true)
                {
                    //logging in case of an instance when same account number and diff ifsc
                    $this->trace->info(
                        TraceCode::FUND_ACCOUNT_VALIDATE_WITH_SAME_ACC_NUMBER_DIFF_IFSC,
                        [
                            'existing_ifsc' => $resultIfsc,
                            'id' => $result->getId(),
                            'requested_ifsc' => $ifscCode
                        ]
                    );
                }
            }

            // Now, If same account detail was already processed and it is active account
            // copy and return
            //also when there is a merger in banks the ifsc changes
            // if the ifsc is in the list of old ifsc mappings then we will do a fresh fav rather than retruning the same response

            $retryRequired = $this->isRetryRequired($ifscCode);

            if (($retryRequired === false) and
                (($result != null) and
                ($isDifferentBankIfsc === false)) and
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
        }

        $this->dispatchFavToQueue();
    }

    protected function isRetryRequired(string $ifsc)
    {
        $isRetryRequired = false;

        if ((array_key_exists($ifsc, OldNewIfscMapping::$oldToNewIfscMapping) === true))
        {
            $isRetryRequired = true;
        }

        $this->trace->info(
            TraceCode::FUND_ACCOUNT_VALIDATION_INVALIDATE_CACHE,
            [
                'is_retry'              => $isRetryRequired
            ]
        );

        return $isRetryRequired;
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
        $narration = preg_replace('/[^a-zA-Z0-9 ]+/', '', $this->validation->merchant->getName());
        $fundTransferAttemptInput = [
            Attempt\Entity::PURPOSE   => Attempt\Purpose::PENNY_TESTING,
            Attempt\Entity::NARRATION => $narration,
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
            $status = $input['fta_status'] ?? $input['status'];

            // We should not have reached here
            // but it is possible that we manually
            // marked FAV as failed and later FTA succeeded
            // or, AfterFtaRecon is being called multiple times.
            $this->trace->info(
                TraceCode::FUND_ACCOUNT_VALIDATION_ALREADY_PROCESSED,
                [
                    'fav_id' => $this->validation->getId(),
                    'fta_status' => $status,
                    'fav_status' => $this->validation->getStatus(),
                ]);

            // Since FAV does not main any reversed state, calling ledger explicitly here
            // as to create ledger entry when FTA is reversed.
            // This will only happen when FAV is already in it's final state, i.e., FAILED or COMPLETED.
            if (Attempt\Status::REVERSED === $status)
            {
                $ftsSourceAccountInformation = [
                    Transaction\Processor\Ledger\Base::FTS_FUND_ACCOUNT_ID => $input[Attempt\Entity::SOURCE_ACCOUNT_ID] ?? null,
                    Transaction\Processor\Ledger\Base::FTS_ACCOUNT_TYPE    => $input[Attempt\Entity::BANK_ACCOUNT_TYPE] ?? null
                ];

                // Will do nothing for reverse shadow
                (new Core)->processLedgerFav($this->validation, Attempt\Status::REVERSED, $ftsSourceAccountInformation);

                if (Core::shouldFavGoThroughLedgerReverseShadowFlow($this->validation) === true)
                {
                    try
                    {
                        $response = (new Transaction\Processor\Ledger\FundAccountValidation())
                            ->processValidationAndCreateJournalEntry($this->validation, $ftsSourceAccountInformation, Attempt\Status::REVERSED);
                    }
                    catch (\Throwable $e)
                    {
                        //This is a money credit flow, that is, there's no balance deduction
                        //Also there's no change to merchant balance here
                        //Thus, we wont disrupt the flow by throwing an exception here.
                        //TODO: Decide how to raise an alert and re-run the request to ledger here.
                        $this->trace->traceException(
                            $e,
                            Logger::ERROR,
                            TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR_IN_CREDIT_FLOW,
                            [
                                'fav_id' => $this->validation->getId(),
                            ]
                        );
                    }
                }
            }

            return;
        }

        $status = $input['fta_status'] ?? $input['status'];

        switch ($status)
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

            $this->trace->warning(TraceCode::BENEFICIARY_NAME_NOT_PRESENT, $traceArray);
        }

        $ftsSourceAccountInformation = [
            Transaction\Processor\Ledger\Base::FTS_FUND_ACCOUNT_ID => $input[Attempt\Entity::SOURCE_ACCOUNT_ID] ?? null,
            Transaction\Processor\Ledger\Base::FTS_ACCOUNT_TYPE    => $input[Attempt\Entity::BANK_ACCOUNT_TYPE] ?? null
        ];

        // In reverse shadow, nothing happens here
        (new Core)->processLedgerFav($this->validation, null, $ftsSourceAccountInformation);

        if (Core::shouldFavGoThroughLedgerReverseShadowFlow($this->validation) === true)
        {
            try
            {
                $response = (new Transaction\Processor\Ledger\FundAccountValidation())
                    ->processValidationAndCreateJournalEntry($this->validation, $ftsSourceAccountInformation);
            }
            catch (\Throwable $e)
            {
                //This does nothing to the merchant balance
                //This only changes other stuff in the CoA
                //Hence not throwing an exception here to avoid disruptions
                //TODO: Decide how to raise an alert and re-run the request to ledger here.
                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR_IN_CREDIT_FLOW,
                    [
                        'fav_id' => $this->validation->getId(),
                    ]
                );
            }
        }
    }

    /**
     * @param array $input
     */
    protected function updateValidationAfterFtaFailed(array $input)
    {
        //for fav we are now not relying on is_internal_error field sent by fts..
        //we will have the mapping at fav side and will mark according the bank status codes
        if ((array_key_exists('bank_status_code', $input)) and
            ($input['bank_status_code'] != null) and
            ($this->isStatusCodeInCompletedStateMap($input['bank_status_code'])))
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

        // This shall do nothing in reverse shadow mode.
        (new Core)->processLedgerFav($this->validation);
    }

    protected function isStatusCodeInCompletedStateMap($bankStatusCode)
    {
        //ErrorCodesMapping file has all the bank status codes which are non internal errors
        //so fav for all such error codes can be marked as completed
        if (in_array($bankStatusCode, ErrorCodesMapping::BANK_STATUS_CODE_MAP_FOR_COMPLETED_STATE, true) === true)
        {
            return true;
        }
        return false;

    }

    public function createTransactionForLedger(array $ledgerResponse)
    {
        $txnId   = $ledgerResponse[Entity::ID];
        $newBalance = Transaction\Processor\Ledger\FundAccountValidation::getMerchantBalanceFromLedgerResponse($ledgerResponse);

        $txn = (new Transaction\Processor\FundAccountValidation($this->validation))
            ->createTransactionForLedger($txnId, $newBalance);

        return $txn;
    }

    // The function to push the FAV ID to the FAV queue for FTS
    protected function dispatchFavToQueue()
    {
        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_FTS_JOB_REQUEST,
            [
                'fav_id' => $this->validation->getId(),
            ]
        );
        FavQueueForFTS::dispatch($this->mode, $this->validation->getId());
    }
}
