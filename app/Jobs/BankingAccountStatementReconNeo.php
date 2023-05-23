<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankingAccountStatement as BAS;

class BankingAccountStatementReconNeo extends Job
{
    const MAX_RETRY_ATTEMPT = 2;

    const MAX_RETRY_DELAY = 120;

    /**
     * @var string
     */
    protected $queueConfigKey = 'banking_account_statement_recon_neo';

    /**
     * @var array
     */
    protected $params;

    /**
     * Default timeout value for a job is 60s. Changing it to 20 mins
     * as fetching account statements for date ranges takes 10-12 mins to complete.
     * @var integer
     */
    public $timeout = 1200;

    public function __construct(string $mode, array $params)
    {
        $this->params = $params;

        parent::__construct($mode);
    }

    public function getParams()
    {
        return $this->params;
    }

    public function handle()
    {
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

                $workerStartTime = microtime(true);

                [$fetchMore, $paginationKey] = (new BAS\Core)->fetchAccountStatementWithRange($this->params, true);

                $workerEndTime = microtime(true);

                $this->trace->info(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCHED,
                                   [
                                       BAS\Entity::CHANNEL            => $this->params['channel'],
                                       BAS\Entity::ACCOUNT_NUMBER     => $this->params['account_number'],
                                       BAS\Entity::MERCHANT_ID        => $BASCore->getBasDetails()->getMerchantId(),
                                       BAS\Details\Entity::BALANCE_ID => $BASCore->getBasDetails()->getBalanceId(),
                                       'response_time'                => $workerEndTime - $workerStartTime,
                                       'fetch_more'                   => $fetchMore,
                                   ]);

                $this->params['expected_attempts'] = $this->params['expected_attempts'] - 1;

                $this->params['pagination_key'] = $paginationKey;

                $this->trace->info(
                    TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_DISPATCH_JOB_REQUEST,
                    $this->params);

                if (($this->params['expected_attempts'] > 0) and
                    ($fetchMore === true) and
                    (empty($paginationKey) === false))
                {
                    BankingAccountStatementReconNeo::dispatch($this->mode, $this->params);
                }
                else
                {
                    $this->trace->info(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_INSERT_REQUEST_DISPATCHED,
                        $this->params);

                    $missingStatements = $BASCore->getMissingRecordsFromRedisForAccount(
                        $this->params['account_number'],
                        $this->params['channel'],
                        $BASCore->getBasDetails()->getMerchantId()
                    );

                    if (empty($missingStatements) === false)
                    {
                        BankingAccountStatementReconProcessNeo::dispatch($this->mode, [
                            BAS\Entity::CHANNEL        => $this->params['channel'],
                            BAS\Entity::ACCOUNT_NUMBER => $this->params['account_number']
                        ])->delay(120);
                    }
                    else
                    {
                        $basDetails->reload();

                        $lastReconciledAt = Carbon::createFromTimestamp($this->params[BAS\Entity::TO_DATE], Timezone::IST)->startOfDay()->getTimestamp();

                        $presentLastReconciledAt = $basDetails->getLastReconciledAt();

                        if ((isset($presentLastReconciledAt) === false) or
                            ($presentLastReconciledAt < $lastReconciledAt))
                        {
                            $basDetails->setLastReconciledAt($lastReconciledAt);

                            $basDetails->saveOrFail();
                        }
                    }
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

            if ($e instanceof Exception\GatewayErrorException)
            {
                $traceData = $this->params;

                $traceData['message'] = $operation = 'Deleting the job after configured number of tries for gateway exception';

                $this->trace->error(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_JOB_DELETED, $traceData);

                $this->trace->count(BAS\Metric::MISSING_STATEMENT_FETCH_ERROR_GATEWAY_EXCEPTION, [
                    BAS\Metric::LABEL_CHANNEL => $this->params['channel'],
                    'is_monitoring'           => true,
                ]);

                (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_rbl_recon_alerts');

                $this->delete();
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

            $data                           = $this->params;
            $data[BAS\Core::DELAY]          = $workerRetryDelay;
            $data[BAS\Core::ATTEMPT_NUMBER] = $this->attempts() + 1;

            $this->trace->info(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_JOB_RELEASED, $data);

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
                'is_monitoring'           => true,
            ]);

            $operation = $this->params['channel'].' banking account statement fetch job failed';

            (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_rbl_recon_alerts');
        }
    }
}
