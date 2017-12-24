<?php

namespace RZP\Models\State\Reason;

class ReasonType
{
    // Enum for Reason Type
    const REJECTION = 'rejection';

    // Allowed Reason Types
    const ALLOWED_REASON_TYPES = [
        self::REJECTION,
    ];
}
