<?php

namespace RZP\Models\Batch\Processor;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser;

use RZP\Exception;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
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

    /**
     * Represents the reconciliator class for individual gateway
     *
     * @var \RZP\Reconciliator\Base\Reconciliate
     */
    protected $gatewayReconciliator;

    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);

        $this->converter = new Converter;

        $this->registerMimeTypeGuesser();
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
        $guesser = MimeTypeGuesser::getInstance();

        $guesser->register(new FileBinaryMimeTypeGuesser());
    }

    protected function saveInputFile(File $file): File
    {
        $this->trace->info(TraceCode::BATCH_UPLOADING_FILE, $this->batch->toArray());

        // We need the original file name with extension while moving the recon file
        // to local directory used by UFH
        $fileNameWithExt = strtolower($file->getFilename());

        // Here we get the original filename without the extension and prepend
        // the batch/upload prefix to indicate it is an input file for batch
        // We use the original filename here instead of the batch id as it s required
        // by the reconciliator classes to determine the type of reconciliation
        $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
        $fileName = Batch\Entity::INPUT_FILE_PREFIX . $fileName;

        $extension = strtolower($file->getExtension());
        $mimeType = strtolower(mime_content_type($file->getRealPath()));

        // we move the file to storage location used by UFH Accessor, so that S3
        // mock works successfully.
        $file = $file->move(
                    $this->batch->getLocalSaveDir(Batch\Entity::INPUT_FILE_PREFIX),
                    $fileNameWithExt);

        $ufh = new FileStore\Creator;

        $ufh->localFilePath($file->getPathname())
            ->mime($mimeType)
            ->name($fileName)
            ->extension($extension)
            ->entity($this->batch)
            ->type(FileStore\Type::RECONCILIATION_BATCH_INPUT)
            ->deleteLocalFile()
            ->save();

        $ufhFile = $ufh->getFileInstance();

        $this->batch->setUploadFileUrl($ufh->getUrl());

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $ufhFile->toArrayPublic());

        return $file;
    }

    protected function validateInputFileAndUpdateBatch(string $filePath, array $input)
    {
        //
        // Not doing anything here as in recon we don't need to validate / parse
        // entries at the time of saving the input file.
        //
        return;
    }

    protected function performPreProcessingActions()
    {
        parent::performPreProcessingActions();

        //
        // We need the gateway reconciliatoe object to get some gateway specific
        // details like sheet names etc which are required during parsing of the file
        //
        $this->setGatewayReconciliatorObject();
    }

    protected function setGatewayReconciliatorObject()
    {
        $gateway = $this->batch->getGateway();

        $gatewayReconciliatorClassName = 'RZP\\Reconciliator' . '\\' . $gateway . '\\' . 'Reconciliate';

        $this->gatewayReconciliator = new $gatewayReconciliatorClassName;
    }

    /**
     * We call the gateway's reconciliator class with the file entries obtained
     * by parsing the file
     *
     * @param   array       $entries
     */
    protected function processEntries(array & $entries)
    {
        $this->gatewayReconciliator->startReconciliationV2($entries, $this->batch);
    }

    protected function postProcessEntries(array & $entries)
    {
        //
        // Not doing anything here, as no special post processing steps need to
        // be taken for recon
        //
        return;
    }

    protected function createSetOutputFileAndSave(array & $entries)
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
     * @param  string   $filePath  Path of the file to be parsed
     *
     * @return array parsed contents of the recon file
     *
     * @throws Exception\ReconciliationException
     */
    protected function parseFile(string $filePath): array
    {
        $inputFileDetails = $this->getInputFileDetails($filePath);

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
                [self::FILE_DETAILS => $inputFileDetails]
            );
        }

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
        $sheetNames = $this->gatewayReconciliator->getSheetNames();

        $startRow = $this->gatewayReconciliator->getStartRow($inputFileDetails);

        $excelArray = $this->converter->convertExcelToArray($inputFileDetails, $sheetNames, $startRow);

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

        return [
            FileProcessor::FILE_NAME => strtolower($inputFile->getFilename()),
            FileProcessor::EXTENSION => strtolower($inputFile->getExtension()),
            FileProcessor::MIME_TYPE => $mimeType,
            FileProcessor::SIZE      => $inputFile->getSize(),
            FileProcessor::FILE_PATH => $filePath,
            FileProcessor::FILE_TYPE => $fileType,
        ];
    }

    protected function sendProcessedMail()
    {
        //
        // For reconciliation batch we don't need to send any mail,
        // hence not doing anything inside this function
        //
        return;
    }

    protected function increaseAllowedSystemLimits()
    {
        //
        // The reconciliation can run for a long time.
        // Hence, changing the script's execution time limit to 1 hour.
        //
        RuntimeManager::setTimeLimit(3600);

        //
        // In certain cases XLS parsing takes a long time. We are setting
        // the execution time to 60 min here to prevent the execution
        // from being terminated.
        //
        RuntimeManager::setMaxExecTime(3600);
    }
}
