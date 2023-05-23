<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\BankingAccountStatement as BAS;

class BankingAccountStatementSourceLinking extends Job
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'banking_account_statement_source_linking';

    /**
     * @var array
     */
    public $params;

    /**
     * Default timeout value for a job is 60s.
     * @var integer
     */
    public $timeout = 60;

    /**
     * @param string $mode
     * @param array  $params
     *      1. payout_id
     */
    public function __construct(string $mode, array $params)
    {
        $this->params = $params;

        parent::__construct($mode);
    }

    public function handle()
    {
        $this->trace->info(
            TraceCode::BAS_SOURCE_LINKING_RETRY_INITIATE,
            [
                'params' => $this->params,
            ]);

        try
        {
            (new BAS\Core)->retryBasSourceLinkingForProcessedPayout($this->params);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::BAS_SOURCE_LINKING_RETRY_FAILURE
            );
        }

        $this->delete();
    }
}
