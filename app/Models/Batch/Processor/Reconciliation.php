<?php

namespace RZP\Models\Batch\Processor;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use Symfony\Component\HttpFoundation\File\File;

use RZP\Models\Batch;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
use RZP\Models\Batch\Status;
use RZP\Reconciliator\Converter;
use RZP\Reconciliator\FileProcessor;

class Reconciliation extends Base
{
    const EXTRA_DETAILS = 'extra_details';
    const FILE_DETAILS  = 'file_details';

    /**
     * Lock wait timeout for reconciliation batch entity
     */
    const MUTEX_LOCK_TIMEOUT = 3600;

    protected $converter;

    protected $gatewayReconciliator;

    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);

        $this->converter = new Converter;
    }

    /**
     * Saves recon file to s3 using UFH and updates batch entity with relevant file details.
     * This is done in sync when the request is received and not inside queue
     *
     * @param  array  $input batch creation params
     */
    public function storeInputFileAndCreateBatch(array $input)
    {
        $this->repo->transaction(function () use ($input)
        {
            $reconFileDetails = $input[Batch\Entity::FILE];

            $this->uploadReconFile($reconFileDetails);

            $this->repo->saveOrFail($this->batch);
        });
    }

    protected function uploadReconFile(array $inputFileDetails)
    {
        $this->trace->info(TraceCode::BATCH_UPLOADING_FILE, $this->batch->toArray());

        // We need the original file name with extension while moving the recon file
        // to local directory used by UFH
        $fileNameWithExt = $inputFileDetails[FileProcessor::FILE_NAME];

        // Here we get the original filename without the extension and prepend
        // the batch/upload prefix to indicate it is an input file for batch
        // We use the original filename here instead of the batch id as it s required
        // by the reconciliator classes to determine the type of reconciliation
        $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
        $fileName = $this->batch->getFilePrefix() . $fileName;

        $file = new File($inputFileDetails[FileProcessor::FILE_PATH]);

        // we move the file to storage location used by UFH Accessor, so that S3
        // mock works successfully.
        $file = $file->move($this->batch->getLocalSaveDir(), $fileNameWithExt);

        $ufh = new FileStore\Creator;

        $ufh->localFilePath($file->getPathname())
            ->mime($inputFileDetails[FileProcessor::MIME_TYPE])
            ->name($fileName)
            ->extension($inputFileDetails[FileProcessor::EXTENSION])
            ->entity($this->batch)
            ->type(FileStore\Type::BATCH_RECON_INPUT)
            ->deleteLocalFile()
            ->save();

        $ufhFile = $ufh->getFileInstance();

        $this->batch->setUploadFileUrl($ufh->getUrl());

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $ufhFile->toArrayPublic());
    }

    protected function performPreProcessingActions()
    {
        $this->setGatewayReconciliatorObject();

        $this->batch->incrementAttempts();
    }

    protected function setGatewayReconciliatorObject()
    {
        $gateway = $this->batch->getGateway();

        $gatewayReconciliatorClassName = 'RZP\\Reconciliator' . '\\' . $gateway . '\\' . 'Reconciliate';

        $this->gatewayReconciliator = new $gatewayReconciliatorClassName;
    }

    /**
     * We download the recon file for processing here, and parse the contents.
     * We then process the contents of the recon file by calling the gateway recon class.
     * Any unhandled exceptions in the reconciliator is being handled here.
     */
    protected function parseAndProcessBatchData()
    {
        $fileContents = $this->parseInputFileContents();

        $this->gatewayReconciliator->startReconciliationV2($fileContents, $this->batch);

        $this->updateBatchStatus();
    }

    protected function shouldMarkProcessed(): bool
    {
        return false;
    }

    /**
     * Parses the file and converts the contents into an in memory array
     * @return array parsed contents of the recon file
     */
    protected function parseInputFileContents(): array
    {
        $inputFileDetails = $this->getInputFileDetails();

        $fileType = $inputFileDetails[FileProcessor::FILE_TYPE];

        if ($fileType === FileProcessor::EXCEL)
        {
            $fileContent = $this->parseExcelContent($inputFileDetails);
        }
        else if ($fileType === FileProcessor::CSV)
        {
            $fileContent = $this->parseCsvContent($inputFileDetails);
        }
        else
        {
            throw new Exception\ReconciliationException(
                'File is neither an Excel nor a CSV type.',
                ['file_details' => $inputFileDetails]
            );
        }

        return $fileContent;
    }

    protected function updateBatchPostProcessing()
    {
        $now = Carbon::now()->getTimestamp();

        // TBD for failed batches should we have separate failed_at timestamp
        $this->batch->setProcessedAt($now);
    }

    protected function parseExcelContent(array $inputFileDetails): array
    {
        $fileContents = [];

        $totalCount = 0;
        //
        // Gets the sheet names which need to be collected for the given gateway.
        // Returns empty if there is no restriction on which sheets to collect.
        // If sheetNames returned is empty, ensure that the gateway does not perform
        // any operation based on the sheet name.
        //
        $sheetNames = $this->gatewayReconciliator->getSheetNames();

        $startRow = $this->gatewayReconciliator->getStartRow($inputFileDetails);

        $excelArray = $this->converter->convertExcelToArray($inputFileDetails, $sheetNames, $startRow);

        // @todo see if this part can be refactored better
        foreach ($excelArray as $sheetName => $sheetData)
        {
            $inputFileDetails[FileProcessor::SHEET_NAME] = $sheetName;

            $totalCount += count($sheetData);

            $this->setExtraDetails($sheetData, $inputFileDetails);

            $fileContents[] = $sheetData;
        }

        $this->batch->setTotalCount($totalCount);

        return $fileContents;
    }

    protected function parseCsvContent(array $fileDetails)
    {
        $totalCount = 0;

        $fileContents = [];

        $columnHeaders = $this->getColumnHeadersForGatewayIfApplicable($fileDetails);

        $linesToSkip = $this->gatewayReconciliator->getNumLinesToSkip($fileDetails);

        $delimiter = $this->gatewayReconciliator->getDelimiter();

        $csvArray = $this->converter->convertCsvToArray($fileDetails, $columnHeaders, $linesToSkip, $delimiter);

        $totalCount += count($csvArray);

        $this->setExtraDetails($csvArray, $fileDetails);

        $this->batch->setTotalCount($totalCount);

        $fileContents[] = $csvArray;

        return $fileContents;
    }

    /**
    * In case of some csv files, the column headers are not present in the csv.
    * These have to be manually defined in the bank reconciliator file.
    *
    * @param $fileDetails
    *
    * @return array
    */
    protected function getColumnHeadersForGatewayIfApplicable($fileDetails)
    {
        $fileName = $fileDetails[FileProcessor::FILE_NAME];

        $reconType = $this->gatewayReconciliator->getReconciliationTypeFromFileName($fileName);

        $columnHeaders = $this->gatewayReconciliator->getColumnHeadersForType($reconType);

        return $columnHeaders;
    }

    protected function setExtraDetails(array & $arrayContent, array $fileDetails)
    {
        $arrayContent[self::EXTRA_DETAILS][self::FILE_DETAILS] = $fileDetails;
    }

    /**
     * Downloads the file from S3 and returns the metadata regarding the same
     *
     * @return array downloaded recon file metadata
     */
    protected function getInputFileDetails(): array
    {
        $inputFile = $this->batch->inputFile();

        $filePath = (new FileStore\Accessor)
                    ->id($inputFile->getId())
                    ->getFile();

        $fileType = FileProcessor::getFileType($inputFile->getMime());

        if (empty($fileType) === true)
        {
            // @todo handle this exception better
            throw new Exception\ReconciliationException(
                'Unsupported file type.', ['file_details' => $fileDetails, 'file_type' => $fileType]
            );
        }

        return [
            FileProcessor::FILE_NAME => $inputFile->getName(),
            FileProcessor::EXTENSION => $inputFile->getExtension(),
            FileProcessor::MIME_TYPE => $inputFile->getMime(),
            FileProcessor::SIZE      => $inputFile->getSize(),
            FileProcessor::FILE_PATH => $filePath,
            FileProcessor::FILE_TYPE => $fileType,
        ];
    }

    /**
     * Un case of unhandled exceptions in case of recon, we still mark the batch
     * as partially processed if we were able to process some rows and had abort on a
     * particular row. Basically we mark it as partially processed if we were able
     * to get the total number of rows
     *
     * @param  \Throwable $ex Exception thrown while processing the batch
     */
    protected function handleBatchProcessingException(\Throwable $ex)
    {
        $this->trace->traceException(
                            $ex,
                            Trace::ERROR,
                            TraceCode::BATCH_FILE_PROCESSING_ERROR,
                            [
                                Batch\Entity::ID => $this->batch->getId()
                            ]);

        if ($this->batch->getTotalCount() > 0)
        {
            $this->batch->setStatus(Status::PARTIALLY_PROCESSED);
        }
        else if ($this->batch->isPartiallyProcessed() === false)
        {
            $this->batch->setStatus(Status::FAILED);
        }

        $this->batch->setFailureReason($ex->getMessage());
    }

    protected function postProcess()
    {
        $this->batch->setProcessing(0);

        $this->repo->saveOrFail($this->batch);
    }

    /**
     * The reconciliation can run for a long time.
     * Hence, changing the system's execution time limit to 1 hour.
     */
    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setTimeLimit(3600);
    }
}
