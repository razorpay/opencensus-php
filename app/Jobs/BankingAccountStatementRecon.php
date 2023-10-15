<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Services\RazorXClient;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankingAccountStatement as BAS;

class BankingAccountStatementRecon extends Job
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
     * @var bool
     */
    protected $isMonitoring;

    /**
     * @var bool
     */
    protected $retried = false;

    /**
     * Default timeout value for a job is 60s. Changing it to 20 mins
     * as fetching account statements for date ranges takes 10-12 mins to complete.
     * @var integer
     */
    public $timeout = 1200;

    public function __construct(string $mode, array $params, bool $isMonitoring)
    {
        $this->params = $params;

        $this->isMonitoring = $isMonitoring;

        parent::__construct($mode);
    }

    public function handle()
    {
        $workerStartTime = microtime(true);

        try
        {
            parent::handle();

            $BASCore = new BAS\Core;

            $basDetails = $BASCore->getBasDetails($this->params['account_number'], $this->params['channel']);

            if (isset($basDetails) === false)
            {
                $this->trace->info(TraceCode::BAS_DETAILS_NOT_FOUND);

                $this->delete();
            }
            else
            {
                $this->trace->info(
                    TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_JOB_INIT,
                    [
                        BAS\Entity::CHANNEL            => $this->params['channel'],
                        BAS\Entity::ACCOUNT_NUMBER     => $this->params['account_number'],
                        BAS\Entity::MERCHANT_ID        => $BASCore->getBasDetails()->getMerchantId(),
                        BAS\Details\Entity::BALANCE_ID => $BASCore->getBasDetails()->getBalanceId(),
                        BAS\Entity::FROM_DATE          => $this->params[BAS\Entity::FROM_DATE],
                        BAS\Entity::TO_DATE            => $this->params[BAS\Entity::TO_DATE],
                        'expected_attempts'            => $this->params['expected_attempts'],
                        'pagination_key'               => $this->params['pagination_key'],
                        BAS\Entity::SAVE_IN_REDIS      => $this->params[BAS\Entity::SAVE_IN_REDIS],
                    ]);

                [$fetchMore, $paginationKey] = (new BAS\Core)->fetchAccountStatementWithRange($this->params, $this->isMonitoring);

                $workerProcessingEndTime = microtime(true);

                $workerProcessingCompletionTotalTime =  $workerProcessingEndTime - $workerStartTime;

                $this->trace->info(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCHED,
                                   [
                                       BAS\Entity::CHANNEL            => $this->params['channel'],
                                       BAS\Entity::ACCOUNT_NUMBER     => $this->params['account_number'],
                                       BAS\Entity::MERCHANT_ID        => $BASCore->getBasDetails()->getMerchantId(),
                                       BAS\Details\Entity::BALANCE_ID => $BASCore->getBasDetails()->getBalanceId(),
                                       'response_time'                => $workerProcessingCompletionTotalTime,
                                       'fetch_more'                   => $fetchMore,
                                   ]);

                $dimensions = [
                    'worker_class' => $this->getJobName(),
                    'balance_id'   => $BASCore->getBasDetails()->getBalanceId(),
                    'merchant_id'  => $BASCore->getBasDetails()->getMerchantId(),
                    'channel'      => $this->params['channel'],
                ];

                $this->trace->histogram(
                    BAS\Metric::BAS_FETCH_PROCESS_DURATION_SECONDS, $workerProcessingCompletionTotalTime, $dimensions);

                $this->params['expected_attempts'] = $this->params['expected_attempts'] - 1;

                $this->params['pagination_key'] = $paginationKey;

                $this->trace->info(
                    TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_DISPATCH_JOB_REQUEST,
                    $this->params);

                if (($this->params['expected_attempts'] > 0) and
                    ($fetchMore === true) and
                    (empty($paginationKey) === false))
                {
                    BankingAccountStatementRecon::dispatch($this->mode, $this->params, $this->isMonitoring);
                }

                $this->delete();
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_JOB_FAILED, $this->params);

            $this->trace->count(BAS\Metric::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_JOB_FAILED,
                                [
                                    'channel' => $this->params['channel']
                                ]);

            if ($e instanceof Exception\GatewayErrorException)
            {
                $traceData = $this->params;

                $traceData['message'] = 'Deleting the job after configured number of tries for gateway exception';

                $this->trace->error(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_JOB_DELETED, $traceData);

                $this->trace->count(BAS\Metric::MISSING_STATEMENT_FETCH_ERROR_GATEWAY_EXCEPTION, [
                    BAS\Metric::LABEL_CHANNEL => $this->params['channel'],
                    'is_monitoring'           => $this->isMonitoring,
                ]);

                $this->delete();
            }
            else
            {
                $this->checkRetry();
            }
        }

        $workerCompletionEndTime = microtime(true);

        $dimensions = [
            'worker_class' => $this->getJobName(),
            'balance_id'   => $BASCore->getBasDetails()->getBalanceId(),
            'merchant_id'  => $BASCore->getBasDetails()->getMerchantId(),
            'channel'      => $this->params['channel'],
            'is_retry'     => $this->retried,
        ];

        $workerCompletionTotalTime =  $workerCompletionEndTime - $workerStartTime;

        $this->trace->histogram(
            BAS\Metric::BAS_FETCH_COMPLETED_DURATION_SECONDS, $workerCompletionTotalTime, $dimensions);
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $workerRetryDelay = self::MAX_RETRY_DELAY * pow(2, $this->attempts());

            $data                           = $this->params;
            $data[BAS\Core::DELAY]          = $workerRetryDelay;
            $data[BAS\Core::ATTEMPT_NUMBER] = $this->attempts() + 1;

            $this->trace->info(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_JOB_RELEASED, $data);

            $this->retried = true;

            $this->release($workerRetryDelay);
        }
        else
        {
            $this->delete();

            $traceData                 = $this->params;
            $traceData['job_attempts'] = $this->attempts();
            $traceData['message']      = 'Deleting the job after configured number of tries. Still unsuccessful.';

            $this->trace->error(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_JOB_DELETED, $traceData);

            $this->trace->count(BAS\Metric::MISSING_STATEMENT_FETCH_ERROR_RETRIES_EXHAUSTED, [
                BAS\Metric::LABEL_CHANNEL => $this->params['channel'],
                'is_monitoring'           => $this->isMonitoring,
            ]);
        }
    }

    protected function handleWorkerTimeoutGracefully($context = [], $maxRetries = 1, $retryDelay = 0)
    {
        $internalJob = $this->job;

        $jobIsDeletedOrReleased = false;

        /**
         * Generally all Internal Jobs extends Illuminate\Contracts\Queue\Job interface, which
         * means isDeletedOrReleased() method will always exist. Adding this as an additional safety check.
         */
        if ((is_null($internalJob) === false) and
            (method_exists($internalJob, 'isDeletedOrReleased')))
        {
            $jobIsDeletedOrReleased = $internalJob->isDeletedOrReleased();
        }

        if ($jobIsDeletedOrReleased === false)
        {
            $this->checkRetry();
        }
    }

    /**
     * Defines how the job is handled in an event of worker timeout
     */
    protected function beforeJobKillCleanUp($variant = RazorXClient::DEFAULT_CASE)
    {
        $this->trace->count(Metric::RAZORPAYX_PAYOUTS_BANKING_QUEUES_TIMEOUT_COUNT, [
            'job_name'   => $this->getJobName() ?? '',
            'mode'       => $this->getMode() ?? '',
        ]);

        parent::beforeJobKillCleanUp($variant);

        $this->handleWorkerTimeoutGracefully();

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }
}
