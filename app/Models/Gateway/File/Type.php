<?php

namespace RZP\Models\Gateway\File;

class Type
{
    const EMI      = 'emi';
    const CLAIM    = 'claim';
    const REFUND   = 'refund';
    const COMBINED = 'combined';

    public static function getValidTypes(): array
    {
        return [
            self::EMI,
            self::CLAIM,
            self::REFUND,
            self::COMBINED
        ];
    }

    public static function isValidType(string $type)
    {
        $validTypes = self::getValidTypes();

        return (in_array($type, $validTypes, true) === true);
    }
}
