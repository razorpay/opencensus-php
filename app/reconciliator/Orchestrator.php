<?php

namespace Reconciliator;

use DirectoryIterator;

use EE\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Orchestrator
{
    const GATEWAY = 'gateway';
    const EXTRA_DETAILS = 'extra_details';
    const EMAIL_DETAILS = 'email_details';
    const ATTACHMENT_COUNT = 'attachment_count';

    /******************
     * Bank constants
     ******************/
    const HDFC  = 'HDFC';
    const Axis  = 'Axis';
    const Kotak = 'Kotak';


    /********************
     * Complex constants
     ********************/
    // The gateway names should be the same name as the directories present under 'reconciliator'
    const GATEWAY_SENDER_MAPPING = [
        self::HDFC => ['prashanth@razorpay.com'],
        self::Axis => ['prashanth.yv@razorpay.com'],
    ];


    /*********************
     * Instance variables
     *********************/
    protected $allFilesContents;
    protected $allFilesDetails;
    protected $emailDetails;


    /********************
     * Instance objects
     ********************/
    protected $validator;
    protected $fileProcessor;
    protected $converter;
    protected $gatewayReconciliator;


    public function __construct()
    {
        $this->validator = new Validator;
        $this->fileProcessor = new FileProcessor;
        $this->converter = new Converter;
    }


    public function baseEntry($input)
    {
        // Checks if it's manual call or mailgun call
        if ((isset($input['manual']) === true) and ($input['manual'] === true))
        {
            // Sets the gateway reconciliator object and
            // Gets all the file details from the input.
            $this->allFilesDetails = $this->manualEntry($input);
        }
        else
        {
            // Sets the gateway reconciliator object and
            // Gets all the file details from the input.
            $this->allFilesDetails = $this->mailGunEntry($input);
        }

        // There must be at least one file. Otherwise, error.
        if (empty($this->allFilesDetails) === true)
        {
            throw new Exception\ReconciliationException(
                'File details are empty.', ['all_files_details' => $this->allFilesDetails]
            );
        }

        // Starts the orchestration.
        $this->orchestrate();

        return 200;
    }


    protected function manualEntry($input)
    {
        // Validates the input received.
        // All the attachment files names should start with 'attachment-'
        // Also, adds attachment-count to input, if not present already.
        $this->validator->validateAttachments($input);

        // Gets the input details.
        $inputDetails = $this->getManualInputDetails($input);

        // Figures out the gateway and
        // sets the gateway reconciliator object for the orchestrator,
        // using the input details.
        $this->setGatewayForManual($inputDetails);

        // Gets all the file details.
        $allFilesDetails = $this->getFileDetailsFromInput($inputDetails, $input);

        return $allFilesDetails;
    }


    protected function mailGunEntry($input)
    {
        // Gets the email details and validates the email details.
        $this->emailDetails = $this->getEmailDetails($input);
        $this->validator->filterEmails($this->emailDetails);
        
        // Figures out the gateway and sets the gateway reconciliator object for the orchestrator,
        // using the input details.
        $this->setGatewayFromEmailId();

        // Gets all the file details.
        $allFilesDetails = $this->getFileDetailsFromInput($this->emailDetails, $input);

        return $allFilesDetails;
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
    protected function orchestrate()
    {
        // Run validations and conversions on each file
        foreach ($this->allFilesDetails as $file => $fileDetails)
        {
            // Checks if this particular file needs to be excluded for the gateway
            $inExclude = $this->gatewayReconciliator->inExcludeList($fileDetails);

            if ($inExclude === true)
            {
                // TODO: Raise an alert about skipping the file because it's in the exclude list of gateway.

                $this->handleInvalidFile($file, $fileDetails);

                // Don't get the content of the file.
                continue;
            }

            // Validates the file type, size, etc..
            $validate = $this->validator->validateFile($fileDetails);

            if ($validate === false)
            {
                // TODO: Raise an alert about skipping the file because validation failed.

                $this->handleInvalidFile($file, $fileDetails);

                // Don't get the content of the file.
                continue;
            }

            try
            {
                // Converts to in-memory array and stores it in instance variable.
                // TODO: Might want to move this into Gateway implementation since
                // conversion to array might be different for different gateways.
                $this->getFileContentInArrayAndSet($fileDetails);
            }
            catch (\Exception $ex)
            {
                // TODO: Raise an alert about skipping the file for not being able to convert file content to array.

                $this->handleInvalidFile($file, $fileDetails);

                // Don't get the content of the file.
                continue;
            }

            // Delete the file. We have all the data in $allFilesContents.
            $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
        }

        $this->gatewayReconciliator->startReconciliation($this->allFilesContents);
    }


    protected function handleInvalidFile($file, $fileDetails)
    {
        // Delete it locally.
        $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);

        // Remove the file from allFiles variable.
        unset($this->allFilesDetails[$file]);
    }


    protected function getManualInputDetails($input)
    {
        $inputDetails = [
            self::ATTACHMENT_COUNT => $input['attachment-count'],
            self::GATEWAY => $input['gateway'],
        ];

        return $inputDetails;
    }


    protected function getEmailDetails($input)
    {
        $emailDetails = [
            'from' => $input['sender'],
            'subject' => $input['subject'],
            'to' => $input['recipient'],
            'timestamp' => $input['timestamp'],
            'body' => $input['stripped-text'],
        ];

        // Validates that attachments are present in the email.
        $this->validator->validateAttachments($input);

        $emailDetails[self::ATTACHMENT_COUNT] = $input['attachment-count'];

        return $emailDetails;
    }


    protected function setGatewayForManual($inputDetails)
    {
        // In manual, the input params should contain what gateway is it.
        $gateway = $inputDetails[self::GATEWAY];

        // This is a validation for the value of the gateway input received.
        if (array_key_exists($gateway, self::GATEWAY_SENDER_MAPPING) === false)
        {
            throw new Exception\ReconciliationException(
                'Invalid gateway param. It should be either HDFC/Axis/Kotak. (case sensitive)',
                ['gateway' => $gateway]
            );
        }

        // Sets the gateway reconciliator object for the orchestrator.
        $this->setGatewayReconciliatorClass($gateway);
    }


    protected function setGatewayFromEmailId()
    {
        $fromEmailId = $this->emailDetails['from'];

        // For a particular gateway, reconciliation files can be sent from more than one email ID.
        $gateway = $this->getKeyFromSubArrayMatch($fromEmailId, self::GATEWAY_SENDER_MAPPING);

        if (empty($gateway) === true)
        {
            throw new Exception\ReconciliationException(
                'Email ID not present in Sender-Gateway mapping.',
                ['email_id' => $fromEmailId]
            );
        }

        $this->setGatewayReconciliatorClass($gateway);
    }


    protected function getFileDetailsFromInput($inputDetails, $input)
    {
        $allFilesDetails = [];

        // Goes through each file and gets the file details.
        foreach (range(1, $inputDetails[self::ATTACHMENT_COUNT]) as $attachmentNumber)
        {
            // All the attachment files have to be named as 'attachment-{number}'
            // Validations should take care of this.
            $file = $input['attachment-'.$attachmentNumber];

            // This step is mainly to figure out whether the file is of zip type.
            $fileType = $this->fileProcessor->getTypeOfFile($file);

            // If it's a zip file, get all the details of all the files present in it.
            // Else, get the file details of the attachment.
            if (in_array($fileType, Validator::SUPPORTED_ZIP_EXTENSIONS))
            {
                try
                {    // Gets all files details present in the zip file.
                    $extractedFileDetails = $this->getFileDetailsFromZipAttachment($file);

                    // Throw an error if there's not even one file in the zip. Ideally, shouldn't happen.
                    if (empty($extractedFileDetails) === true)
                    {
                        throw new Exception\ReconciliationException(
                            'No files present in the zip file attachment.',
                            ['file_name' => $file->getClientOriginalName()]
                        );
                    }

                    // Using array merge since $extractedFileDetails contains an
                    // array of file details of different files in the zip file.
                    array_merge($allFilesDetails, $extractedFileDetails);
                }
                catch (\Exception $ex)
                {
                    // TODO: Raise an alert about skipping a file because of zip exception
                    continue;
                }
            }
            else
            {
                // Except zip, all other file types will return with a single element
                // and not an array. Hence using push here instead of merge.
                $allFilesDetails[] = $this->fileProcessor->getFileDetails($file, FileProcessor::UPLOADED);
            }
        }

        return $allFilesDetails;
    }


    /**
     * Converts the data in file (excel/csv) and sets to in-memory array.
     *
     * @param $fileDetails
     * @throws Exception\ReconciliationException
     */
    protected function getFileContentInArrayAndSet($fileDetails)
    {
        // All file types are segrated into either CSV or Excel.
        $fileType = self::getKeyFromSubArrayMatch($fileDetails[FileProcessor::EXTENSION],
                                                    FileProcessor::FILE_TYPES_MAPPINGS);

        $fileDetails[FileProcessor::FILE_TYPE] = $fileType;

        if (empty($fileType) === true)
        {
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
    }


    protected function setGatewayReconciliatorClass($gateway)
    {
        $gatewayReconciliatorClassName = 'Reconciliator' . '\\' . $gateway . '\\' . 'Reconciliate';
        $this->gatewayReconciliator = new $gatewayReconciliatorClassName;
    }


    /**
     * Converts and sets the excel content in an array.
     *
     * @param $fileDetails
     */
    protected function handleSettingExcelContent($fileDetails)
    {
        // Gets the sheet names which need to be collected for the given gateway.
        // Returns empty if there is no restriction on which sheets to collect.
        $sheetNames = $this->gatewayReconciliator->getSheetNames();

        $sheets = $this->converter->getAllExcelSheets($fileDetails, $sheetNames);

        // Every sheet is equivalent to a different file.
        foreach ($sheets as $sheet)
        {
            $sheetArray = $this->converter->convertExcelSheetToArray($sheet);
            $fileDetails[FileProcessor::SHEET_NAME] = $sheet->getTitle();

            $this->setExtraDetails($sheetArray, $fileDetails);
            $this->allFilesContents[] = $sheetArray;
        }
    }


    protected function handleSettingCsvContent($fileDetails)
    {
        $csvArray = $this->converter->convertCsvToArray($fileDetails);

        $this->setExtraDetails($csvArray, $fileDetails);

        $this->allFilesContents[] = $csvArray;
    }


    protected function setExtraDetails(&$arrayContent, $fileDetails)
    {
        $arrayContent[self::EXTRA_DETAILS][FileProcessor::FILE_DETAILS] = $fileDetails;
        //$arrayContent[self::EXTRA_DETAILS][self::EMAIL_DETAILS] = $this->emailDetails;
    }


    /**
     * Unzips the zip file. Iterates through each extracted file and collects
     * the file details.
     *
     * @param UploadedFile $file Zip file that needs to be extracted.
     * @return array File details of all the files present in the zip file.
     * @throws Exception\ReconciliationException
     */
    protected function getFileDetailsFromZipAttachment($file)
    {
        $allExtractedFilesDetails = [];

        // Gets the actual zip file's details first.
        $zippedFileDetails = $this->fileProcessor->getFileDetails($file, FileProcessor::UPLOADED);

        // unzipFile unzips the file and stores it in a location.
        $unzippedFolderPath = $this->fileProcessor->unzipFile($zippedFileDetails);

        // Iterates through each zip file and gets the file details for them.
        foreach (new DirectoryIterator($unzippedFolderPath) as $unzippedFile)
        {
            if($unzippedFile->isFile() === true)
            {
                $allExtractedFilesDetails[] = $this->fileProcessor->getFileDetails($unzippedFile, FileProcessor::STORAGE);
            }
        }

        return $allExtractedFilesDetails;
    }


    /**
     * @param $needle
     * @param array $haystack An associative array with array values.
     *                        ['a' => ['b', 'c'], 'd' => ['e', 'f']]
     * @return int|string|null
     */
    public static function getKeyFromSubArrayMatch($needle, $haystack)
    {
        foreach($haystack as $key => $subArray)
        {
            if (in_array($needle, $subArray) === true)
            {
                return $key;
            }
        }
        return null;
    }
}