<?php

namespace RZP\Models\Risk;

class Type
{
    // Fraud types
    const SUSPECTED = 'suspected';
    const CONFIRMED = 'confirmed';

    public static function getAllTypes(): array
    {
        return [
            self::SUSPECTED,
            self::CONFIRMED,
        ];
    }
}
