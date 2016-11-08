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

    const SUPPORTED_CONTENT_TYPES = [
        self::CSV,
        self::XLS,
        self::XLSX,
        self::TXT
    ];

    public static function validateContentTypeForFormat($content, $format)
    {
        // TODO : Fix content checking

        if (in_array($format, self::SUPPORTED_CONTENT_TYPES) === true)
        {
            return true;
        }

        throw new Exception\BadRequestValidationFailureException(
            'Content type not valid for file format specified.');
    }
}
