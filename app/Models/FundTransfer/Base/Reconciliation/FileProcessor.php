<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Mail\Settlement\Reconciliation as ReconciliationEmail;

abstract class FileProcessor extends Base\Core
{
    use Kotak\FileHandlerTrait;

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

    abstract protected function storeFile($reconcileFile);

    abstract protected function getRowProcessorNamespace($row);

    abstract protected function setDate($data);

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

    /**
     * Checks the reverse file extension is same as specified by the bank
     *
     * @param string $filePath
     *
     * @return string
     *
     * @throws LogicException
     */
    protected function getFileExtensionForParsing(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (in_array($extension, static::$fileExtensions, true) === true)
        {
            return $extension;
        }

        throw new LogicException(
            "Extension not handled: {$extension}"
            , null
            , [
                'file_path' => $filePath
            ]);
    }

    protected function parseFile(string $filePath)
    {
        $ext = $this->getFileExtensionForParsing($filePath);

        switch ($ext)
        {
            case FileStore\Format::XLSX:
            case FileStore\Format::XLS:
                return $this->parseExcelSheets($filePath);

            case FileStore\Format::CSV:
                return $this->parseTextFile($filePath, ',');

            default:
                return $this->parseTextFile($filePath, static::$delimiter);
        }
    }

    protected function processReconciliation($input)
    {
        $reconcileFile = $this->getReconcilationFile($input);

        $this->trace->info(
            TraceCode::MISC_TRACE_CODE,
            [
                'recon_filename' => $reconcileFile,
                'input'          => $input
            ]);

        if ($reconcileFile === null)
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE,
                ['message' => 'No file present']);

            return [];
        }

        $data = $this->parseFile($reconcileFile);

        $this->storeReconciledFile($reconcileFile);

        $response = null;

        if (empty($data) === true)
        {
            $response = ['message' => 'no records to reconcile'];
        }
        else
        {
            $this->setDate($data);

            $response = $this->startReconciliation($data);
        }

        $this->sendEmail();

        return $response;
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

    protected function getReconcilationFile($input)
    {
        $reconcileFile = null;

        if ((isset($input['source']) === true) and
            ($input['source'] === 'lambda'))
        {
            $key = urldecode($input['key']);

            $reconcileFile = $this->getH2HFileFromAws($key);
        }
        else
        {
            $reconcileFile = $this->getFile($input);
        }

        return $reconcileFile;
    }

    protected function reconcileEntity($row)
    {
        $rowProcessorNamespace = $this->getRowProcessorNamespace($row);

        $fta = (new $rowProcessorNamespace($row))->process();

        return $fta;
    }

    protected function sendEmail()
    {
        if (($this->mode === Mode::TEST) and
            ($this->app->environment('dev', 'testing') === false))
        {
            return;
        }

        $msg = 'UTR File reconciled.' . PHP_EOL;

        #TODO:: What date to put here?
        $this->date = Carbon::today(Timezone::IST)->format('d-m-Y');

        $data['date'] = $this->date;
        $data['body'] = $msg;

        $email = new ReconciliationEmail($data, static::$channel);

        Mail::queue($email);
    }
}
