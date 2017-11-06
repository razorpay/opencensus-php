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
    const XLSB  = 'xlsb';
    const XLSX  = 'xlsx';
    const ZIP   = 'zip';
    const DOC   = 'doc';
    const DOCX  = 'docx';
    const RPT   = 'rpt';
    const DAT   = 'dat';

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
        self::XLSB,
        self::XLSX,
        self::ZIP,
        self::DOC,
        self::DOCX,
        self::RPT,
        self::DAT,
    ];

    const VALID_EXTENSION_MIME_MAP = [
        self::CSV   => ['text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values',
                        'text/plain', 'inode/x-empty', 'application/octet-stream', 'application/pgp'],
        self::ENC   => ['application/octet-stream', 'application/pgp'],
        self::JPG   => ['image/jpeg', 'application/pgp'],
        self::JPEG  => ['image/jpeg', 'application/pgp'],
        self::PDF   => ['application/pdf', 'application/x-pdf', 'application/pgp'],
        self::PNG   => ['image/png', 'application/pgp'],
        self::TXT   => ['text/plain', 'application/pgp'],
        self::XLSX  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/pgp',
                        'application/octet-stream', 'text/plain'],
        self::XLS   => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel',
                        'application/vnd.ms-office', 'application/octet-stream', 'text/plain',
                        'application/cdfv2-unknown'],
        self::XLSB  => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel',
                        'application/vnd.ms-office', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/zip', 'application/octet-stream', 'application/vnd.oasis.opendocument.spreadsheet'],
        self::ZIP   => ['application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'],
        self::DOC   => ['application/msword'],
        self::DOCX  => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        self::RPT   => ['text/plain'],
        self::DAT   => ['text/plain'],
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
