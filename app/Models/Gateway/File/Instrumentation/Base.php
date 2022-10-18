<?php

namespace RZP\Models\Gateway\File\Instrumentation;

use App;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Core;
use RZP\Models\FileStore;
use RZP\Models\Gateway\File;
use RZP\Base\RuntimeManager;
use RZP\Services\KafkaProducer;
use RZP\Exception\LogicException;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder as PhpSpreadsheetDefaultValueBinder;


/**
 * Base processor class defines the steps which need to be performed for processing all the generanted files
 * of gateway file entity. It defines abstact methods for each step which needs to be implemented by child class
 */
abstract class Base extends Core
{
    /**
     * Mutex lock is acquired by default for 900s (15 minutes)
     */
    const MUTEX_LOCK_TIMEOUT = 900;

    protected $mutex;

    protected $gatewayFile;

    protected $fileId;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->increaseAllowedSystemLimits();
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
    }

    /**
     * Before starting the file generation, we acquire a mutex lock over the
     * gateway_file entity and check if it can be processed.This is to prevent
     * parallel requests from operating on the same gateway_file entity
     *
     * @param File\Entity $gatewayFile
     * @param string $fileId
     * @throws LogicException
     */
    public function instrumentationProcess(File\Entity $gatewayFile, string $fileId)
    {
        $this->gatewayFile = $gatewayFile;

        $this->fileId = $fileId;

        $this->mutex->acquireAndRelease(
            'file_generation_' . $fileId,
            function ()
            {
                $this->processFileGeneration();
            },
            static::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_GATEWAY_FILE_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    public function resetFileProcessorAttributes()
    {
        return $this;
    }

    abstract public function processInput($data, $entries);

    abstract public function parseTextRow($row);

    abstract public function filterFiles(& $files);


    /**
     * @param $gatewayFile
     * @throws LogicException
     */
    public function processFileGeneration()
    {
        $file = $this->gatewayFile
            ->files()
            ->whereIn(FileStore\Entity::ID, [$this->fileId])
            ->get()
            ->first();

        $this->trace->info(TraceCode::FILE_GENERATE_FILE_ENTITY,
            [
                'file entity' => $file
            ]);

        $filePath = (new FileStore\Accessor)
            ->id($file->getId())
            ->merchantId($file[FileStore\Entity::MERCHANT_ID])
            ->getFile();

        $this->trace->info(TraceCode::FILE_GENERATE_FILE_PATH,
            [
                'filePath' => $filePath
            ]);

        $parseData = $this->parseFile($filePath);

        $subType = $this->gatewayFile->getSubType();
        $type    = $this->gatewayFile->getType();

        $fileData = [
            Constants::FILE_NAME        => $file->getName(),
            Constants::FILE_SIZE        => $file->getSize(),
            Constants::FILE_ID          => $file->getId(),
            Constants::CREATED_AT       => $this->gatewayFile->getCreatedAt(),
            Constants::UPDATED_AT       => $this->gatewayFile->getUpdatedAt(),
            Constants::BEGIN            => $this->gatewayFile->getBegin(),
            Constants::END              => $this->gatewayFile->getEND(),
            Constants::TYPE             => $this->gatewayFile->getType(),
            Constants::TARGET           => $this->gatewayFile->getTarget(),
            Constants::BATCH_ID         => $this->gatewayFile->getId(),
            Constants::GATEWAY          => $this->gatewayFile->getTarget(),
            Constants::OFFSET           => $this->isOffsetExists($type) ? $subType : 0,
            Constants::PAYMENT_STATUS   => "CREATED"
        ];

        $this->processInput($fileData, $parseData);
    }

    protected function parseFile(string $filePath): array
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($ext)
        {
            case FileStore\Format::XLSX:
            case FileStore\Format::XLS:
                return $this->parseExcelSheets($filePath);

            case FileStore\Format::TXT:
                return $this->parseTextFile($filePath);

            case FileStore\Format::CSV:
                return $this->parseCsvFile($filePath);

            default:
                throw new LogicException("Extension not handled: {$ext}");
        }
    }

    /**
     * @param $data
     */
    public function pushEntryToKafka($data)
    {
        $topic = Constants::KAFKA_TOPIC.$this->mode;

        $context = [
            'request_id' => $this->app['request']->getId(),
            'task_id' => $this->app['request']->getTaskId()
        ];

        $event = [
            Constants::EVENT_NAME         => "EMANDATE.FILE.GENERATION.EVENT",
            Constants::EVENT_TYPE         => "emandate-file-generation-debit-event",
            Constants::VERSION            => "v1",
            Constants::EVENT_TIMESTAMP    => Carbon::now()->timestamp,
            Constants::PRODUCER_TIMESTAMP => Carbon::now()->timestamp,
            Constants::SOURCE             => "emandate_file_generation",
            Constants::MODE               => $this->mode,
            Constants::REQUEST_ID         => $this->app['request']->getId(),
            Constants::PROPERTIES         => $data,
            Constants::CONTEXT            => $context
        ];

        (new KafkaProducer($topic, stringify($event)))->Produce();
    }

    protected function parseCsvFile(string $file)
    {
        $rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $data = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }
            if (blank($row) === false)
            {
                $data[] = str_getcsv($row);
            }
        }

        return $data;
    }

    protected function parseTextFile(string $file)
    {
        $rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $data = [];

        foreach ($rows as $index => $row)
        {
            if ($index === 0) {
                continue;
            }
            if (blank($row) === false)
            {
                $data[] = $this->parseTextRow($row);
            }
        }

        return $data;
    }

    protected function parseExcelSheets($filePath, $numRowsToSkip = 0): array
    {
        $fileType = SpreadsheetIOFactory::identify($filePath);

        $reader = SpreadsheetIOFactory::createReader($fileType);

        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new PhpSpreadsheetDefaultValueBinder);

        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        assertTrue($spreadsheet->getSheetCount() === 1);

        $rows = $spreadsheet->getActiveSheet()->toArray(null, false);

        $rows = array_slice($rows, $numRowsToSkip);

        if($this->gatewayFile->getTarget() === 'axis_v2')
        {
            return $rows;
        }

        $headers = array_values(array_shift($rows) ?? []);

        // No rows exists
        if (empty($headers) === true)
        {
            return [];
        }

        // Format rows as "heading key => value" kind of associative array
        foreach ($rows as & $row)
        {
            $row = array_combine($headers, array_values($row));
        }

        return $rows;
    }

    protected function isOffsetExists(string $type): bool
    {
        $existingTypes = ['paper_nach_citi'];
        return in_array($type, $existingTypes);
    }
}
