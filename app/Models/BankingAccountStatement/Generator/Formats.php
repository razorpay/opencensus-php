<?php

namespace RZP\Models\BankingAccountStatement\Generator;

class Formats
{
    const PDF               = 'pdf';
    const CSV               = 'csv';
    const XLSX              = 'xlsx';
    const SUPPORTED_FORMATS = [self::PDF, self::CSV, self::XLSX];

    public static function isFormatSupported($format)
    {
        return in_array($format, self::SUPPORTED_FORMATS, true);
    }
}
