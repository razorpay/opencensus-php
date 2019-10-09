<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Constants\Entity as Table;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\FundAccount\Validation\Status;
use RZP\Models\FundAccount\Validation\Constants;
use RZP\Models\FundAccount\Validation\Traits\FtaStatus;
use RZP\Models\FundAccount\Validation\Entity as Validation;

class Vpa extends Base
{
    use FtaStatus;

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
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_HAS_ACTIVE_FTA, null, $e);
        }
    }

    public function preProcessValidation()
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

    /**
     *
     * @return void [type] [description]
     */
    public function processValidation()
    {
        return;
    }

    protected function createFundTransferAttempt(): Attempt\Entity
    {
        $fundTransferAttemptInput = [
            Attempt\Entity::PURPOSE   => Attempt\Purpose::PENNY_TESTING,
            Attempt\Entity::NARRATION => $this->validation->merchant->getName(),
        ];

        $fta = (new Attempt\Core)->createWithVpa(
            $this->validation,
            $this->account,
            $fundTransferAttemptInput);

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
}
