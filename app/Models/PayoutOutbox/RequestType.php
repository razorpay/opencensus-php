<?php

namespace RZP\Models\PayoutOutbox;

use ReflectionClass;

class RequestType
{
    // For now request type can be payouts. This can be extended in the future based on the needs
    const PAYOUTS        = 'payouts';

    public static function exists(string $type): bool {
        $class      = new ReflectionClass(__CLASS__);

        $validTypes = $class->getConstants();

        return in_array($type, $validTypes, true);

    }
}
