<?php

namespace RZP\Models\Workflow\Action\Differ;

class EntityValidator
{
    const VALIDATOR = [
        'merchant_edit_email' => 'editEmail',
    ];

    public static function getValidator($route)
    {
        return self::VALIDATOR[$route];
    }
}
