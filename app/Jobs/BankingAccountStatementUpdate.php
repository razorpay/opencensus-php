<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\BankingAccountStatement;

class BankingAccountStatementUpdate extends Job
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'banking_account_statement_update';

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

        $BASCore = new BankingAccountStatement\Core;

        $this->trace->info(TraceCode::BAS_ENTITIES_BALANCE_UPDATE_REQUEST,
            [
                'params' => $this->params,
            ]);

        try
        {
            $BASCore->correctBalanceForStatementsEffectedByMissingStatements($this->params);

            $this->delete();
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::BAS_ENTITIES_BALANCE_UPDATE_FAILED,
                [
                    'params' => $this->params,
                ]);

            $this->delete();
        }
    }
}
