<?php

namespace RZP\Models\Merchant\Detail;

class ClarificationMode
{
    // Enum values used for Clarification Mode
    const EMAIL = 'email';
    const CALL  = 'call';

    /*
     * Allowed clarification modes
     */
    const ALLOWED_CLARIFICATION_MODES = [
        self::EMAIL,
        self::CALL,
    ];
}
