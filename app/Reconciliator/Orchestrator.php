<?php

namespace RZP\Reconciliator;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\FileStore\Format;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicCollection;

class Orchestrator extends Base\Core
{
    const GATEWAY = 'gateway';

    /**
     * This contains file details, sheet details and email details,
     * whenever applicable. It does not contain the actual content.
     * It's all meta data.
     */
    const EXTRA_DETAILS           = 'extra_details';
    const ATTACHMENT_COUNT        = 'attachment_count';
    const ATTACHMENT_HYPHEN_COUNT = 'attachment-count';
    const FORCE_UPDATE            = 'force_update';
    const INPUT_DETAILS           = 'input_details';

    /**************************
     * Email details constants
     **************************/
    const EMAIL_DETAILS    = 'email_details';
    const FROM             = 'from';
    const TO               = 'to';
    const SUBJECT          = 'subject';
    const TIMESTAMP        = 'timestamp';
    const BODY             = 'body';
    const BODY_HTML_TEXT   = 'body_html_text';

    /******************
     * Bank constants
     ******************/

    const HDFC                   = 'HDFC';
    const AXIS                   = 'Axis';
    const KOTAK                  = 'Kotak';
    const BILLDESK               = 'BillDesk';
    const PAYZAPP                = 'PayZapp';
    const MOBIKWIK               = 'Mobikwik';
    const PAYTM                  = 'Paytm';
    const OLAMONEY               = 'Olamoney';
    const FREECHARGE             = 'Freecharge';
    const NETBANKING_AXIS        = 'NetbankingAxis';
    const NETBANKING_ICICI       = 'NetbankingIcici';
    const NETBANKING_FEDERAL     = 'NetbankingFederal';
    const NETBANKING_BOB         = 'NetbankingBob';
    const NETBANKING_CORPORATION = 'NetbankingCorporation';
    const NETBANKING_RBL         = 'NetbankingRbl';
    const NETBANKING_INDUSIND    = 'NetbankingIndusind';
    const NETBANKING_PNB         = 'NetbankingPnb';
    const VIRTUAL_ACC_KOTAK      = 'VirtualAccKotak';
    const JIOMONEY               = 'Jiomoney';
    const EBS                    = 'Ebs';
    const FIRST_DATA             = 'FirstData';
    const ADMIN                  = 'admin';

    /**
     * Gateways for which we run validations on email content
     */
    const GATEWAY_EMAIL_VALIDATION = [
        self::HDFC,
        self::AXIS,
        self::KOTAK,
        self::OLAMONEY,
        self::FREECHARGE,
        self::FIRST_DATA,
        self::NETBANKING_AXIS,
        self::NETBANKING_ICICI,
        self::NETBANKING_FEDERAL,
        self::VIRTUAL_ACC_KOTAK,
    ];

    /**
     * Banks or Wallets which do not give the MIS file in attachments but as a
     * link
     */
    const LINK_BASED_BANKS = [
        self::FREECHARGE,
    ];

    /*
     *  These field can be force updated with passed with request
     */
    const REFUND_ARN = 'refund_arn';

    const BATCH_RECON_GATEWAYS = [
        self::JIOMONEY,
        self::FIRST_DATA
    ];

    /*********************
     * Instance variables
     *********************/

    protected $allFilesContents;
    protected $allFilesDetails;
    protected $inputDetails;

    /********************
     * Instance objects
     ********************/

    protected $validator;
    protected $fileProcessor;
    protected $converter;
    protected $gatewayReconciliator;
    protected $messenger;
    protected $gateway;

    public function __construct(string $gateway)
    {
        parent::__construct();

        $this->setGatewayReconciliatorObject($gateway);

        $this->increaseAllowedSystemLimits();

        $this->messenger     = new Messenger;
        $this->validator     = new Validator;
        $this->fileProcessor = new FileProcessor;
        $this->converter     = new Converter;
    }

    /**
     * Validates each file.
     * Gets the content of each file and stores it in an array.
     * Deletes the file from local storage.
     * Calls the gateway reconciliator with
     * all the file details and file contents.
     *
     * @throws Exception\ReconciliationException
     */
    public function orchestrate(array $allFilesDetails)
    {
        // Run validations and conversions on each file
        foreach ($allFilesDetails as $file => $fileDetails)
        {
            $this->trace->info(
                TraceCode::RECON_FILE_DETAILS,
                [
                    'message'      => 'File details of the file being orchestrated.',
                    'file_details' => $fileDetails
                ]
            );

            $skipFile = $this->shouldSkipFile($fileDetails);

            if ($skipFile === true)
            {
                $this->handleFileSkip($file, $fileDetails, $allFilesDetails);

                continue;
            }

            try
            {
                // Converts to in-memory array and stores it in instance variable.
                // Might have to move this into Gateway implementation since
                // conversion to array might be different for different gateways.
                $this->getFileContentInArrayAndSet($fileDetails);
            }
            catch (\Exception $ex)
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

                $this->handleFileSkip($file, $fileDetails, $allFilesDetails);

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

        if (empty($allFilesDetails) === true)
        {
            throw new Exception\ReconciliationException(
                'File contents are empty.',
                [
                    'all_files_details' => $allFilesDetails,
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
     * @throws Exception\ReconciliationException
     */
    public function orchestrateV2(array $allFilesDetails)
    {
        $batches = new PublicCollection;

        foreach ($allFilesDetails as $file => $fileDetails)
        {
            $this->trace->info(
                TraceCode::RECON_FILE_DETAILS,
                [
                    'message'      => 'File details of the file being orchestrated.',
                    'file_details' => $fileDetails
                ]
            );

            $skipFile = $this->shouldSkipFile($fileDetails);

            if ($skipFile === true)
            {
                $this->handleFileSkip($file, $fileDetails, $allFilesDetails);

                continue;
            }

            try
            {
                // Creates batch with relevant params and dispatches for processing via queue
                $batch = $this->createBatchAndDispatchForProcessing($fileDetails);

                $batches->push($batch);
            }
            catch (\Exception $ex)
            {
                $this->handleBatchCreationError($ex, $file, $fileDetails, $allFilesDetails);

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
                    'all_files_details' => $allFilesDetails,
                ]);
        }

        $result = $batches->toArrayAdmin();

        return $result;
    }

    protected function setGatewayReconciliatorObject($gateway)
    {
        $gatewayReconciliatorClassName = 'RZP\\Reconciliator' . '\\' . $gateway . '\\' . 'Reconciliate';

        $this->gateway = $gateway;

        $this->gatewayReconciliator = new $gatewayReconciliatorClassName;
    }

    protected function shouldSkipFile($fileDetails)
    {
        // Checks if this particular file needs to be excluded for the gateway
        $shouldExclude = $this->gatewayReconciliator->inExcludeList($fileDetails);

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

        // Validates the file type, size, etc..
        $validate = $this->validator->validateFile($fileDetails);

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

    protected function handleFileSkip($file, array $fileDetails, array & $allFilesDetails)
    {
        $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);

        // Remove the file from allFiles variable, since this file is now, not part of reconciliation.
        unset($allFilesDetails[$file]);
    }

    /**
     * Creates batch for recon and enqueues it for processing
     *
     * @param  array  $fileDetails Recon file details
     * @return Batch\Entity        batch entity created
     */
    protected function createBatchAndDispatchForProcessing(array $fileDetails): Batch\Entity
    {
        $merchant = $this->repo->merchant->findOrFailPublic(Account::SHARED_ACCOUNT);

        $params = [
            Batch\Entity::TYPE        => Batch\Type::RECONCILIATION,
            Batch\Entity::GATEWAY     => $this->gateway,
            Batch\Entity::FILE        => $fileDetails
        ];

        $batch = (new Batch\Core)->create($params, $merchant);

        return $batch;
    }

    protected function handleBatchCreationError(
        \Exception $ex,
        int $file,
        array $fileDetails,
        array & $allFilesDetails)
    {
        $this->messenger->raiseReconAlert(
            [
                'trace_code'   => TraceCode::RECON_BATCH_CREATION_FAILED,
                'message'      => 'Skipping file because not able to create batch for the file. -> ' .
                                    $ex->getMessage(),
                'file_details' => $fileDetails,
                'gateway'      => $this->gateway,
            ]);

        $this->handleFileSkip($file, $fileDetails, $allFilesDetails);
    }

    /**
     * Converts the data in file (excel/csv) and sets to in-memory array.
     *
     * @param $fileDetails
     * @throws Exception\ReconciliationException
     */
    protected function getFileContentInArrayAndSet($fileDetails)
    {
        $this->trace->info(TraceCode::RECON_BEGIN_FILE_PARSING, [
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
        $sheetNames = $this->gatewayReconciliator->getSheetNames();

        $startRow = $this->gatewayReconciliator->getStartRow($fileDetails);

        // this flag enables us to check if spout lib has been used
        $spoutLib = false;

        if ($fileDetails[FileProcessor::EXTENSION] === Format::XLSX)
        {
            $spoutLib = true;

            // getting contents using spout library for xlsx
            $sheetsContents = $this->converter->getRowsFromExcelSheetsSpout($fileDetails, $sheetNames);
        }
        else
        {
            $sheetsContents = $this->converter->getRowsFromExcelSheetsOptimized($fileDetails, $sheetNames, $startRow);
        }

        foreach ($sheetsContents as $sheetName => $rows)
        {
            if (empty($rows) === true)
            {
                // This would happen when the sheet name sent, does not exist
                continue;
            }

            $sheetArray = [];

            foreach ($rows as $cellCollection)
            {
                if ($spoutLib === true)
                {
                    $sheetArray[] = $cellCollection;
                }
                else
                {
                    $sheetArray[] = $cellCollection->all();
                }
            }

            $fileDetails[FileProcessor::SHEET_NAME] = $sheetName;

            $this->setExtraDetails($sheetArray, $fileDetails);

            $this->allFilesContents[] = $sheetArray;
        }
    }

    protected function handleSettingCsvContent($fileDetails)
    {
        $columnHeaders = $this->getColumnHeadersForGatewayIfApplicable($fileDetails);

        $linesToSkip = $this->gatewayReconciliator->getNumLinesToSkip($fileDetails);

        $delimiter = $this->gatewayReconciliator->getDelimiter();

        $csvArray = $this->converter->convertCsvToArray($fileDetails, $columnHeaders, $linesToSkip, $delimiter);

        $this->setExtraDetails($csvArray, $fileDetails);
        $this->allFilesContents[] = $csvArray;
    }

    protected function setExtraDetails(& $arrayContent, $fileDetails)
    {
        $arrayContent[self::EXTRA_DETAILS][FileProcessor::FILE_DETAILS] = $fileDetails;

        $arrayContent[self::EXTRA_DETAILS][self::INPUT_DETAILS] = $this->inputDetails;
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
