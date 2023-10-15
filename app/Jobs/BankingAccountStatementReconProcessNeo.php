<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Services\RazorXClient;
use RZP\Models\BankingAccountStatement;

class BankingAccountStatementReconProcessNeo extends Job
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'banking_account_statement_recon_process_neo';

    /**
     * @var array
     */
    protected $params;

    /**
     * Default timeout value for a job is 60s. Changing it to 300s
     * as account statement update takes 1-2 mins to complete.
     * @var integer
     */
    public $timeout = 1800;

    /**
     * @param string $mode
     * @param array  $params
     *      1. channel (bank channel)
     *      2. Account number
     *      3. created_at
     *      4. updated_at
     *      5. Bas Ids to Net Amount Map
     *      6. Latest Corrected Bas Id
     *      7. Batch Number
     */
    public function __construct(string $mode, array $params)
    {
        $this->params = $params;

        parent::__construct($mode);
    }

    public function handle()
    {
        parent::handle();

        $BasService =  new BankingAccountStatement\Service;

        $this->trace->info(TraceCode::BAS_ENTITIES_BALANCE_UPDATE_AND_INSERT_REQUEST,
            [
                'params' => $this->params,
            ]);

        try
        {
            $BasService->insertMissingStatementsAndProcessNeo($this->params);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::BAS_ENTITIES_BALANCE_UPDATE_AND_INSERT_FAILED,
                [
                    'params' => $this->params,
                ]);
        }

        $this->delete();
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

        $context = [
            'params' => $this->params,
        ];

        $this->handleWorkerTimeoutGracefully($context);

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }
}
