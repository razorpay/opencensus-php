<?php

namespace RZP\Models\FileStore;

use RZP\Exception;

class Format
{
    const CSV   = 'csv';
    const ENC   = 'enc';
    const JPG   = 'jpg';
    const JPEG  = 'jpeg';
    const PDF   = 'pdf';
    const PNG   = 'png';
    const TXT   = 'txt';
    const XLS   = 'xls';
    const XLSX  = 'xlsx';
    const ZIP   = 'zip';

    const EXCEL_COLUMN_TEXT = '@';

    const SUPPORTED_EXTENSION_TYPES = [
        self::CSV,
        self::ENC,
        self::JPG,
        self::JPEG,
        self::PDF,
        self::PNG,
        self::TXT,
        self::XLS,
        self::XLSX,
        self::ZIP,
    ];

    const VALID_EXTENSION_MIME_MAP = [
        self::CSV   => ['text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values',
                        'text/plain', 'inode/x-empty', 'application/octet-stream'],
        self::ENC   => ['application/octet-stream'],
        self::JPG   => ['image/jpeg'],
        self::JPEG  => ['image/jpeg'],
        self::PDF   => ['application/pdf', 'application/x-pdf'],
        self::PNG   => ['image/png'],
        self::TXT   => ['text/plain'],
        self::XLSX  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/pgp'],
        self::XLS   => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel',
                        'application/vnd.ms-office'],
        self::ZIP   => ['application/zip'],
    ];

    const VALID_LOCAL_EXTENSIONS = [
        self::CSV,
        self::ENC,
        self::TXT,
        self::XLSX,
    ];

    /**
     * Validate if content given given is proper for filetype provided
     *
     * @param string $content   Content of file
     * @param string $extension Extension of file
     *
     * @return void
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function validateContentTypeForExtension($content, $extension)
    {
        // TODO : Fix content checking
        if (in_array($extension, self::SUPPORTED_EXTENSION_TYPES) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid Extension');
        }
    }

    /**
     * Validate if Mime is valid for provided Extension type
     *
     * @param string $mime      Mime
     * @param string $extension Extension
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function validateMimeForExtension($mime, $extension)
    {
        $allowedMime = Format::VALID_EXTENSION_MIME_MAP[$extension];

        if (in_array($mime, $allowedMime) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Mime for file extension specified.', null, ['mime' => $mime, 'extension' => $extension]);
        }
    }
}
