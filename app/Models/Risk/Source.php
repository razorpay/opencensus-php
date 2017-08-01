<?php

namespace RZP\Models\Risk;

class Source
{
    // Data Sources
    const MAXMIND  = 'maxmind';
    const BANK     = 'bank';
    const GATEWAY  = 'gateway';
    const INTERNAL = 'internal';

    // internal rzp employees
    const MANUAL   = 'manual';

    public static function getAllSources(): array
    {
        return [
            self::MAXMIND,
            self::BANK,
            self::GATEWAY,
            self::INTERNAL,
            self::MANUAL,
        ];
    }
}
