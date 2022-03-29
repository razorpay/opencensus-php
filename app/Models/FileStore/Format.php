<?php

namespace RZP\Models\FileStore;

use RZP\Exception;

class Format
{
    const CSV       = 'csv';
    const ENC       = 'enc';
    const JPG       = 'jpg';
    const JPEG      = 'jpeg';
    const PDF       = 'pdf';
    const PNG       = 'png';
    const TXT       = 'txt';
    const XLS       = 'xls';
    const XLSB      = 'xlsb';
    const XLSX      = 'xlsx';
    const ZIP       = 'zip';
    const DOC       = 'doc';
    const DOCX      = 'docx';
    const RPT       = 'rpt';
    const DAT       = 'dat';
    const XML       = 'xml';
    const CLT       = 'clt';
    const IN        = 'in';
    const NONE      = null;
    const SEVEN_Z   = '7z';
    const GPG       = 'gpg';
    const IOB       = 'iob';
    const VAL       = 'val';

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
        self::XML,
        self::IN,
        self::SEVEN_Z,
        self::GPG,
        self::IOB,
        self::VAL,
    ];

    const VALID_EXTENSION_MIME_MAP = [
        self::CSV     => ['text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values',
                        'text/plain', 'inode/x-empty', 'application/octet-stream', 'application/pgp',
                        'text/x-Algol68'],
        self::ENC     => ['application/octet-stream', 'application/pgp', 'application/zlib', 'application/x-object'],
        self::JPG     => ['image/jpeg', 'application/pgp'],
        self::JPEG    => ['image/jpeg', 'application/pgp'],
        self::PDF     => ['application/pdf', 'application/x-pdf', 'application/pgp'],
        self::PNG     => ['image/png', 'application/pgp'],
        self::TXT     => ['text/plain', 'application/pgp', 'application/octet-stream', 'audio/x-unknown', 'text/x-Algol68', 'text/x-algol68'],
        self::IN      => ['text/plain', 'application/pgp'],
        // Adding all possible type of mime type as current library we are using to create xlsx file will not take
        // care of mime
        self::XLSX    => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/pgp',
                        'application/octet-stream', 'text/plain', 'application/zlib', 'image/x-portable-pixmap',
                        'application/zip',],
        // `text/plain` is being added here because HDFC sends recon CSV files with XLS extension
        // `application/CDFV2-unknown` is being sent as mime_type for FirstData recon files
        self::XLS     => ['application/excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel', 'application/msexcel',
                        'application/vnd.ms-office', 'application/octet-stream', 'text/plain',
                        'application/cdfv2-unknown'],
        self::XLSB    => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel',
                        'application/vnd.ms-office', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/zip', 'application/octet-stream', 'application/vnd.oasis.opendocument.spreadsheet'],
        self::ZIP     => ['application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'],
        self::DOC     => ['application/msword'],
        self::DOCX    => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        self::XML     => ['application/xml'],
        self::RPT     => ['text/plain'],
        self::DAT     => ['text/plain', 'application/octet-stream'],
        self::NONE    => ['text/plain'],
        self::SEVEN_Z => ['application/x-7z-compressed'],
        self::GPG     => ['application/pgp', 'application/octet-stream'],
        self::IOB     => ['text/plain'],
        self::VAL     => ['text/plain'],
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
        if (($extension !== Format::NONE) and
            (in_array($extension, self::SUPPORTED_EXTENSION_TYPES, true) === false))
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
