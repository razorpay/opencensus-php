<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccountStatement as BAS;
use RZP\Models\BankingAccountStatement\Constants;
use RZP\Models\BankingAccountStatement\Details as BASD;

class BankingAccountStatementCleanUp extends Job
{
    const MAX_RETRY_ATTEMPT = 2;

    const MAX_RETRY_DELAY = 120;

    /**
     * @var string
     */
    protected $queueConfigKey = 'banking_account_statement_recon';

    /**
     * @var array
     */
    protected $params;

    /**
     * @var array
     */
    protected $cleanUpConfig;

    /**
     * @var array
     */
    protected $fetchInput;

    /**
     * Default timeout value for a job is 60s. Changing it to 120s since we have kept
     * 80s for mozart account statement fetch V2 API.
     * @var integer
     */
    public $timeout = 120;

    /**
     * Params include:
     * $input = [
     *  'clean_up_config'       => 'cleanup config sent while dispatch',
     *  'total_mismatch_amount' => 'to_be_sent_in_the_input',
     *  'mismatch_amount_found' => 0 (will be updated as we go fetching missing statements),
     *  'fetch_in_progress'     => false (initially it will be false),
     *  'channel'               => 'channel',
     *  'account_number'        => 'account_number',
     *  'merchant_id'           => 'merchant_id,
     *  'fetch_input'           =>  [
     *      BAS\Entity::FROM_DATE            => BAS\Entity::FROM_DATE,
     *      BAS\Entity::TO_DATE              => BAS\Entity::TO_DATE,
     *      BAS\Constants::EXPECTED_ATTEMPTS => 'to_be_calculated',
     *      BAS\Constants::PAGINATION_KEY    => 'depends_on_previous_fetch',
     *      BAS\Entity::SAVE_IN_REDIS        => BAS\Entity::SAVE_IN_REDIS,
     *   ], // Initially to be sent as null
     * ];
     */
    public function __construct(string $mode, array $input)
    {
        $this->cleanUpConfig = (isset($input[Constants::CLEAN_UP_CONFIG]) === true) ?
            $input[Constants::CLEAN_UP_CONFIG] : null;

        $this->fetchInput = (isset($input[Constants::FETCH_INPUT]) === true) ?
            $input[Constants::FETCH_INPUT] : null;

        unset($input[Constants::CLEAN_UP_CONFIG]);
        unset($input[Constants::FETCH_INPUT]);

        $this->params = $input;

        parent::__construct($mode);
    }

    public function getJobInput()
    {
        return [
            'params'                   => $this->params,
            Constants::FETCH_INPUT     => $this->fetchInput,
            Constants::CLEAN_UP_CONFIG => $this->cleanUpConfig
        ];
    }

    // Clean Up Config Pre-Requisite
    // Cleanup config should be cleansed of duplicates and should contain months for which recon needs to run.
    //
    // Segregation of Flows
    // - If fetch_in_progress is false
    //   Check if clean_up_config variable is empty or not,
    //   -- If clean_up_config up is not empty
    //      Check if new fetch needs to be done from clean_up_config variable.
    //      --- If Yes
    //          Release the message back into the queue,
    //          initiating new fetch and fetch_in_progress as true.
    //      --- Else
    //          ---- If total_mismatch_amount !== mismatch_amount_found
    //               Don't initiate insertion as it will block the merchant.
    //               Raise Alert and delete the msg.
    //          ---- Else
    //               We see that all fetches are completed and we then dispatch to insertion queue.
    //   -- Else
    //      Alert and Delete the Job.
    //  - Else
    //     Initiate next fetch with pagination key like earlier. Once fetch is completed release the message with
    //     fetch_in_progress as false and update clean_up_config.

    public function handle()
    {
        try
        {
            parent::handle();

            $BASCore = new BAS\Core;

            $basDetails = $BASCore->getBasDetails($this->params[BAS\Entity::ACCOUNT_NUMBER], $this->params[BAS\Entity::CHANNEL], [
                BASD\Status::UNDER_MAINTENANCE,
                BASD\Status::ACTIVE
            ]);

            $this->fetchCleanUpConfig($this->params[BAS\Entity::ACCOUNT_NUMBER]);

            // Validations
            if ((isset($basDetails) === false) or
                (array_key_exists(Constants::COMPLETED, $this->cleanUpConfig) === false) or
                ($this->cleanUpConfig[Constants::COMPLETED] === false))
            {
                $this->trace->info(TraceCode::BAS_CLEANUP_JOB_VALIDATION_FAILED, [
                    'bas_details'     => $basDetails,
                    'clean_up_config' => $this->cleanUpConfig,
                ]);

                $this->delete();

                return;
            }

            $this->trace->info(TraceCode::BAS_CLEANUP_JOB_INIT, [
                'params'          => $this->params,
                'clean_up_config' => $this->cleanUpConfig,
                'fetch_input'     => $this->fetchInput,
            ]);

            if ($this->params[Constants::FETCH_IN_PROGRESS] === false)
            {
                // Check if new fetch needs to be done from clean_up_config variable.
                [$isFetchRequired, $fetchInput] = $BASCore->checkCleanUpConfigIfFetchIsRequired(
                    $this->cleanUpConfig, $this->params[BAS\Entity::CHANNEL], $this->params[BAS\Entity::ACCOUNT_NUMBER]);

                if ($isFetchRequired === true)
                {
                    $queueInput = [];

                    $queueInput                               = array_merge($queueInput, $this->params);
                    $queueInput[Constants::FETCH_INPUT]       = $fetchInput;
                    $queueInput[Constants::CLEAN_UP_CONFIG]   = $this->cleanUpConfig;
                    $queueInput[Constants::FETCH_IN_PROGRESS] = true;

                    $BASCore->dispatchIntoQueueAndRetryIfFailure(Constants::BANKING_ACCOUNT_STATEMENT_CLEAN_UP, $queueInput);
                }
                else
                {
                    if (abs($this->params[Constants::TOTAL_MISMATCH_AMOUNT]) !== abs($this->params[Constants::MISMATCH_AMOUNT_FOUND]))
                    {
                        $this->trace->error(TraceCode::BAS_CLEANUP_AMOUNT_MISMATCH_ERROR, [
                            'params'          => $this->params,
                        ]);
                    }
                    else
                    {
                        $this->trace->info(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_INSERT_REQUEST_DISPATCHED,[
                            'params'          => $this->params,
                            'clean_up_config' => $this->cleanUpConfig,
                        ]);

                        $missingStatements = $BASCore->getMissingRecordsFromRedisForAccount(
                            $this->params[BAS\Entity::ACCOUNT_NUMBER],
                            $this->params[BAS\Entity::CHANNEL],
                            $this->params[BAS\Entity::MERCHANT_ID]
                        );

                        if (empty($missingStatements) === false)
                        {
                            $BASCore->dispatchIntoQueueAndRetryIfFailure(Constants::BANKING_ACCOUNT_MISSING_STATEMENT_INSERT, [
                                BAS\Entity::CHANNEL        => $this->params[BAS\Entity::CHANNEL],
                                BAS\Entity::ACCOUNT_NUMBER => $this->params[BAS\Entity::ACCOUNT_NUMBER]
                            ], 120);
                        }
                    }
                }
            }
            else
            {
                $workerStartTime = Carbon::now()->getTimestamp();

                $fetchInputWithRange = [
                    BAS\Entity::CHANNEL        => $this->params[BAS\Entity::CHANNEL],
                    BAS\Entity::ACCOUNT_NUMBER => $this->params[BAS\Entity::ACCOUNT_NUMBER],
                ];

                $fetchInputWithRange = array_merge($fetchInputWithRange, $this->fetchInput);

                [$fetchMore, $paginationKey, $bankTransactions, $mismatchAmountFound] = (new BAS\Core)->fetchAccountStatementWithRange(
                    $fetchInputWithRange, true);

                $workerEndTime = Carbon::now()->getTimestamp();

                $this->trace->info(TraceCode::BAS_CLEANUP_MISSING_STATEMENTS_FETCHED, [
                    'params'        => $this->params,
                    'response_time' => $workerEndTime - $workerStartTime,
                    'fetch_more'    => $fetchMore,
                ]);

                $this->fetchInput[Constants::EXPECTED_ATTEMPTS] = $this->fetchInput[Constants::EXPECTED_ATTEMPTS] - 1;

                $this->fetchInput[Constants::PAGINATION_KEY] = $paginationKey;

                $this->params[Constants::MISMATCH_AMOUNT_FOUND] += $mismatchAmountFound;

                $queueInput = [];

                if (($this->fetchInput[Constants::EXPECTED_ATTEMPTS] > 0) and
                    ($fetchMore === true) and
                    (empty($paginationKey) === false))
                {
                    $queueInput                               = array_merge($queueInput, $this->params);
                    $queueInput[Constants::FETCH_INPUT]       = $this->fetchInput;
                    $queueInput[Constants::CLEAN_UP_CONFIG]   = $this->cleanUpConfig;
                    $queueInput[Constants::FETCH_IN_PROGRESS] = true;

                    $BASCore->dispatchIntoQueueAndRetryIfFailure(Constants::BANKING_ACCOUNT_STATEMENT_CLEAN_UP, $queueInput);
                }
                else
                {
                    // When Fetch for a given range completes
                    $updationSuccess = $BASCore->updateCleanUpConfigWhenFetchIsFinished($this->cleanUpConfig, $this->fetchInput);

                    if ($updationSuccess === false)
                    {
                        $this->delete();

                        $this->trace->error(TraceCode::BAS_CLEANUP_JOB_DELETED, [
                            'message' => 'Cleanup config updation failure'
                        ]);

                        return;
                    }

                    $this->trace->info(TraceCode::BAS_CLEANUP_CONFIG_UPDATE_SUCCESS, [
                        'clean_up_config' => $this->cleanUpConfig,
                        'fetch_input'     => $this->fetchInput,
                    ]);

                    $queueInput                               = array_merge($queueInput, $this->params);
                    $queueInput[Constants::FETCH_INPUT]       = null;
                    $queueInput[Constants::CLEAN_UP_CONFIG]   = $this->cleanUpConfig;
                    $queueInput[Constants::FETCH_IN_PROGRESS] = false;

                    $BASCore->dispatchIntoQueueAndRetryIfFailure(Constants::BANKING_ACCOUNT_STATEMENT_CLEAN_UP, $queueInput);
                }
            }

            $this->delete();

            $this->trace->error(TraceCode::BAS_CLEANUP_JOB_DELETED);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::BAS_CLEANUP_JOB_FAILED, [
                'params'          => $this->params,
                'clean_up_config' => $this->cleanUpConfig,
                'fetch_input'     => $this->fetchInput,
            ]);

            if ($e instanceof Exception\GatewayErrorException)
            {
                $this->trace->count(BAS\Metric::MISSING_STATEMENT_FETCH_ERROR_GATEWAY_EXCEPTION, [
                    BAS\Metric::LABEL_CHANNEL => $this->params[BAS\Entity::CHANNEL],
                    'is_monitoring'           => true,
                ]);

                $this->delete();

                $this->trace->error(TraceCode::BAS_CLEANUP_JOB_DELETED, [
                    'message' => 'Deleting the job after configured number of tries for gateway exception',
                ]);
            }
            else
            {
                $this->checkRetry();
            }
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $workerRetryDelay = self::MAX_RETRY_DELAY * pow(2, $this->attempts());

            $this->trace->info(TraceCode::BAS_CLEANUP_JOB_RELEASED, [
                'params'          => $this->params,
                'clean_up_config' => $this->cleanUpConfig,
                'fetch_input'     => $this->fetchInput,
                'delay'           => $workerRetryDelay,
                'attempt_number'  => $this->attempts() + 1,
            ]);

            $this->release($workerRetryDelay);
        }
        else
        {
            $this->delete();

            $message = 'Deleting the job after configured number of tries. Still unsuccessful.';

            $this->trace->error(TraceCode::BAS_CLEANUP_JOB_DELETED, [
                'attempts' => $this->attempts(),
                'message'  => $message,
            ]);

            $this->trace->count(BAS\Metric::MISSING_STATEMENT_FETCH_ERROR_RETRIES_EXHAUSTED, [
                BAS\Metric::LABEL_CHANNEL => $this->params[BAS\Entity::CHANNEL],
                'is_monitoring'           => true,
            ]);
        }
    }

    protected function fetchCleanUpConfig($accountNumber)
    {
        if ($this->cleanUpConfig === null)
        {
            $cleanUpConfigKey = Admin\ConfigKey::PREFIX . 'rx_ca_missing_statement_detection_' . $this->params['channel'];

            $cleanUpConfig = (new Admin\Service())->getConfigKey(['key' => $cleanUpConfigKey]);

            if ((empty($cleanUpConfig) === true) or
                (array_key_exists($accountNumber, $cleanUpConfig) === false))
            {
                return;
            }

            $this->cleanUpConfig = $cleanUpConfig[$accountNumber];
        }
    }
}
