<?php

namespace RZP\Models\FileStore;

use RZP\Exception;

class Format
{
    const CSV   = 'csv';
    const PNG   = 'png';
    const JPG   = 'jpg';
    const JPEG  = 'jpeg';
    const PDF   = 'pdf';
    const XLS   = 'xls';
    const XLSX  = 'xlsx';
    const TXT   = 'txt';

    const SUPPORTED_EXTENSION_TYPES = [
        self::CSV,
        self::XLS,
        self::XLSX,
        self::TXT
    ];

    const VALID_LOCAL_EXTENSIONS = [
        self::TXT,
    ];

    /**
     * Validate if content given given is proper for filetype provided
     * @param string $content    Content of file
     * @param string $extension  Extension of file
     * @return boolean
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function validateContentTypeForExtension($content, $extension)
    {
        // TODO : Fix content checking

        if (in_array($extension, self::SUPPORTED_EXTENSION_TYPES) === true)
        {
            return true;
        }

        throw new Exception\BadRequestValidationFailureException(
            'Content type not valid for file extension specified.');
    }
}
