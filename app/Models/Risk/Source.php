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

    public static function isValidSource(string $source): bool
    {
        $source = strtoupper($source);

        return (defined(__CLASS__ . '::' . $source) === true);
    }
}
