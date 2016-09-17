<?php

namespace RZP\Reconciliator;

use RZP\Exception;

class Validator
{
    const ACCEPTED_EXTENSIONS_MAP = [
        'csv'   => ['text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values', 'text/plain'],
        'txt'   => ['text/plain'],
        // Don't know why but, getting application/zip and application/octet-stream as mimetype for xlsx files.
        'xlsx'  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/zip', 'application/octet-stream'],
        'xls'   => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel', 'application/vnd.ms-office'],
        'zip'   => ['application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'],
    ];

    // Add here too when being added in Validator::ACCEPTED_EXTENSIONS_MAP
    const SUPPORTED_ZIP_EXTENSIONS = ['zip'];

    // Max allowed file size - 20M (20*1024*1024).
    const MAX_FILE_SIZE = 20971520;

    public function filterEmails($emailDetails)
    {
        $from = $emailDetails['from'];
        $validEmailIds = Orchestrator::GATEWAY_SENDER_MAPPING;

        if (Orchestrator::getKeyFromSubArrayMatch($from, $validEmailIds) === null)
        {
            throw new Exception\ReconciliationException(
                'The sender email ID is not whitelisted.', ['email_details' => $emailDetails]
            );
        }
    }

    public function validateAttachments(& $input)
    {
        // Gets all the attachments found in the input by checking the number of
        // input keys starting with 'attachment-'.
        // Excludes 'attachment-count'.
        $foundAttachments = array_filter(
            $input,
            function($key)
            {
                return (strpos($key, 'attachment-') === 0) and
                       (strpos($key, 'attachment-count') === false);
            },
            ARRAY_FILTER_USE_KEY
        );

        $foundAttachmentsCount = count($foundAttachments);

        // There should be at least 1 attachment present.
        if ($foundAttachmentsCount === 0)
        {
            throw new Exception\ReconciliationException(
                'No attachments found in the input.'
            );
        }

        // Sets 'attachment-count' if not present and returns.
        // If present, converts it to int.
        if (isset($input['attachment-count']) === false)
        {
            $input['attachment-count'] = $foundAttachmentsCount;
        }
        else
        {
            $input['attachment-count'] = intval($input['attachment-count']);

            // The input's attachment-count and found attachments count should be equal.
            if ($input['attachment-count'] !== $foundAttachmentsCount)
            {
                throw new Exception\ReconciliationException(
                    'The number of attachments found, does not match with the attachment-count input',
                    ['attachments_found' => $foundAttachmentsCount, 'attachment_count' => $input['attachment-count']]
                );
            }
        }
    }

    /**
     * Validates if the file size is within the limits and
     * validates if extension and mime type combination is as expected.
     *
     * @param $fileDetails
     * @return bool true if validation in successful, otherwise, false.
     */
    public function validateFile(array $fileDetails)
    {
        // Extensions are in uppercase sometimes.
        $extension = strtolower($fileDetails['extension']);
        $mimeType = strtolower($fileDetails['mime_type']);
        $fileSize = $fileDetails['size'];

        if (($this->validateExtensionMimeType($extension, $mimeType) === true) and
            ($this->validateFileSize($fileSize) === true))
        {
            return true;
        }

        return false;
    }

    public function validateExtensionMimeType($extension, $mimeType)
    {
        $acceptedExtensionsMap = self::ACCEPTED_EXTENSIONS_MAP;

        if ((isset($acceptedExtensionsMap[$extension]) === false) or
            (in_array($mimeType, $acceptedExtensionsMap[$extension]) === false))
        {
            return false;
        }

        return true;
    }

    public function validateFileSize($fileSize)
    {
        if ($fileSize > self::MAX_FILE_SIZE)
        {
            return false;
        }

        return true;
    }
}
