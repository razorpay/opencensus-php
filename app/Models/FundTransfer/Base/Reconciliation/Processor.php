<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

abstract class Processor extends Base\Core
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

            ErrorCode::BAD_REQUEST_SETTLEMENT_RECONCILIATION_IN_PROGRESS);

        return $data;
    }

    final protected function parseFile($filePath)
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($ext)
        {
            case FileStore\Format::XLSX:
            case FileStore\Format::XLS:
                return $this->parseExcelSheets($filePath);

            case FileStore\Format::TXT:
                return $this->parseTextFile($filePath, static::$delimiter);

            case FileStore\Format::CSV:
                return $this->parseTextFile($filePath, ',');

            default:
                throw new LogicException("Extension not handled: {$ext}");
        }
    }

    final protected function processReconciliation($input)
    {
        $reconcileFile = $this->getReconcilationFile($input);

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

        return $response;
    }

    final protected function startReconciliation($data): array
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

    final protected function getSummary(): array
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

    final protected function getReconcilationFile($input)
    {
        $reconcileFile = null;

        if ((isset($input['source']) === true) and
            ($input['source'] === 'lambda'))
        {
            $key = $input['key'];

            $reconcileFile = $this->getH2HFileFromAws($key);
        }
        else
        {
            $reconcileFile = $this->getFile($input);
        }

        return $reconcileFile;
    }

    /**
     * @param $row
     *
     * @return FundTransferAttempt\Entity $fta
     */
    final protected function reconcileEntity($row): FundTransferAttempt\Entity
    {
        $rowProcessorNamespace = $this->getRowProcessorNamespace($row);

        $fta = (new $rowProcessorNamespace($row))->process();

        return $fta;
    }
}
