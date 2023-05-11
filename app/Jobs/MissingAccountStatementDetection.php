<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccountStatement as BAS;
use RZP\Models\BankingAccountStatement\Details as BASDetails;
use RZP\Models\BankingAccountStatement\Constants as BASConstants;

class MissingAccountStatementDetection extends Job
{
    protected $mode;

    protected $queueConfigKey = 'missing_account_statement_detect';

    protected $accountNumber;

    protected $channel;

    protected $basDetails;

    public $fromDate;

    public $toDate;

    protected $basCore;

    const JOB_DELAY = 5;

    const MAX_JOB_ATTEMPTS = 2;

    /**
     * MissingAccountStatementDetection constructor.
     *
     * @param string|null $mode
     * @param string      $accountNumber
     * @param             $fromDate
     * @param             $toDate
     * @param string      $channel
     */
    public function __construct(
        string $mode,
        string $accountNumber,
        $fromDate,
        $toDate,
        string $channel
    )
    {
        parent::__construct($mode);

        $this->accountNumber = $accountNumber;

        $this->channel = $channel;

        $this->setTimeRangeDetails($fromDate, $toDate);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $fromDate = Carbon::createFromTimestamp($this->fromDate, Timezone::IST)->format('d-m-Y');

            $toDate = Carbon::createFromTimestamp($this->toDate, Timezone::IST)->format('d-m-Y');

            $this->basCore = new BAS\Core;

            $this->basDetails = $this->basCore->getBasDetails($this->accountNumber, $this->channel, [
                BASDetails\Status::ACTIVE, BASDetails\Status::UNDER_MAINTENANCE
            ]);

            $this->trace->info(
                TraceCode::MISSING_STATEMENT_DETECTION_JOB_INIT,
                [
                    'merchant_id'         => $this->basDetails->getMerchantId(),
                    'channel'             => $this->channel,
                    BAS\Entity::FROM_DATE => $fromDate,
                    BAS\Entity::TO_DATE   => $toDate,
                    'from_time'           => $this->fromDate,
                    'to_time'             => $this->toDate
                ]);

            $app = App::getFacadeRoot();

            [$isCurrentIterationSuccessful, $isLastIteration, $pushNextJob, $missingStatementConfig] = $app['api.mutex']->acquireAndRelease(
                'missing_statement_detect_' . $this->accountNumber,
                function() {
                    $isLastIteration = false;

                    $isCurrentIterationSuccessful = true;

                    $missingStatementConfig = null;

                    $pushNextJob = false;

                    if (isset($this->basDetails) === false)
                    {
                        $isCurrentIterationSuccessful = false;

                        $this->trace->info(
                            TraceCode::BAS_DETAILS_NOT_FOUND,
                            [
                                'merchant_id' => $this->basDetails->getMerchantId(),
                                'channel'     => $this->channel
                            ]);
                    }
                    else
                    {
                        if ($this->basDetails->getCreatedAt() > $this->fromDate)
                        {
                            $isLastIteration = true;
                        }

                        $fetchedBAS = $this->repoManager->banking_account_statement->getLatestForGivenPostedDateRangeBy(
                            $this->accountNumber,
                            $this->channel,
                            $this->fromDate,
                            $this->toDate
                        );

                        $missingStatementConfig = $this->basCore->fetchMissingStatementConfigFor($this->accountNumber, $this->channel);

                        if ($fetchedBAS === null)
                        {
                            if ($isLastIteration === false)
                            {
                                $pushNextJob = true;
                            }
                        }
                        else
                        {
                            $statementFetchInput = $this->getStatementFetchInput($fetchedBAS);

                            [$fetchMore, $paginationKey, $fetchedStatements] = $this->basCore->fetchAccountStatementWithRange($statementFetchInput, false, false, false);

                            [$matchedBASinFetchedStatement, $fetchedBAS] = $this->basCore->findMatchingBASInFetchedStatements($fetchedBAS, $fetchedStatements);

                            if ($matchedBASinFetchedStatement === null)
                            {
                                $isCurrentIterationSuccessful = false;

                                $this->trace->error(
                                    TraceCode::MISSING_STATEMENT_DETECTION_BAS_NOT_FOUND_IN_STATEMENT,
                                    [
                                        'merchant_id'        => $this->basDetails->getMerchantId(),
                                        'channel'            => $this->channel,
                                        'from_time'          => $this->fromDate,
                                        'to_time'            => $this->toDate,
                                        'bas_entity'         => $fetchedBAS,
                                        'fetched_statements' => count($fetchedStatements)
                                    ]);
                            }
                            else
                            {
                                $amountDiff = $matchedBASinFetchedStatement->getBalance() - $fetchedBAS->getBalance();

                                if ($amountDiff === 0)
                                {
                                    $isLastIteration = true;

                                    $this->trace->info(
                                        TraceCode::MISSING_STATEMENT_DETECTION_NO_DIFF,
                                        [
                                            'merchant_id'       => $this->basDetails->getMerchantId(),
                                            'channel'           => $this->channel,
                                            'fetched_statement' => $matchedBASinFetchedStatement,
                                            'bas_entity'        => $fetchedBAS,
                                            'from_time'         => $this->fromDate,
                                            'to_time'           => $this->toDate
                                        ]);
                                }
                                else
                                {
                                    $newConfigValue = $this->prepareConfigForUpdate($amountDiff, $fetchedBAS, $missingStatementConfig);

                                    $missingStatementConfig = $this->basCore->updateMissingStatementConfigFor($this->accountNumber, $this->channel, $newConfigValue);

                                    if ($isLastIteration === false)
                                    {
                                        $pushNextJob = true;
                                    }
                                }
                            }
                        }
                    }

                    return [$isCurrentIterationSuccessful, $isLastIteration, $pushNextJob, $missingStatementConfig];
                },
                100,
                ErrorCode::BAD_REQUEST_MISSING_STATEMENT_DETECTION_IN_PROGRESS
            );

            $this->pushNextOrSetComplete($pushNextJob, $isLastIteration, $isCurrentIterationSuccessful, $missingStatementConfig);

            $this->trace->info(
                TraceCode::MISSING_STATEMENT_DETECTION_JOB_COMPLETE,
                [
                    'merchant_id'         => $this->basDetails->getMerchantId(),
                    'channel'             => $this->channel,
                    BAS\Entity::FROM_DATE => $fromDate,
                    BAS\Entity::TO_DATE   => $toDate,
                    'from_time'           => $this->fromDate,
                    'to_time'             => $this->toDate
                ]);

            $this->delete();
        }
        catch (\Exception $ex)
        {
            $this->trace->error(
                TraceCode::MISSING_STATEMENT_DETECTION_JOB_EXCEPTION,
                [
                    'merchant_id' => $this->basDetails->getMerchantId(),
                    'channel'     => $this->channel,
                    'from_time'   => $this->fromDate,
                    'to_time'     => $this->toDate,
                    'exception'   => $ex,
                    'attempts'    => $this->attempts()
                ]);

            if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
            {
                $this->trace->info(
                    TraceCode::MISSING_STATEMENT_DETECTION_JOB_DELETED,
                    [
                        'merchant_id' => $this->basDetails->getMerchantId(),
                        'channel'     => $this->channel,
                        'from_time'   => $this->fromDate,
                        'to_time'     => $this->toDate,
                        'exception'   => $ex,
                        'attempts'    => $this->attempts()
                    ]);

                $this->delete();
            }
            else
            {
                $this->release(self::JOB_DELAY);
            }
        }
    }

    private function setTimeRangeDetails($fromDate, $toDate)
    {
        $this->fromDate = Carbon::createFromTimestamp($fromDate, Timezone::IST);

        $this->toDate = Carbon::createFromTimestamp($toDate, Timezone::IST);

        if (($fromDate === null) and
            ($toDate === null))
        {
            $currentTime = Carbon::now(Timezone::IST);

            $this->fromDate = $currentTime->startOfMonth()->startOfDay()->timestamp;

            $this->toDate = $currentTime->timestamp;

            return;
        }

        if (($fromDate === null) and
            ($toDate !== null))
        {
            $this->fromDate = Carbon::createFromTimestamp($toDate, Timezone::IST)->startOfMonth()->startOfDay()->timestamp;

            $this->toDate = $this->toDate->timestamp;

            return;
        }

        $this->fromDate = $this->fromDate->timestamp;

        $this->toDate = $this->toDate->timestamp;
    }

    private function getStatementFetchInput($fetchedBAS): array
    {
        $fetchDate = Carbon::createFromTimestamp($fetchedBAS->getTransactionDate(), Timezone::IST);

        $paginationKey = ($this->channel === BAS\Channel::RBL) ? null : '';

        $statementFetchInput = [
            BAS\Entity::FROM_DATE      => $fetchDate->timestamp,
            BAS\Entity::TO_DATE        => $fetchDate->endOfDay()->timestamp,
            BAS\Entity::CHANNEL        => $this->channel,
            BAS\Entity::ACCOUNT_NUMBER => $this->accountNumber,
            'pagination_key'           => $paginationKey
        ];

        return $statementFetchInput;
    }

    private function prepareConfigForUpdate(
        $amountDifference,
        BAS\Entity $comparedBASEntity,
        $currentConfigValue
    )
    {
        if ($currentConfigValue === null)
        {
            $currentConfigValue = [];
        }

        $amountDiffType = 'no_diff';

        if ($amountDifference < 0)
        {
            $amountDiffType = "missing_debit";
        }
        else
        {
            if ($amountDifference > 0)
            {
                $amountDiffType = "missing_credit";
            }
        }

        if (empty($currentConfigValue[BASConstants::MISMATCH_DATA]) === true)
        {
            $currentConfigValue[BASConstants::MISMATCH_DATA] = [];
        }

        $currentConfigValue[BASConstants::MISMATCH_DATA][] = [
            BAS\Entity::FROM_DATE => $this->fromDate,
            BAS\Entity::TO_DATE   => $this->toDate,
            'mismatch_amount'     => $amountDifference,
            'mismatch_type'       => $amountDiffType,
            'analysed_bas_id'     => $comparedBASEntity->getId()
        ];

        $currentConfigValue['completed'] = false;

        return $currentConfigValue;
    }

    private function pushNextOrSetComplete($pushNextJob, $isLastIteration, $isCurrentIterationSuccessful, $missingStatementConfig): void
    {
        if ($pushNextJob === true)
        {
            $this->pushNextJobForMissingStatementDetection();
        }
        else
        {
            if (($isLastIteration === true) and
                ($isCurrentIterationSuccessful === true))
            {
                $this->setDetectionCompletedAndTriggerFetch($missingStatementConfig);
            }
        }
    }

    private function pushNextJobForMissingStatementDetection(): void
    {
        $nextJobFromTime = Carbon::createFromTimestamp($this->fromDate, Timezone::IST)->subMonth()->startOfDay()->timestamp;

        $nextJobToTime = Carbon::createFromTimestamp($this->fromDate, Timezone::IST)->subDay()->endOfDay()->timestamp;

        MissingAccountStatementDetection::dispatch(
            $this->mode,
            $this->accountNumber,
            $nextJobFromTime,
            $nextJobToTime,
            $this->channel
        );

        $this->trace->info(
            TraceCode::MISSING_STATEMENT_DETECTION_PUSH_NEXT_JOB,
            [
                'merchant_id'    => $this->basDetails->getMerchantId(),
                'channel'        => $this->channel,
                'next_from_time' => $nextJobFromTime,
                'next_to_time'   => $nextJobToTime
            ]);
    }

    private function setDetectionCompletedAndTriggerFetch($missingStatementConfig)
    {
        if ($missingStatementConfig !== null)
        {
            $missingStatementConfig['completed'] = true;

            $this->basCore->updateMissingStatementConfigFor($this->accountNumber, $this->channel, $missingStatementConfig);

            $this->trace->info(
                TraceCode::MISSING_STATEMENT_DETECTION_UPDATE_CONFIG,
                [
                    'merchant_id' => $this->basDetails->getMerchantId(),
                    'channel'     => $this->channel,
                    'config'      => $missingStatementConfig,
                    'step'        => 'set_to_completed'
                ]);

            if ((array_key_exists(BASConstants::MISMATCH_DATA, $missingStatementConfig)) and
                (empty($missingStatementConfig[BASConstants::MISMATCH_DATA]) === false))
            {
                $this->trace->info(
                    TraceCode::MISSING_STATEMENT_DETECTION_TRIGGER_FETCH,
                    [
                        'merchant_id' => $this->basDetails->getMerchantId(),
                        'channel'     => $this->channel,
                        'config'      => $missingStatementConfig
                    ]);

                $this->basCore->triggerMissingStatementFetchForIdentifiedTimeRange($this->basDetails->getMerchantId(), $this->accountNumber, $this->channel, $missingStatementConfig);
            }
        }

        $this->trace->info(
            TraceCode::MISSING_STATEMENT_DETECTION_COMPLETED,
            [
                'merchant_id' => $this->basDetails->getMerchantId(),
                'channel'     => $this->channel,
                'config'      => $missingStatementConfig
            ]);
    }
}
