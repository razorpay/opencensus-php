<?php

namespace RZP\Reconciliator;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\Constants;
use Symfony\Component\HttpFoundation\File\File;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\FileStore\Format;
use RZP\Models\Merchant\Account;
use RZP\Reconciliator\FileProcessor;
use RZP\Models\Base\PublicCollection;
use RZP\Reconciliator\RequestProcessor;

class Orchestrator extends Base\Core
{
    /**
     * This contains file details, sheet details whenever applicable.
     * It does not contain the actual content.
     * It's all meta data.
     */
    const EXTRA_DETAILS           = 'extra_details';

    /*********************
     * Instance variables
     *********************/

    protected $inputDetails;
    protected $allFilesDetails;
    protected $allFilesContents;

    /********************
     * Instance objects
     ********************/

    protected $validator;
    protected $fileProcessor;
    protected $converter;
    protected $gatewayReconciliator;
    protected $messenger;
    protected $gateway;
    protected $sharedMerchant;
    protected $batchCore;

    public function __construct(string $gateway, $gatewayReconciliator)
    {
        parent::__construct();

        $this->gateway = $gateway;

        $this->gatewayReconciliator = $gatewayReconciliator;

        $this->increaseAllowedSystemLimits();

        $this->messenger     = new Messenger;
        $this->validator     = new Validator;
        $this->fileProcessor = new FileProcessor;
        $this->converter     = new Converter($gateway);

        $this->sharedMerchant = $this->repo
                                     ->merchant
                                     ->findOrFailPublic(Account::SHARED_ACCOUNT);

        $this->batchCore = new Batch\Core;
    }

    /**
     * Validates each file.
     * Gets the content of each file and stores it in an array.
     * Deletes the file from local storage.
     * Calls the gateway reconciliator with
     * all the file details and file contents.
     *
     * @param array $reconDetails
     *
     * @return array
     *
     * @throws Exception\ReconciliationException
     */
    public function orchestrate(array $reconDetails)
    {
        $this->allFilesDetails = $reconDetails[RequestProcessor\Base::FILE_DETAILS] ?? [];
        $this->inputDetails = $reconDetails[RequestProcessor\Base::INPUT_DETAILS] ?? [];

        // Run validations and conversions on each file
        foreach ($this->allFilesDetails as $file => $fileDetails)
        {
            $skipFile = $this->shouldSkipFile($fileDetails);

            if ($skipFile === true)
            {
                $this->handleFileSkip($file, $fileDetails);

                continue;
            }

            try
            {
                // Converts to in-memory array and stores it in instance variable.
                // Might have to move this into Gateway implementation since
                // conversion to array might be different for different gateways.
                $this->getFileContentInArrayAndSet($fileDetails);
            }
            catch (\Throwable $ex)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'   => TraceCode::RECON_FILE_SKIP,
                        'message'      => 'Skipping file because not able to convert file content to array. -> ' .
                                            $ex->getMessage(),
                        'file_details' => $fileDetails,
                        'gateway'      => $this->gateway,
                    ]);

                $this->trace->traceException($ex);

                $this->handleFileSkip($file, $fileDetails);

                // Don't get the content of the file.
                continue;
            }

            //
            // Delete the file. We have all the data in $allFilesContents.
            // Ensure that you don't delete the directory by mistake.
            // In case of zip files, that's fine. But otherwise, it'll delete
            // off the settlement folder only.
            //
            $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
        }

        if (empty($this->allFilesContents) === true)
        {
            throw new Exception\ReconciliationException(
                'File contents are empty.',
                [
                    'all_files_details' => $this->allFilesDetails,
                ]);
        }

        return $this->gatewayReconciliator->startReconciliation($this->allFilesContents);
    }

    /**
     * Validates each file.
     * Gets the content of each file and stores it in an array.
     * Deletes the file from local storage.
     * Creates a batch entity with the reconciliation details
     * and queues it for processing
     *
     * @param array $reconDetails
     *
     * @return array
     *
     * @throws Exception\ReconciliationException
     */
    public function orchestrateV2(array $reconDetails)
    {
        $this->allFilesDetails = $reconDetails[RequestProcessor\Base::FILE_DETAILS] ?? [];
        $this->inputDetails = $reconDetails[RequestProcessor\Base::INPUT_DETAILS] ?? [];

        $batches = new PublicCollection;

        foreach ($this->allFilesDetails as $fileIndex => $fileDetails)
        {
            $skipFile = $this->shouldSkipFile($fileDetails, $this->inputDetails);

            if ($skipFile === true)
            {
                $this->handleFileSkip($fileIndex, $fileDetails);

                continue;
            }

            try
            {
                // Creates batch with relevant params and dispatches for processing via queue
                $batch = $this->createBatchAndDispatchForProcessing($fileDetails);

                $batches->push($batch);
            }
            catch (\Throwable $ex)
            {
                $this->handleBatchCreationError($ex, $fileIndex, $fileDetails);

                continue;
            }

            //
            // Delete the file. We have all the data in $allFilesContents.
            // Ensure that you don't delete the directory by mistake.
            // In case of zip files, that's fine. But otherwise, it'll delete
            // off the settlement folder only.
            //
            $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
        }

        if ($batches->isEmpty() === true)
        {
            throw new Exception\ReconciliationException(
                'No batches created for recon',
                [
                    'all_files_details' => $this->allFilesDetails,
                ]);
        }

        $result = $batches->toArrayAdmin();

        return $result;
    }

    protected function shouldSkipFile(array $fileDetails, array $inputDetails = []): bool
    {
        // Checks if this particular file needs to be excluded for the gateway
        $shouldExclude = $this->gatewayReconciliator->inExcludeList($fileDetails, $inputDetails);

        if ($shouldExclude === true)
        {
            $this->trace->info(
                TraceCode::RECON_FILE_SKIP,
                [
                    'trace_code'   => TraceCode::RECON_FILE_SKIP,
                    'message'      => 'Skipping file because it is present in the exclude list of the gateway.',
                    'file_details' => $fileDetails,
                    'gateway'      => $this->gateway,
                ]);

            return true;
        }

        // Validate file size based on whether this recon
        // request will be forwarded to batch service.
        $variant = $this->app->razorx->getTreatment($this->gateway, Constants::BATCH_SERVICE_RECONCILIATION_MIGRATION, $this->mode);

        $forwardToBatchService = ($variant === 'on');

        // Validates the file type, size, etc..
        $validate = $this->validator->validateFile($fileDetails, $forwardToBatchService);

        if ($validate === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'   => TraceCode::RECON_FILE_SKIP,
                    'message'      => 'Skipping file because validations failed.',
                    'file_details' => $fileDetails,
                    'gateway'      => $this->gateway,
                ]);

            return true;
        }

        return false;
    }

    protected function handleFileSkip($file, array $fileDetails)
    {
        $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);

        // Remove the file from allFiles variable, since this file is now, not part of reconciliation.
        unset($this->allFilesDetails[$file]);
    }

    /**
     * Creates batch for recon and enqueues it for processing
     *
     * @param  array  $fileDetails Recon file details
     * @return Batch\Entity        batch entity created
     */
    protected function createBatchAndDispatchForProcessing(array $fileDetails): Batch\Entity
    {
        $file = new File($fileDetails[FileProcessor::FILE_PATH]);

        $params = [
            Batch\Entity::TYPE          => Batch\Type::RECONCILIATION,
            Batch\Entity::GATEWAY       => $this->gateway,
            Batch\Entity::FILE          => $file,
        ];

        $this->updateBatchConfigParamsIfPresent($params);

        $batch = $this->batchCore->create($params, $this->sharedMerchant);

        return $batch;
    }

    protected function updateBatchConfigParamsIfPresent(array & $params)
    {
        $batchConfig = array_filter(array_only($this->inputDetails, RequestProcessor\Base::CONFIG_PARAMS));

        if (empty($batchConfig) === false)
        {
            $params[Batch\Entity::CONFIG] = $batchConfig;
        }
    }

    protected function handleBatchCreationError(
        \Throwable $ex,
        int $fileIndex,
        array $fileDetails)
    {
        $this->trace->traceException($ex);

        $this->messenger->raiseReconAlert(
            [
                'trace_code'   => TraceCode::RECON_BATCH_CREATION_FAILED,
                'message'      => 'Skipping file because not able to create batch for the file. -> ' .
                                    $ex->getMessage(),
                'file_details' => $fileDetails,
                'gateway'      => $this->gateway,
            ]);

        $this->handleFileSkip($fileIndex, $fileDetails);
    }

    /**
     * Converts the data in file (excel/csv) and sets to in-memory array.
     *
     * @param $fileDetails
     * @throws Exception\ReconciliationException
     */
    protected function getFileContentInArrayAndSet(array $fileDetails)
    {
        $this->trace->info(
            TraceCode::RECON_BEGIN_FILE_PARSING,
            [
                'gateway'      => $this->gateway,
                'file_details' => $fileDetails,
            ]);

        $fileType = $this->gatewayReconciliator->getFileType($fileDetails[FileProcessor::MIME_TYPE]);

        $fileDetails[FileProcessor::FILE_TYPE] = $fileType;

        if (empty($fileType) === true)
        {
            // Throwing exception instead of raising alert, because the parent function needs to
            // perform some operations if this condition block executes to true.
            throw new Exception\ReconciliationException(
                'Unsupported file type.', ['file_details' => $fileDetails, 'file_type' => $fileType]
            );
        }

        if ($fileType === FileProcessor::EXCEL)
        {
            $this->handleSettingExcelContent($fileDetails);
        }
        else if ($fileType === FileProcessor::CSV)
        {
            $this->handleSettingCsvContent($fileDetails);
        }
        else
        {
            throw new Exception\ReconciliationException(
                'File is neither an Excel nor a CSV type.',
                ['file_details' => $fileDetails]
            );
        }

        $this->trace->info(TraceCode::RECON_END_FILE_PARSING, [
            'gateway'      => $this->gateway,
            'file_details' => $fileDetails,
        ]);

    }

    /**
     * Converts and sets the excel content in an array.
     *
     * @param array $fileDetails
     */
    protected function handleSettingExcelContent(array $fileDetails)
    {
        //
        // Gets the sheet names which need to be collected for the given gateway.
        // Returns empty if there is no restriction on which sheets to collect.
        // If sheetNames returned is empty, ensure that the gateway does not perform
        // any operation based on the sheet name.
        //
        $sheetNames = $this->gatewayReconciliator->getSheetNames($fileDetails);

        $startRow = $this->gatewayReconciliator->getStartRow($fileDetails);

        $keyColumnNames = $this->gatewayReconciliator->getKeyColumnNames($fileDetails);

        if ($fileDetails[FileProcessor::EXTENSION] === Format::XLSX)
        {
            // getting contents using spout library for xlsx
            $sheetsContents = $this->converter->getRowsFromExcelSheetsSpout($fileDetails, $sheetNames, $startRow, $keyColumnNames);
        }
        else
        {
            $sheetsContents = $this->converter->getRowsFromExcelSheetsOptimized($fileDetails, $sheetNames, $startRow, $keyColumnNames);
        }

        foreach ($sheetsContents as $sheetName => $sheetData)
        {
            if (empty($sheetData) === true)
            {
                // This would happen when the sheet name sent, does not exist
                continue;
            }

            $fileDetails[FileProcessor::SHEET_NAME] = $sheetName;

            $this->setExtraDetails($sheetData, $fileDetails);

            $this->allFilesContents[] = $sheetData;
        }
    }

    protected function handleSettingCsvContent($fileDetails)
    {
        $columnHeaders = $this->getColumnHeadersForGatewayIfApplicable($fileDetails);

        $linesToSkip = $this->gatewayReconciliator->getNumLinesToSkip($fileDetails);

        $delimiter = $this->gatewayReconciliator->getDelimiter();

        $csvArray = $this->converter->convertCsvToArray(
            $fileDetails,
            $columnHeaders,
            $linesToSkip,
            $delimiter,
            $this->gateway);

        $this->setExtraDetails($csvArray, $fileDetails);
        $this->allFilesContents[] = $csvArray;
    }

    protected function setExtraDetails(& $arrayContent, $fileDetails)
    {
        $arrayContent[self::EXTRA_DETAILS][RequestProcessor\Base::FILE_DETAILS] = $fileDetails;

        $arrayContent[self::EXTRA_DETAILS][RequestProcessor\Base::INPUT_DETAILS] = $this->inputDetails;
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

    /**
     * The reconciliation can run for a long time.
     * Hence, changing the system's execution time limit to 1 hour.
     */
    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setTimeLimit(3600);

        //
        // In certain cases XLS parsing takes a long time. We are setting
        // the execution time to 60 min here to prevent the execution
        // from being terminated.
        //
        RuntimeManager::setMaxExecTime(3600);
    }
}
