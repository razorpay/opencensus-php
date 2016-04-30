<?php

namespace Reconciliator;

use DirectoryIterator;

use EE\Exception;

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
        self::HDFC => ['prashanth.yv@razorpay.com'],
        self::Axis => ['prashanth@razorpay.com'],
    ];


    /*********************
     * Instance variables
     *********************/
    protected $gateway;
    protected $allFilesContents;
    protected $allFilesDetails;


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

        $gatewayReconciliatorClassName = 'Reconciliator' . '\\' . $this->gateway . '\\' . 'Reconciliate';
        $gatewayReconciliator = new $gatewayReconciliatorClassName;
        $gatewayReconciliator->startReconciliation($this->allFilesContents);
    }


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

        // TODO: Figure out a way to move this logic to 'Converter'.
        // Currently, the problem is with Excel files having multiple sheets.
        // Hence, we get
        // $allFilesContents = [[file1sheet1dataArray, file1sheet2_ataArray], file2csv_data_array, file3csv_data_array]
        // Use array_merge for solving this.

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


    protected function handleSettingExcelContent($fileDetails)
    {
        $sheets = $this->converter->getAllExcelSheets($fileDetails);
        // Every sheet is equivalent to a different file.
        foreach ($sheets as $sheet)
        {
            $sheetArray = $this->converter->convertExcelSheetToArray($sheet);
            $sheetArray[FileProcessor::FILE_DETAILS] = $fileDetails;
            $sheetArray[FileProcessor::FILE_DETAILS][FileProcessor::SHEET_NAME] = $sheet->getTitle();
            $this->allFilesContents[] = $sheetArray;
        }
    }


    protected function handleSettingCsvContent($fileDetails)
    {
        $csvArray = $this->converter->convertCsvToArray($fileDetails);
        $csvArray[FileProcessor::FILE_DETAILS] = $fileDetails;
        $this->allFilesContents[] = $csvArray;
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


    protected function manualEntry($input)
    {
        // TODO: Fill this up.
        return null;
    }


    protected function mailGunEntry($input)
    {
        $emailDetails = $this->getEmailDetails($input);
        $this->validator->filterEmails($emailDetails);

        // Sets the gateway for the orchestrator
        $this->gateway = $this->getGatewayFromEmailId($emailDetails);

        // Sets file details of all the attachments present in the email, for the orchestrator.
        $allFilesDetails = $this->getFileDetailsFromAllAttachments($emailDetails, $input);

        return $allFilesDetails;
    }


    protected function getFileDetailsFromAllAttachments($emailDetails, $input)
    {
        $allFilesDetails = [];

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


    protected function getGatewayFromEmailId($emailDetails)
    {
        $fromEmailId = $emailDetails['from'];

        $gateway = $this->getKeyFromSubArrayMatch($fromEmailId, self::GATEWAY_SENDER_MAPPING);

        if (empty($gateway) === true)
        {
            throw new Exception\ReconciliationException(
                'Email ID not present in Sender-Gateway mapping.',
                ['email_id' => $fromEmailId]
            );
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
            throw new Exception\ReconciliationException(
                'No attachments present in the email.'
            );
        }

        return $emailDetails;
    }
}