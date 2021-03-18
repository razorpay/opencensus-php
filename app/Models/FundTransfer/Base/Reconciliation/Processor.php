<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;
use Carbon\Carbon;

use Razorpay\Trace\Logger;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt\Type;
use RZP\Models\FundTransfer\Attempt\Entity;
use RZP\Models\Settlement\SlackNotification;
use RZP\Jobs\AttemptsRecon as AttemptsReconJob;
use RZP\Mail\Settlement\Reconciliation as ReconciliationEmail;

abstract class Processor extends Base\Core
{
    const MUTEX_RESOURCE = 'SETTLEMENT_RECONCILIATION_%s_%s';

    const VERIFY_MUTEX_RESOURCE = 'SETTLEMENT_VERIFICATION_%s';

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

    abstract protected function verifySettlements(array $input);

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * This function is called for reconciling (only updating)
     * file based as well as for api based.
     *
     * Also, this function is called only by the cron (route) and
     * is not called via initiate transfer route internally.
     *
     * @param $input
     *
     * @return mixed
     */
    public function process($input)
    {
        $mutexResource = sprintf(self::MUTEX_RESOURCE, static::$channel, $this->mode);

        try
        {
            return $this->mutex->acquireAndRelease(
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
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::FUND_TRANSFER_RECONCILIATION_FILE_SKIPPED,
                [
                    'input' => $input,
                    'error' => $e->getMessage(),
                ]);

            throw $e;
        }
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

    public function startReconciliation($data, $reconcileFile = null): array
    {
        $summary = $this->repo->transaction(function() use ($data, $reconcileFile)
        {
            try
            {
                foreach ($data as $row)
                {
                    // $row will be an array of data (row taken from excel)
                    // in case of file based channel and it will be FTA
                    // entity in case of API based channel

                    $entity = $this->reconcileEntity($row, $reconcileFile);

                    if ($entity === null)
                    {
                        // Define the column that has FTA in each, and access that
                        $this->unprocessedRows[] = $row;
                    }
                    else
                    {
                        $this->allReconciledRows[] = $entity;

                        // When status is run via cron, we dispatch the
                        // recon job directly for both file and API.
                        $this->dispatchFtaForReconProcess($entity);
                    }
                }
            }
            catch (\Throwable $e)
            {
                (new SlackNotification)->send('reconcile_file', [], $e);

                throw $e;
            }

            $summary = $this->getSummary();

            $this->trace->info(TraceCode::FTA_RECON_SUMMARY, ['summary' => $summary]);

            return $summary;
        });

        $apiBasedChannels = Channel::getApiBasedChannels();

        //reducing slack alerts for API based channels
        if (in_array(static::$channel, $apiBasedChannels, true) === false)
        {
            (new SlackNotification)->send('reconcile_file', $summary, null, $summary['unprocessed_count']);

        }

        return $summary;
    }

    protected function reconcileEntity($row, $reconcileFile = null)
    {
        $this->trace->info(TraceCode::VERIFY_FTA_ROW, ['row' => $row]);

        $rowProcessorNamespace = $this->getRowProcessorNamespace($row);

        $fta = (new $rowProcessorNamespace($row, $reconcileFile))->process();

        return $fta;
    }

    protected function dispatchFtaForReconProcess(Entity $attempt)
    {
        try
        {
            //
            // Dispatching in 5 sec as all the operation are happening in queue
            // and all the queues are trying to acquire log in fta id
            // to avoid the mutex lock issue we are dispatching the job with some delay
            // so that current lock will be release before next job starts
            //
            AttemptsReconJob::dispatch($this->mode, $attempt->getId())->delay(5);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FTA_RECONCILE_DISPATCH_FAILED,
                [
                    'mode'   => $this->mode,
                    'fta_id' => $attempt->getId(),
                ]);
        }
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

    /**
     * @param $input
     * @return mixed
     */
    public function verify($input)
    {
        $mutexResource = sprintf(self::VERIFY_MUTEX_RESOURCE, static::$channel);

        $summary = $this->mutex->acquireAndRelease(
            $mutexResource,
            function () use ($input)
            {
                return $this->verifySettlements($input);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SETTLEMENT_VERIFICATION_IN_PROGRESS,
            50,
            2000,
            4000);

        return $summary;
    }

    protected function startVerification($data): array
    {
        $summary = $this->repo->transaction(function() use ($data)
        {
            try
            {
                foreach ($data as $row)
                {
                    $this->trace->info(TraceCode::VERIFY_FTA_ROW, ['row' => $row]);

                    $rowProcessorNamespace = $this->getRowProcessorNamespace($row);

                    $entity = (new $rowProcessorNamespace($row))->verifyRow();

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
                (new SlackNotification)->send('setl_verify', [], $e);

                throw $e;
            }

            $summary = $this->getSummary();

            return $summary;
        });

        (new SlackNotification)->send('setl_verify', $summary);

        return $summary;
    }

    public function notifyH2HErrors(array $input)
    {
        return $input;
    }
}
