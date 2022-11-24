<?php

namespace RZP\Models\Batch\Processor;

use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Metrics\Metric;
use RZP\Reconciliator\Base\Reconciliate;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Mime\FileBinaryMimeTypeGuesser;

use RZP\Exception;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
use RZP\Reconciliator\Converter;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\FileProcessor;
use RZP\Reconciliator\Base\Constants;
use RZP\Reconciliator\RequestProcessor;
use RZP\Reconciliator\Base\Foundation\ScroogeReconciliate;

class Reconciliation extends Base
{
    const EXTRA_DETAILS = 'extra_details';
    const FILE_DETAILS  = 'file_details';
    const INPUT_DETAILS = 'input_details';

    // Flag to indicate if this recon request
    // has come from batch service
    const BATCH_SERVICE_RECON_REQUEST = 'batch_service_recon_request';

    const IS_GATEWAY_CAPTURED_MISMATCH = 'is_gateway_captured_mismatch';

    /**
     * Lock wait timeout for reconciliation batch entity
     */
    const MUTEX_LOCK_TIMEOUT = 3600;

    protected $converter;

    /**
     * Represents the reconciliator class for individual gateway
     *
     * @var \RZP\Reconciliator\Base\Reconciliate
     */
    protected $gatewayReconciliator;

    public function __construct(Batch\Entity $batch = null)
    {
        parent::__construct($batch);

        if ($batch !== null)
        {
            $this->converter = new Converter($batch->getGateway());
        }
        $this->registerMimeTypeGuesser();
    }

    public function process()
    {
        parent::process();

        $this->scroogeDispatch();
    }

    public function setScroogeDispatchData(array $scroogeData)
    {
        if (empty($this->scroogeDispatchData) === true)
        {
            // setting for the first time
            $this->scroogeDispatchData = $scroogeData;
        }
        else
        {
            //
            // When excel sheet has multiple sheets, then this method
            // is called for each sheet. So we should not overwrite the
            // scroogeDispatchData, instead we add them along with refunds
            // belonging to previous sheets.
            //
            $refunds = $scroogeData['data'];

            foreach ($refunds as $refundId => $refundDetails)
            {
                $this->scroogeDispatchData['data'][$refundId] = $refundDetails;
            }
        }
    }

    public function setReconBatchOutputData(array $data)
    {
        $this->reconBatchOutputData = $data;
    }

    public function getReconBatchOutputData()
    {
        return $this->reconBatchOutputData;
    }

    public function setStatusAfterSuccessfulProcessing()
    {
        // Update status only if there are no scrooge refunds in the batch
        if ($this->hasScroogeRefunds() === false)
        {
            parent::setStatusAfterSuccessfulProcessing();
        }

        // Else ignore, we will be updating it post dispatch
    }

    protected function postProcess()
    {
        // Check if we have any scrooge refunds to process,
        // If yes, we will skip updating `is_processing` & `processed_at`
        // If not, we will update `is_processing` to false & `processed_at` to current timestamp,
        // as there will not be any async dispatch process
        if ($this->hasScroogeRefunds() === false)
        {
            $this->trace->info(TraceCode::BATCH_FILE_PROCESSED, $this->batch->toArrayTraceAll());

            $this->updateStatusPostProcess();
        }

        $this->repo->saveOrFail($this->batch);

        //
        // For reconciliation batch we don't need to send any mail,
        // hence not sending any email here (which is present in parent)
        //

        $this->deleteLocalFiles();
    }

    public function hasScroogeRefunds()
    {
        return (empty($this->scroogeDispatchData['data']) === false);
    }

    protected function scroogeDispatch()
    {
        if ($this->hasScroogeRefunds() === false)
        {
            return;
        }

        $data = $this->scroogeDispatchData['data'] ?? [];

        $forceUpdateArn = $this->scroogeDispatchData['force_update_arn'] ?? false;

        $source = $this->scroogeDispatchData['source'] ?? 'unknown';

        $gateway = $this->scroogeDispatchData['gateway'];

        $batchId = $this->scroogeDispatchData['batch_id'];

        (new ScroogeReconciliate)->callRefundReconcileFunctionOnScrooge(
                                                                            $data,
                                                                            $forceUpdateArn,
                                                                            $this->batch,
                                                                            $source,
                                                                            $gateway,
                                                                            $batchId
                                                                        );
    }

    /**
     * We do this because two guessers are registered by default:
     *   - FileBinaryMimeTypeGuesser
     *   - FileinfoMimeTypeGuesser
     * FileinfoMimeTypeGuesser is given the higher preference.
     * To give FileBinaryMimeTypeGuesser the higher preference,
     * we have to re-register it like a custom guesser.
     * Check Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser class
     * for more information around this.
     *
     * FileBinaryMimeTypeGuesser seems to be better guesser of the two.
     * It runs the command `file -b --mime %s` to get the mime type.
     * For some text files, `FileinfoMimeTypeGuesser` gives application/zlib and
     * `FileBinaryMimeTypeGuesser` gives text/plain (correct!)
     */
    protected function registerMimeTypeGuesser()
    {
        $guesser = new MimeTypes();

        $guesser->registerGuesser(new FileBinaryMimeTypeGuesser());
    }

    protected function saveInputFile(File $file): FileStore\Creator
    {
        // If this recon request is to be sent to batch service then
        // it should be uploaded to batch service bucket
        if ($this->shouldSendToBatchService())
        {
            return parent::saveInputFile($file);
        }

        $this->trace->info(TraceCode::BATCH_UPLOADING_FILE, $this->batch->toArrayTrace());

        // We need the original file name with extension while moving the recon file
        // to local directory used by UFH
        $fileNameWithExt = strtolower($file->getFilename());

        // Here we get the original filename without the extension and prepend
        // the batch/upload prefix to indicate it is an input file for batch
        // We use the original filename here instead of the batch id as it s required
        // by the reconciliator classes to determine the type of reconciliation
        $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);

        //
        // Adding batch id in filename to make file name unique per batch and
        // to avoid replacement of file in case new batch is created with same name.
        //
        $fileName = Batch\Entity::INPUT_FILE_PREFIX . $fileName . '_' . $this->batch->getId();

        $extension = strtolower($file->getExtension());
        $mimeType = strtolower(mime_content_type($file->getRealPath()));

        // we move the file to storage location used by UFH Accessor, so that S3
        // mock works successfully.
        $movedFile = $file->move(
                        $this->batch->getLocalSaveDir(Batch\Entity::INPUT_FILE_PREFIX),
                        $fileNameWithExt);

        $ufh = new FileStore\Creator;

        $ufh->localFilePath($movedFile->getPathname())
            ->mime($mimeType)
            ->name($fileName)
            ->extension($extension)
            ->type(FileStore\Type::RECONCILIATION_BATCH_INPUT)
            ->save();

        $this->trace->info(
            TraceCode::BATCH_UPLOAD_FILE,
            $ufh->getFileInstance()->toArrayPublic());

        return $ufh;
    }

    protected function validateInputFileEntries(array $input): array
    {
        //
        // Not doing anything here as in recon we don't need to validate / parse
        // entries at the time of saving the input file.
        //
        return [];
    }

    public function addSettingsIfRequired(& $input)
    {
        $forceAuthorize = $input[Batch\Entity::CONFIG][RequestProcessor\Base::FORCE_AUTHORIZE] ?? [];

        $forceUpdate = $input[Batch\Entity::CONFIG][RequestProcessor\Base::FORCE_UPDATE] ?? [];

        $gateway = $this->batch->getGateway();

        $gatewayReconciliatorClassName = 'RZP\\Reconciliator' . '\\' . $gateway . '\\' . 'Reconciliate';

        $gatewayReconciliator = new $gatewayReconciliatorClassName($gateway);

        $filename = $input['file']->getFilename();

        $subType = $gatewayReconciliator->getReconciliationTypeFromFileName($filename);

        //
        // If sub_type is NA or null etc, we can not forward the
        // request to batch service, so better to throw exception.
        //
        if ((in_array($subType, Reconciliate::VALID_RECON_TYPES, true) === false))
        {
            $this->trace->info(
                TraceCode::RECON_CRITICAL_ALERT,
                [
                    'info_code'             => InfoCode::RECON_FILE_SKIPPED_DUE_TO_UNKNOWN_RECON_TYPE,
                    'reconciliation_type'   => $subType,
                    'file_name'             => $filename,
                    'gateway'               => $gateway,
                ]);

            throw new Exception\ReconciliationException(
                'Unable to figure out the reconciliation type. Skipping this file. Please check the filename.');
        }

        $input[Batch\Entity::CONFIG][Constants::GATEWAY]                        = $gateway;
        $input[Batch\Entity::CONFIG][Constants::SUB_TYPE]                       = $subType;
        $input[Batch\Entity::CONFIG][RequestProcessor\Base::FORCE_AUTHORIZE]    = $forceAuthorize;
        $input[Batch\Entity::CONFIG][RequestProcessor\Base::FORCE_UPDATE]       = $forceUpdate;

        $this->trace->info(TraceCode::RECON_BATCH_SERVICE_INPUT_CONFIG, $input);
    }

    protected function performPreProcessingActions()
    {
        parent::performPreProcessingActions();

        //
        // We need the gateway reconciliator object to get some gateway specific
        // details like sheet names etc which are required during parsing of the file
        //
        $this->setGatewayReconciliatorObject();
    }

    protected function setGatewayReconciliatorObject()
    {
        $gateway = $this->batch->getGateway();

        if ($this->isManualReconFile() === true)
        {
            $gatewayReconciliatorClassName = 'RZP\Reconciliator\Base\ManualReconciliate';
        }
        else
        {
            $gatewayReconciliatorClassName = 'RZP\\Reconciliator' . '\\' . $gateway . '\\' . 'Reconciliate';
        }

        $this->gatewayReconciliator = new $gatewayReconciliatorClassName($gateway);
    }

    /**
     * Checks if this is manual recon file prepared by FinOps.
     * @return bool
     */
    protected function isManualReconFile()
    {
        $keyExists = $this->settingsAccessor->exists(RequestProcessor\Base::MANUAL_RECON_FILE);

        $manualReconFile = $this->settingsAccessor->get(RequestProcessor\Base::MANUAL_RECON_FILE);

        //
        // Note : We can not just use get() and convert the value to bool, bcoz when key does not
        // exist, then it returns an instance of Dictionary.
        //
        $result = ($keyExists === false) ? false : ($manualReconFile === '1');

        return $result;
    }

    /**
     * We call the gateway's reconciliator class with
     * the file entries obtained by parsing the file
     * @param array $entries
     */
    protected function processEntries(array & $entries)
    {
        $start = time();

        $source = $this->settingsAccessor->get(RequestProcessor\Base::SOURCE);

        $this->gatewayReconciliator->startReconciliationV2($entries, $this, $source);

        $processingTime = (time() - $start);

        $gateway = $this->batch->getGateway();

        $dimensions = Metric::getFileProcessingMetricDimension($gateway, $source);

        $this->trace->histogram(Metric::RECON_MIS_FILE_PROCESSING_TIME_SECONDS, $processingTime, $dimensions);
    }

    /**
     * We call the gateway's reconciliator class with
     * the recon inputs from batch service
     * @param array $entries
     * @return mixed
     */
    public function batchProcessEntries(array $entries)
    {
        $gateway = $entries[0][Orchestrator::EXTRA_DETAILS][Batch\Entity::CONFIG][Constants::GATEWAY];

        $source = $entries[0][Orchestrator::EXTRA_DETAILS][Batch\Entity::CONFIG][Constants::SOURCE];

        $gatewayReconciliatorClassName = 'RZP\\Reconciliator' . '\\' . $gateway . '\\' . 'Reconciliate';

        $this->gatewayReconciliator = new $gatewayReconciliatorClassName($gateway);

        $response = $this->gatewayReconciliator->startReconciliationV2($entries, $this, $source);

        $this->scroogeDispatch();

        return $response;
    }

    protected function postProcessEntries(array & $entries)
    {
        //
        // Not doing anything here, as the processing metadata (success_count / failure_count)
        // etc, is updated during reconciliation itself to the batch entity. We cant return
        // the run summary from reconciliator, as that is obtained inside a finally block
        // and returning from the same also suppresses any exception being thrown by reconciliator
        //
        return;
    }

    protected function createSetOutputFileAndSave(array & $entries, string $fileType = FileStore\Type::BATCH_OUTPUT)
    {
        //
        // For recon batch procesing we don't need to create any output file.
        //
        return;
    }

    protected function shouldMarkProcessedOnFailures(): bool
    {
        return false;
    }

    /**
     * Parses the file and converts the contents into an in memory array
     *
     * @param  string $filePath  Path of the file to be parsed
     *
     * @return array parsed contents of the recon file
     *
     * @throws Exception\ReconciliationException
     */
    protected function parseFile(string $filePath): array
    {
        $start = time();

        $inputFileDetails = $this->getInputFileDetails($filePath);

        $this->gatewayReconciliator->getDecryptedFile($inputFileDetails);

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
                [
                    self::FILE_DETAILS => $inputFileDetails
                ]);
        }

        $fileParseTime = (time() - $start);

        $source = $this->settingsAccessor->get(RequestProcessor\Base::SOURCE);
        $gateway = $this->batch->getGateway();

        $dimensions = Metric::getFileProcessingMetricDimension($gateway, $source);

        $this->trace->histogram(Metric::RECON_MIS_FILE_PARSING_TIME_SECONDS, $fileParseTime, $dimensions);

        return $fileContent;
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
        $sheetNames = $this->gatewayReconciliator->getSheetNames($inputFileDetails);

        $startRow = $this->gatewayReconciliator->getStartRow($inputFileDetails);

        $keyColumnNames = $this->gatewayReconciliator->getKeyColumnNames($inputFileDetails);

        $excelArray = $this->converter->convertExcelToArray($inputFileDetails,
                                                            $sheetNames,
                                                            $startRow,
                                                            $keyColumnNames,
                                                            $this->batch->getGateway());

        $sheetCount = count(array_keys($excelArray));

        foreach ($excelArray as $sheetName => $sheetData)
        {
            $inputFileDetails[FileProcessor::SHEET_NAME] = $sheetName;
            $inputFileDetails[FileProcessor::SHEET_COUNT] = $sheetCount;

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

        $csvArray = $this->converter->convertCsvToArray($fileDetails,
                                                        $columnHeaders,
                                                        $linesToSkip,
                                                        $delimiter,
                                                        $this->batch->getGateway());

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
        $arrayContent[self::EXTRA_DETAILS][RequestProcessor\Base::FILE_DETAILS] = $fileDetails;

        $forceUpdateFields = $this->settingsAccessor->get(RequestProcessor\Base::FORCE_UPDATE)->toArray();

        $forceAuthorizePayments = $this->settingsAccessor->get(RequestProcessor\Base::FORCE_AUTHORIZE)->toArray();

        //
        // In some cases, like when batch is retried, there are no additional input_details
        // set, in the request. So we set input_details as an empty array.
        //
        $arrayContent[self::EXTRA_DETAILS][RequestProcessor\Base::INPUT_DETAILS] = [
            RequestProcessor\Base::FORCE_UPDATE     => $forceUpdateFields,
            RequestProcessor\Base::FORCE_AUTHORIZE  => $forceAuthorizePayments
        ];
    }

    /**
     * Downloads the file from S3 and returns the metadata regarding the same
     *
     * @param string $filePath
     *
     * @return array downloaded recon file metadata
     *
     * @throws Exception\ReconciliationException
     */
    protected function getInputFileDetails(string $filePath): array
    {
        $inputFile = new File($filePath);

        $mimeType = strtolower(mime_content_type($inputFile->getRealPath()));

        $fileType = $this->gatewayReconciliator->getFileType($mimeType);

        if (empty($fileType) === true)
        {
            throw new Exception\ReconciliationException(
                'Unsupported file type.', ['file_type' => $fileType]
            );
        }

        //
        // Removing appended batch id in file name because recon uses filename for some validations/configs.
        //
        $originalFileName = str_replace('_' . $this->batch->getId(), '', $inputFile->getFilename());

        return [
            FileProcessor::FILE_NAME => strtolower($originalFileName),
            FileProcessor::EXTENSION => strtolower($inputFile->getExtension()),
            FileProcessor::MIME_TYPE => $mimeType,
            FileProcessor::SIZE      => $inputFile->getSize(),
            FileProcessor::FILE_PATH => $filePath,
            FileProcessor::FILE_TYPE => $fileType,
        ];
    }

    protected function increaseAllowedSystemLimits()
    {
        //
        // Processing of file having size around 30M, consuming memory more than 910M.
        //
        RuntimeManager::setMemoryLimit('2048M');

        // As now reconciliation runs as K8s job, increasing time limit to 2 hour.
        // The reconciliation can run for a long time.
        // Hence, changing the script's execution time limit to 2 hour.
        //
        RuntimeManager::setTimeLimit(7200);

        //
        // In certain cases XLS parsing takes a long time. We are setting
        // the execution time to 120 min here to prevent the execution
        // from being terminated.
        //
        RuntimeManager::setMaxExecTime(7200);
    }

    /**
     * Overriding this function as not cleaning parsed recon entries at this step.
     * Filtering empty rows is already happening while parsing the recon file.
     * Don't want to iterate over 50,000 rows again.
     * @param array $entries
     * @return array
     */
    protected function cleanParsedEntries(array $entries): array
    {
        return $entries;
    }
}
