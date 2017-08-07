<?php

namespace RZP\Models\Risk;

class Type
{
    // Fraud types
    const SUSPECTED = 'suspected';
    const CONFIRMED = 'confirmed';

    public static function isValidType(string $type): bool
    {
        $type = strtoupper($type);

        return (defined(__CLASS__ . '::' . $type) === true);
    }
}
