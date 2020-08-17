<?php

namespace RZP\Models\Merchant\BvsValidation;

class Constants
{
    const PG      = 'pg';
    const CAPITAL = 'capital';

    const SUCCESS  = 'success';
    const FAILED   = 'failed';
    const CAPTURED = 'captured';

    const PLATFORMS = [
        self::PG,
        self::CAPITAL,
    ];

    const STATUS = [
        self::SUCCESS,
        self::FAILED,
        self::CAPTURED,
    ];
}
