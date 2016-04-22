<?php

namespace Reconciliator;

class Orchestrator
{
    const EXCEL = 'excel';
    const CSV = 'csv';
    
    // This map should have all the extensions mentioned in Validator::ACCEPTED_EXTENSIONS_MAP
    const FILE_TYPES_MAPPINGS = [
            self::EXCEL => ['xls', 'xlsx'],
            self::CSV   => ['txt', 'csv', 'text']
    ];


    protected $validator;
    protected $fileProcessor;
    protected $deserializer;

    public function __construct()
    {
        $this->validator = new Validator;
        $this->fileProcessor = new FileProcessor;
        $this->deserializer = new Deserializer;
    }

    public function start($input)
    {
        $emailDetails = $this->getEmailDetails($input);

        $this->validator->filterEmails($emailDetails);

        // Attachment names have numbers starting with 1 and not 0 -- MailGun.
        // Goes through each file and runs orchestrate on them.
        foreach (range(1, $emailDetails['attachment_count']) as $attachmentNumber)
        {
            // TODO: Handle zip files
            $file = $input['attachment-'.$attachmentNumber];

            // Gets all the file details.
            $fileDetails = $this->fileProcessor->getFileDetails($file);

            // Let the orchestration begin!
            $this->orchestrate($fileDetails);
        }

        return 200;
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
    
    protected function orchestrate($fileDetails)
    {
        // Validates the file type, size, etc..
        $this->validator->validateFile($fileDetails);

        foreach(self::FILE_TYPES_MAPPINGS as $key=>$value)
        {
            if(in_array($fileDetails['extension'], $value) === true)
            {
                $fileDetails['file_type'] = $key;
                break;
            }
            else
            {
                // TODO: Throw an exception for an unsupported type.
            }
        }

        $this->deserializer->deserialize($fileDetails);

        // Deletes the file.
        $this->fileProcessor->deleteFileLocally($fileDetails);
    }
}