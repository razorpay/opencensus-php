<?php

namespace Reconciliator;

class Orchestrator
{
    const EXCEL = 'excel';
    const CSV = 'csv';

    /******************
     * Bank constants
     ******************/

    const HDFC  = 'HDFC';
    const Axis  = 'Axis';
    const Kotak = 'Kotak';

    // This map should have all the extensions mentioned in Validator::ACCEPTED_EXTENSIONS_MAP
    const FILE_TYPES_MAPPINGS = [
        self::EXCEL => ['xls', 'xlsx'],
        self::CSV   => ['txt', 'csv', 'text']
    ];

    const GATEWAY_SENDER_MAPPING = [
        self::HDFC => ['prashanth@razorpay.com'],
        self::Axis => ['prashanth.yv@razorpay.com'],
    ];


    protected $validator;
    protected $fileProcessor;
    protected $deserializer;

    protected $gateway;

    public function __construct()
    {
        $this->validator = new Validator;
        $this->fileProcessor = new FileProcessor;
        $this->deserializer = new Deserializer;
    }

    public function baseEntry($input)
    {
        if ((isset($input['manual'])) and ($input['manual'] === true))
        {
            $gateway = $this->manualEntry($input);
        }
        else
        {
            $gateway = $this->mailGunEntry($input);
        }

        $this->orchestrate($input, $gateway);

        // Attachment names have numbers starting with 1 and not 0 -- MailGun.
        // Goes through each file and runs orchestrate on them.
        // foreach (range(1, $emailDetails['attachment_count']) as $attachmentNumber)
        // {
        //     // TODO: Handle zip files
        //     $file = $input['attachment-'.$attachmentNumber];
        //
        //     // Gets all the file details.
        //     $fileDetails = $this->fileProcessor->getFileDetails($file);
        //
        //     // Let the orchestration begin!
        //     $this->orchestrate($fileDetails);
        // }

        return 200;
    }

    protected function orchestrate($input, $gateway)
    {
        // $files = $this->getAllFilesFromInput();

        // Validates the file type, size, etc..
        // $this->validator->validateFile($fileDetails);
        //
        // foreach(self::FILE_TYPES_MAPPINGS as $key=>$value)
        // {
        //     if(in_array($fileDetails['extension'], $value) === true)
        //     {
        //         $fileDetails['file_type'] = $key;
        //         break;
        //     }
        //     else
        //     {
        //         // TODO: Throw an exception for an unsupported type.
        //     }
        // }
        //
        // $this->deserializer->deserialize($fileDetails);
        //
        // // Deletes the file.
        // $this->fileProcessor->deleteFileLocally($fileDetails);
    }

    protected function manualEntry(&$input)
    {
        return null;
    }

    protected function mailGunEntry(&$input)
    {
        $emailDetails = $this->getEmailDetails($input);
        $this->validator->filterEmails($emailDetails);

        // Sets the gateway for the orchestrator
        $this->gateway = $this->getGatewayFromEmailId($emailDetails);

        // Gets file details of all the attachments present in the email.
        $allFileDetails = $this->getFileDetailsFromAllAttachments($emailDetails, $input);
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
                $zippedFileDetails = $this->fileProcessor->getFileDetails($file);
                $unzippedFiles = $this->fileProcessor->unzipFile($zippedFileDetails, $this->gateway);

                foreach ($unzippedFiles as $unzippedFile)
                {
                    $fileDetails[] = $this->fileProcessor->getFileDetails($unzippedFile);
                }
            }
            else
            {
                $fileDetails[] = $this->fileProcessor->getFileDetails($file);
            }
        }

        return $fileDetails;
    }

    protected function getGatewayFromEmailId($emailDetails)
    {
        $fromEmailId = $emailDetails['from'];

        foreach (self::GATEWAY_SENDER_MAPPING as $gateway=>$senders)
        {
            if (in_array($fromEmailId, $senders))
            {
                return $gateway;
            }
        }

        // TODO: Throw exception for unknown email ID.
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