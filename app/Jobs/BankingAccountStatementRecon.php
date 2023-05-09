<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;

use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Metric;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccountStatement as BAS;
use RZP\Models\BankingAccountStatement\Details as BASD;

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

                $traceData['message'] = $operation = 'Deleting the job after configured number of tries for gateway exception';

                $this->trace->error(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_JOB_DELETED, $traceData);

                $this->trace->count(BAS\Metric::MISSING_STATEMENT_FETCH_ERROR_GATEWAY_EXCEPTION, [
                    BAS\Metric::LABEL_CHANNEL => $this->params['channel'],
                    'is_monitoring'           => $this->isMonitoring,
                ]);

                (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_rbl_recon_alerts');

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

            $operation = $this->params['channel'].' banking account statement fetch job failed';

            (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_rbl_recon_alerts');
        }
    }
}
