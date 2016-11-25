<?php

namespace RZP\Models\FileStore;

use RZP\Exception;

class Format
{
    const CSV   = 'csv';
    const JPG   = 'jpg';
    const JPEG  = 'jpeg';
    const PDF   = 'pdf';
    const PNG   = 'png';
    const TXT   = 'txt';
    const XLS   = 'xls';
    const XLSX  = 'xlsx';

    const EXCEL_COLUMN_TEXT = '@';

    const SUPPORTED_EXTENSION_TYPES = [
        self::CSV,
        self::TXT,
        self::XLS,
        self::XLSX,
    ];

    const VALID_EXTENSION_MIME_MAP = [
        self::CSV   => ['text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values', 'text/plain'],
        self::TXT   => ['text/plain'],
        self::XLSX  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        self::XLS   => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel', 'application/vnd.ms-office'],
    ];

    const VALID_LOCAL_EXTENSIONS = [
        self::CSV,
        self::TXT,
        self::XLSX,
    ];

    /**
     * Validate if content given given is proper for filetype provided
     *
     * @param string $content   Content of file
     * @param string $extension Extension of file
     *
     * @return boolean
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function validateContentTypeForExtension($content, $extension)
    {
        // TODO : Fix content checking
        if (in_array($extension, self::SUPPORTED_EXTENSION_TYPES) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Content type not valid for file extension specified.');
        }
    }

    public static function validateMimeForExtension($mime, $extension)
    {
        $allowedMime = Format::VALID_EXTENSION_MIME_MAP[$extension];

        if (in_array($mime, $allowedMime) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Mime for file extension specified.');
        }
    }
}
