<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\BankingAccountStatement;
use RZP\Models\BankingAccountStatement as BAS;

class BankingAccountMissingStatementInsert extends Job
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'missing_account_statement_insert';

    /**
     * @var array
     */
    protected $updateParams;

    /**
     * @var array
     */
    protected $input;

    protected $app;

    /**
     * Default timeout value for a job is 60s. Changing it to 300s
     * as account statement update takes 1-2 mins to complete.
     * @var integer
     */
    public $timeout = 1800;

    /**
     * @param string $mode
     * @param array  $updateParams
     */
    public function __construct(string $mode, array $input, array $updateParams = [])
    {
        $this->updateParams = $updateParams;

        $this->input = $input;

        parent::__construct($mode);
    }

    public function handle()
    {
        $workerStartTime = microtime(true);

        parent::handle();

        $BasService =  new BankingAccountStatement\Service;

        $this->trace->info(TraceCode::BAS_MISSING_RECORDS_INSERTION_REQUEST,
            [
                'update_params' => $this->updateParams,
                'input'         => $this->input
            ]);

        try
        {
            $BasService->insertMissingStatementsNeo($this->input, $this->updateParams);

            $workerCompletionEndTime = microtime(true);

            $workerCompletionTotalTime =  $workerCompletionEndTime - $workerStartTime;

            $this->trace->info(TraceCode::BAS_BATCH_INSERT_COMPLETED_SUCCESSFULLY, [
                'params'        => $this->updateParams,
                'response_time' => $workerCompletionTotalTime,
            ]);

            $dimensions = [
                'worker_class' => $this->getJobName(),
                'balance_id'   => $this->updateParams['balance_id'],
                'merchant_id'  => $this->updateParams['merchant_id'],
                'channel'      => $this->updateParams['channel'],
            ];

            $this->trace->histogram(
                BAS\Metric::BAS_INSERT_COMPLETED_DURATION_SECONDS, $workerCompletionTotalTime, $dimensions);

            $this->delete();
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::BAS_MISSING_RECORDS_INSERTION_REQUEST_FAILED,
                [
                    'update_params' => $this->updateParams,
                ]);

            $this->trace->count(BAS\Metric::MISSING_STATEMENT_BATCH_INSERT_FAILURE, [
                BAS\Metric::LABEL_CHANNEL => $this->updateParams[BAS\Entity::CHANNEL],
            ]);

            $this->delete();
        }
    }
}
