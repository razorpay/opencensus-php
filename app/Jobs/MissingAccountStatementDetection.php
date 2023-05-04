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

class MissingAccountStatementDetection extends Job
{
    protected $app;

    protected $mode;

    protected $queueConfigKey = 'missing_account_statement_detect';

    protected $accountNumber;

    protected $channel;

    protected $basDetails;

    public $startDate;

    public $endDate;

    const JOB_DELAY = 5;

    const MAX_JOB_ATTEMPTS = 2;

    /**
     * MissingAccountStatementDetection constructor.
     *
     * @param string|null $mode
     * @param string      $accountNumber
     * @param             $startDate
     * @param             $endDate
     * @param string      $channel
     */
    public function __construct(
        string $mode,
        string $accountNumber,
        $startDate,
        $endDate,
        string $channel
    )
    {
        parent::__construct($mode);

        $this->accountNumber = $accountNumber;

        $this->channel = $channel;

        $this->setTimeRangeDetails($startDate, $endDate);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $app = App::getFacadeRoot();

        try
        {
            $startDate = Carbon::createFromTimestamp($this->startDate, Timezone::IST)->format('d-m-Y');

            $endDate = Carbon::createFromTimestamp($this->endDate, Timezone::IST)->format('d-m-Y');

            $this->trace->info(
                TraceCode::MISSING_STATEMENT_DETECTION_JOB_INIT,
                [
                    'merchant_id' => $this->accountNumber,
                    'channel'     => $this->channel,
                    'start_date'  => $startDate,
                    'end_date'    => $endDate,
                    'start_time'  => $this->startDate,
                    'end_time'    => $this->endDate
                ]);

            $basCore = new BAS\Core;

            $this->basDetails = $basCore->getBasDetails($this->accountNumber, $this->channel, [
                BASDetails\Status::ACTIVE, BASDetails\Status::UNDER_MAINTENANCE
            ]);

            [$isCurrentIterationSuccessful, $isLastIteration, $pushNextJob, $missingStatementConfig] = $app['api.mutex']->acquireAndRelease(
                'missing_statement_detect_' . $this->accountNumber,
                function() use ($basCore) {
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
                        if ($this->basDetails->getCreatedAt() > $this->startDate)
                        {
                            $isLastIteration = true;
                        }

                        $fetchedBAS = $this->repoManager->banking_account_statement->getLatestForGivenPostedDateRangeBy(
                            $this->basDetails->getAccountNumber(),
                            $this->channel,
                            $this->startDate,
                            $this->endDate
                        );

                        $missingStatementConfig = $this->fetchMissingStatementConfigFor($this->basDetails->getAccountNumber());

                        if ($fetchedBAS === null)
                        {
                            if ($isLastIteration === false)
                            {
                                $pushNextJob = true;
                            }
                        }
                        else
                        {
                            $fetchDate = Carbon::createFromTimestamp($fetchedBAS->getTransactionDate(), Timezone::IST);

                            $paginationKey = ($this->channel === BAS\Channel::RBL) ? null : '';

                            $statementFetchInput = [
                                BAS\Entity::FROM_DATE      => $fetchDate->timestamp,
                                BAS\Entity::TO_DATE        => $fetchDate->endOfDay()->timestamp,
                                BAS\Entity::CHANNEL        => $this->channel,
                                BAS\Entity::ACCOUNT_NUMBER => $this->basDetails->getAccountNumber(),
                                'pagination_key'           => $paginationKey
                            ];

                            [$fetchMore, $paginationKey, $fetchedStatements] = $basCore->fetchAccountStatementWithRange($statementFetchInput, false, false, false);

                            [$matchedBASinFetchedStatement, $fetchedBAS] = $basCore->findMatchingBASInFetchedStatements($fetchedBAS, $fetchedStatements);

                            if ($matchedBASinFetchedStatement === null)
                            {
                                $isCurrentIterationSuccessful = false;

                                $this->trace->error(
                                    TraceCode::MISSING_STATEMENT_DETECTION_BAS_NOT_FOUND_IN_STATEMENT,
                                    [
                                        'merchant_id'        => $this->basDetails->getMerchantId(),
                                        'channel'            => $this->channel,
                                        'start_time'         => $this->startDate,
                                        'end_time'           => $this->endDate,
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
                                            'start_time'        => $this->startDate,
                                            'end_time'          => $this->endDate
                                        ]);
                                }
                                else
                                {
                                    $newConfigValue = $this->prepareConfigForUpdate($amountDiff, $fetchedBAS, $missingStatementConfig);

                                    $missingStatementConfig = $this->updateMissingStatementConfigFor($this->basDetails->getAccountNumber(), $newConfigValue);

                                    $this->trace->info(
                                        TraceCode::MISSING_STATEMENT_DETECTION_UPDATE_CONFIG,
                                        [
                                            'merchant_id' => $this->basDetails->getMerchantId(),
                                            'channel'     => $this->channel,
                                            'config'      => $newConfigValue,
                                            'step'        => 'difference_in_closing_balance'
                                        ]);

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
                60,
                ErrorCode::MISSING_STATEMENT_DETECTION_IN_PROGRESS,
                3
            );

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

            $this->trace->info(
                TraceCode::MISSING_STATEMENT_DETECTION_JOB_COMPLETE,
                [
                    'merchant_id' => $this->basDetails->getMerchantId(),
                    'channel'     => $this->channel,
                    'start_date'  => $startDate,
                    'end_date'    => $endDate,
                    'start_time'  => $this->startDate,
                    'end_time'    => $this->endDate
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
                    'start_time'  => $this->startDate,
                    'end_time'    => $this->endDate,
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
                        'start_time'  => $this->startDate,
                        'end_time'    => $this->endDate,
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

    private function setTimeRangeDetails($startDate, $endDate)
    {
        $this->startDate = Carbon::createFromTimestamp($startDate, Timezone::IST);

        $this->endDate = Carbon::createFromTimestamp($endDate, Timezone::IST);

        if (($startDate === null) and
            ($endDate === null))
        {
            $currentTime = Carbon::now(Timezone::IST);

            $this->startDate = $currentTime->startOfMonth()->addDay()->startOfDay()->timestamp;

            $this->endDate = $currentTime->timestamp;

            return;
        }

        if (($startDate === null) and
            ($endDate !== null))
        {
            $this->startDate = Carbon::createFromTimestamp($endDate, Timezone::IST)->startOfMonth()->addDay()->startOfDay()->timestamp;

            $this->endDate = $this->endDate->timestamp;

            return;
        }

        $this->startDate = $this->startDate->timestamp;

        $this->endDate = $this->endDate->timestamp;
    }

    private function fetchMissingStatementConfigFor(string $accountNumber)
    {
        $configKey = Admin\ConfigKey::PREFIX . 'rx_ca_missing_statement_detection_' . $this->channel;

        $config = (new Admin\Service())->getConfigKey(['key' => $configKey]);

        if ((empty($config) === true) or
            (array_key_exists($accountNumber, $config) === false))
        {
            return null;
        }

        return $config[$accountNumber];
    }

    private function updateMissingStatementConfigFor(string $accountNumber, array $config)
    {
        $app = App::getFacadeRoot();

        return $app['api.mutex']->acquireAndRelease(
            'update_redis_missing_statement_detect_' . $this->channel,
            function() use ($accountNumber, $config) {
                $configKey = Admin\ConfigKey::PREFIX . 'rx_ca_missing_statement_detection_' . $this->channel;

                $existingConfigs = (new Admin\Service())->getConfigKey(['key' => $configKey]);

                if ($existingConfigs === null)
                {
                    $existingConfigs = [];
                }

                $existingConfigs[$accountNumber] = $config;

                (new BAS\Core)->setConfigKeys([$configKey => $existingConfigs]);

                return $existingConfigs[$accountNumber];
            },
            60,
            ErrorCode::MISSING_STATEMENT_DETECTION_UPDATE_IN_PROGRESS,
            3
        );
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

        if (empty($currentConfigValue['mismatch_data']) === true)
        {
            $currentConfigValue['mismatch_data'] = [];
        }

        $currentConfigValue['mismatch_data'][] = [
            'start_date'      => $this->startDate,
            'end_date'        => $this->endDate,
            'mismatch_amount' => $amountDifference,
            'mismatch_type'   => $amountDiffType,
            'analysed_bas_id' => $comparedBASEntity->getId()
        ];

        $currentConfigValue['completed'] = false;

        return $currentConfigValue;
    }

    private function pushNextJobForMissingStatementDetection(): void
    {
        $nextJobStartTime = Carbon::createFromTimestamp($this->startDate, Timezone::IST)->subMonth()->startOfDay()->timestamp;

        $nextJobEndTime = Carbon::createFromTimestamp($this->startDate, Timezone::IST)->subDay()->endOfDay()->timestamp;

        MissingAccountStatementDetection::dispatch(
            $this->mode,
            $this->accountNumber,
            $nextJobStartTime,
            $nextJobEndTime,
            $this->channel
        );

        $this->trace->info(
            TraceCode::MISSING_STATEMENT_DETECTION_PUSH_NEXT_JOB,
            [
                'merchant_id'     => $this->basDetails->getMerchantId(),
                'channel'         => $this->channel,
                'next_start_time' => $nextJobStartTime,
                'next_end_time'   => $nextJobEndTime
            ]);
    }

    private function setDetectionCompletedAndTriggerFetch($missingStatementConfig)
    {
        if ($missingStatementConfig !== null)
        {
            $missingStatementConfig['completed'] = true;

            $this->updateMissingStatementConfigFor($this->basDetails->getAccountNumber(), $missingStatementConfig);

            $this->trace->info(
                TraceCode::MISSING_STATEMENT_DETECTION_UPDATE_CONFIG,
                [
                    'merchant_id' => $this->basDetails->getMerchantId(),
                    'channel'     => $this->channel,
                    'config'      => $missingStatementConfig,
                    'step'        => 'set_to_completed'
                ]);

            if ((array_key_exists('mismatch_data', $missingStatementConfig)) and
                (empty($missingStatementConfig['mismatch_data']) === false))
            {
                $this->trace->info(
                    TraceCode::MISSING_STATEMENT_DETECTION_TRIGGER_FETCH,
                    [
                        'merchant_id' => $this->basDetails->getMerchantId(),
                        'channel'     => $this->channel,
                        'config'      => $missingStatementConfig
                    ]);

                // trigger fetch action
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
