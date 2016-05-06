<?php

namespace Reconciliator;

use DirectoryIterator;

use EE\Exception;

class Orchestrator
{
    const GATEWAY = 'gateway';
    const EXTRA_DETAILS = 'extra_details';
    const EMAIL_DETAILS = 'email_details';

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
    protected $gateway;
    protected $allFilesContents;
    protected $allFilesDetails;
    protected $emailDetails;
    protected $gatewayReconciliator;


    /********************
     * Instance objects
     ********************/
    protected $validator;
    protected $fileProcessor;
    protected $converter;


    public function __construct()
    {
        $this->validator = new Validator;
        $this->fileProcessor = new FileProcessor;
        $this->converter = new Converter;
    }


    public function baseEntry($input)
    {
        if ((isset($input['manual'])) and ($input['manual'] === true))
        {
            $this->allFilesDetails = $this->manualEntry($input);
        }
        else
        {
            $this->allFilesDetails = $this->mailGunEntry($input);
        }

        if (empty($this->allFilesDetails) === true)
        {
            throw new Exception\ReconciliationException(
                'File details are empty.', ['all_files_details' => $this->allFilesDetails]
            );
        }

        $this->orchestrate();

        return 200;
    }


    protected function manualEntry($input)
    {
        $this->validator->validateManualInput($input);

        $inputDetails = $this->getManualInputDetails($input);

        // Sets the gateway for the orchestrator
        $this->setGatewayForManual($inputDetails);

        $allFilesDetails = $this->getFileDetailsFromInput($inputDetails, $input);

        return $allFilesDetails;
    }


    protected function mailGunEntry($input)
    {
        $this->emailDetails = $this->getEmailDetails($input);
        $this->validator->filterEmails($this->emailDetails);

        // Sets the gateway for the orchestrator
        $this->setGatewayFromEmailId();

        // Sets file details of all the attachments present in the email, for the orchestrator.
        $allFilesDetails = $this->getFileDetailsFromInput($this->emailDetails, $input);

        return $allFilesDetails;
    }


    protected function orchestrate()
    {
        // Run validations and conversions on each file
        foreach ($this->allFilesDetails as $file => $fileDetails)
        {
            // Validates the file type, size, etc..
            $this->validator->validateFile($fileDetails);

            // Converts to in-memory array
            // TODO: Might want to move this into Gateway implementation since
            // conversion to array might be different for different gateways.
            $this->getFileContentInArrayAndSet($fileDetails);

            // Delete the file. We have all the data in $allFilesContents.
            $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
        }

        $this->gatewayReconciliator->startReconciliation($this->allFilesContents);
    }


    protected function getManualInputDetails($input)
    {
        // TODO: Fill this up. (Get the number of attachments also in the input details.)
        return null;
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

        $this->validator->validateEmailAttachments($input);

        $emailDetails['attachment_count'] = $input['attachment-count'];

        return $emailDetails;
    }


    protected function setGatewayForManual($inputDetails)
    {
        $gateway = $inputDetails[self::GATEWAY];

        if (array_key_exists($gateway, self::GATEWAY_SENDER_MAPPING) === false)
        {
            throw new Exception\ReconciliationException(
                'Invalid gateway param. It should be either HDFC/Axis/Kotak. (case sensitive)',
                ['gateway' => $gateway]
            );
        }

        $this->setGatewayReconciliatorClass($gateway);
    }


    protected function setGatewayFromEmailId()
    {
        $fromEmailId = $this->emailDetails['from'];

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
        foreach (range(1, $inputDetails['attachment_count']) as $attachmentNumber)
        {
            // Attachment files in MainGun are named as 'attachment-[1..n]'.
            $file = $input['attachment-'.$attachmentNumber];

            $fileType = $this->fileProcessor->getTypeOfFile($file);

            // If it's a zip file, get all the details of all the files present in it.
            // Else, get the file details of the attachment.
            if (in_array($fileType, Validator::SUPPORTED_ZIP_EXTENSIONS))
            {
                $extractedFileDetails = $this->getFileDetailsFromZipAttachment($file);

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
            else
            {
                // Except zip, all other file types will return with a single element
                // and not an array. Hence using push here instead of merge.
                $allFilesDetails[] = $this->fileProcessor->getUploadedFileDetails($file);
            }
        }

        return $allFilesDetails;
    }


    // Converts the data in file and sets to in-memory array.
    protected function getFileContentInArrayAndSet($fileDetails)
    {
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
        $this->gateway = $gateway;

        $gatewayReconciliatorClassName = 'Reconciliator' . '\\' . $gateway . '\\' . 'Reconciliate';
        $this->gatewayReconciliator = new $gatewayReconciliatorClassName;
    }

    protected function handleSettingExcelContent($fileDetails)
    {

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
        $arrayContent[self::EXTRA_DETAILS][self::EMAIL_DETAILS] = $this->emailDetails;
    }


    protected function getFileDetailsFromZipAttachment($file)
    {
        $allExtractedFilesDetails = [];

        $zippedFileDetails = $this->fileProcessor->getUploadedFileDetails($file);
        $unzippedFolderPath = $this->fileProcessor->unzipFile($zippedFileDetails, $this->gateway);

        foreach (new DirectoryIterator($unzippedFolderPath) as $unzippedFile)
        {
            if($unzippedFile->isFile() === true)
            {
                $allExtractedFilesDetails[] = $this->fileProcessor->getStorageFileDetails($unzippedFile);
            }
        }

        return $allExtractedFilesDetails;
    }


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