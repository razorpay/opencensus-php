<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\SlackNotification;
use RZP\Mail\Settlement\Reconciliation as ReconciliationEmail;

abstract class Processor extends Base\Core
{
    const MUTEX_RESOURCE = 'SETTLEMENT_RECONCILIATION_%s';

    const MUTEX_LOCK_TIMEOUT = 300;

    protected $mutex;
    /**
     * Array of reconciled data - one row corresponding to every row of the reconciliation file
     */
    protected $allReconciledRows = [];

    /**
     * Array of rows, ids for which entity couldn't be found in database
     */
    protected $unprocessedRows = [];

    /**
     * Child class needs to assign value to this.
     */
    protected $date;

    abstract protected function getRowProcessorNamespace($row);

    abstract protected function processReconciliation(array $input);

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function process($input)
    {
        $mutexResource = sprintf(self::MUTEX_RESOURCE, static::$channel);

        $data = $this->mutex->acquireAndRelease(
                                $mutexResource,
                                function () use ($input)
                                {
                                    return $this->processReconciliation($input);
                                },
                                self::MUTEX_LOCK_TIMEOUT,
                                ErrorCode::BAD_REQUEST_SETTLEMENT_RECONCILIATION_IN_PROGRESS,
                                50,
                                2000,
                                4000);

        return $data;
    }

    protected function getSummary(): array
    {
        $processedCount = count($this->allReconciledRows);

        $unprocessedCount = count($this->unprocessedRows);

        $totalCount = $processedCount + $unprocessedCount;

        $summary = [
            'channel'               => static::$channel,
            'total_count'           => $totalCount,
            'unprocessed_count'     => $unprocessedCount,
        ];

        return $summary;
    }

    protected function startReconciliation($data): array
    {
        $summary = $this->repo->transaction(function() use ($data)
        {
            try
            {
                foreach ($data as $row)
                {
                    $entity = $this->reconcileEntity($row);

                    if ($entity === null)
                    {
                        // Define the column that has FTA in each, and access that
                        $this->unprocessedRows[] = $row;
                    }
                    else
                    {
                        $this->allReconciledRows[] = $entity;
                    }
                }
            }
            catch (\Throwable $e)
            {
                (new SlackNotification)->failure('reconcile_file', $e);

                throw $e;
            }

            $summary = $this->getSummary();

            return $summary;
        });

        (new SlackNotification)->success('reconcile_file', $summary);

        return $summary;
    }

    protected function reconcileEntity($row)
    {
        $rowProcessorNamespace = $this->getRowProcessorNamespace($row);

        $fta = (new $rowProcessorNamespace($row))->process();

        return $fta;
    }

    protected function sendEmail(string $message = null)
    {
        if (($this->mode === Mode::TEST) and
            ($this->app->environment('dev', 'testing') === false))
        {
            return;
        }

        $msg = $message ?? ('UTR reconciled.' . PHP_EOL);

        $this->date = Carbon::today(Timezone::IST)->format('d-m-Y');

        $data['date'] = $this->date;
        $data['body'] = $msg;
        $data['channel'] = static::$channel;

        $email = new ReconciliationEmail($data);

        Mail::queue($email);
    }
}
