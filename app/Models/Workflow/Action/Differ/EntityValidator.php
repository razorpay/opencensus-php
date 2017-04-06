<?php

namespace RZP\Models\Workflow\Action\Differ;

class EntityValidator
{
    const VALIDATOR = [
        'merchant_edit_email' => 'editEmail',
        'admin_edit'          => 'edit',
    ];

    public static function getValidator($route)
    {
        $validator = null;

        if (array_key_exists($route, self::VALIDATOR))
        {
            $validator = self::VALIDATOR[$route];
        }

        return $validator;
    }
}
