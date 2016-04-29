<?php

namespace Reconciliator;

use DirectoryIterator;

class Orchestrator
{
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
            $allFilesDetails = $this->manualEntry($input);
        }
        else
        {
            $allFilesDetails = $this->mailGunEntry($input);
        }

        if (empty($allFilesDetails) === true)
        {
            // TODO: Throw an exception about having no file details
        }

        $this->orchestrate($allFilesDetails);

        return 200;
    }

    protected function orchestrate($allFilesDetails)
    {
        // Run validations and conversions on each file
        foreach ($allFilesDetails as $file => $fileDetails)
        {
            // Validates the file type, size, etc..
            $this->validator->validateFile($fileDetails);

            // Converts to in-memory array
            // TODO: Might want to move this into Gateway implementation since
            // conversion to array might be different for different gateways.
            $this->getFileContentInArrayAndSet($fileDetails);

            // Deletes the file.
            $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
        }
    }

    protected function getFileContentInArrayAndSet($fileDetails)
    {
        $fileType = self::getKeyFromSubArrayMatch($fileDetails[FileProcessor::EXTENSION],
                                                    FileProcessor::FILE_TYPES_MAPPINGS);

        if (empty($fileType) === true)
        {
            // TODO: Throw an exception for an unsupported type.
        }

        // TODO: Figure out a way to move this logic to 'Converter'.
        // Currently, the problem is with Excel files having multiple sheets.
        // Hence, we get
        // $allFileContents = [[file1sheet1dataArray, file1sheet2_ataArray], file2csv_data_array, file3csv_data_array]
        if ($fileType === FileProcessor::EXCEL)
        {
            $sheets = $this->converter->getAllExcelSheets($fileDetails);
            foreach ($sheets as $sheet)
            {
                $this->allFilesContents[] = $this->converter->convertExcelSheetToArray($sheet);
            }
        }
        else if ($fileType === FileProcessor::CSV)
        {
            $this->allFilesContents[] = $this->converter->convertCsvToArray($fileDetails);
        }
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

    protected function manualEntry(&$input)
    {
        // TODO: Fill this up.
        return null;
    }

    protected function mailGunEntry(&$input)
    {
        $emailDetails = $this->getEmailDetails($input);
        $this->validator->filterEmails($emailDetails);

        // Sets the gateway for the orchestrator
        $this->gateway = $this->getGatewayFromEmailId($emailDetails);

        // Gets file details of all the attachments present in the email.
        $allFilesDetails = $this->getFileDetailsFromAllAttachments($emailDetails, $input);

        return $allFilesDetails;
    }

    protected function getFileDetailsFromAllAttachments($emailDetails, $input)
    {
        // Attachment names have numbers starting with 1 and not 0 -- MailGun Specific.
        // Goes through each file and gets the file details.
        foreach (range(1, $emailDetails['attachment_count']) as $attachmentNumber)
        {
            $file = $input['attachment-'.$attachmentNumber];

            $fileType = $this->fileProcessor->getTypeOfFile($file);

            // If it's a zip file, get all the details of all the files present in it.
            // Else, get the file details of the attachment.
            if (in_array($fileType, Validator::SUPPORTED_ZIP_EXTENSIONS))
            {
                $zippedFileDetails = $this->fileProcessor->getUploadedFileDetails($file);
                $unzippedFolderPath = $this->fileProcessor->unzipFile($zippedFileDetails, $this->gateway);
                foreach (new DirectoryIterator($unzippedFolderPath) as $unzippedFile)
                {
                    if($unzippedFile->isFile() === true)
                    {
                        $allFilesDetails[] = $this->fileProcessor->getStorageFileDetails($unzippedFile);
                    }
                }
            }
            else
            {
                $allFilesDetails[] = $this->fileProcessor->getUploadedFileDetails($file);
            }
        }

        return $allFilesDetails;
    }

    protected function getGatewayFromEmailId($emailDetails)
    {
        $fromEmailId = $emailDetails['from'];

        $gateway = $this->getKeyFromSubArrayMatch($fromEmailId, self::GATEWAY_SENDER_MAPPING);

        if (empty($gateway) === true)
        {
            // TODO: Throw exception for unknown email ID.
        }

        return $gateway;
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

        if ((isset($input['attachment-count']) === true) and ($input['attachment-count'] > 0))
        {
            $emailDetails['attachment_count'] = $input['attachment-count'];
        }
        else
        {
            // TODO: Throw exception for having no attachments
        }

        return $emailDetails;
    }
}