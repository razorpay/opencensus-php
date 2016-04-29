<?php
/**
 * Created by PhpStorm.
 * User: prashanth.yv
 * Date: 21/04/16
 * Time: 4:04 PM
 */

namespace Reconciliator;

use EE\Exception;

class Validator
{
    /*
     * TODO: Add all email IDs which we are expecting the mails to come from for settlements.
     * This list should be the same as the one configured in MailGun.
     * It's being added here to ensure more robustness.
     */
    const FROM_EMAILS_FILTER = ['prashanth.yv@razorpay.com'];
    const SUBJECT_FILTER = [];

    // Can add more to this as and when we add converters to CSV from different file types.
    const ACCEPTED_EXTENSIONS_MAP = [
        // TODO: Might need to add more mime types for the extensions.
        'csv'   => ['text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values'],
        'txt'   => ['text/plain'],
        'text'  => ['text/plain'],
        'xlsx'  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'xls'   => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel'],
        'zip'   => ['application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'],
    ];

    // Add here too when being added in Validator::ACCEPTED_EXTENSIONS_MAP
    const SUPPORTED_ZIP_EXTENSIONS = ['zip'];

    // Max allowed file size - 2M.
    const MAX_FILE_SIZE = 2*1024*1024;

    public function filterEmails($emailDetails)
    {
        if (in_array($emailDetails['from'], self::FROM_EMAILS_FILTER) === false)
        {
            // TODO: Throw exception for an invalid "from".
        }

        // TODO: Filter against subject?
    }

    public function validateFile($fileDetails)
    {
        $extension = $fileDetails['extension'];
        $mimeType = $fileDetails['mime_type'];
        $fileSize = $fileDetails['size'];

        $this->validateExtensionMimeType($extension, $mimeType);

        $this->validateFileSize($fileSize);

        // TODO: CSV and text file validations will be done directly while reading.
    }

    public function validateExtensionMimeType($extension, $mimeType)
    {
        $acceptedExtensionsMap = self::ACCEPTED_EXTENSIONS_MAP;

        if ((isset($acceptedExtensionsMap[$extension]) === false) or
            (in_array($mimeType, $acceptedExtensionsMap[$extension]) === false))
        {
            // TODO: Throw an exception for bad extension/mime-type.
        }
    }

    protected function validateFileSize($fileSize)
    {
        if ($fileSize > self::MAX_FILE_SIZE)
        {
            // TODO: Throw an exception for exceeding file size limitation.
        }
    }
}