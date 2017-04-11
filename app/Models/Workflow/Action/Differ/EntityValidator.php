<?php

namespace RZP\Models\Workflow\Action\Differ;

class EntityValidator
{
    const VALIDATOR = [
        'merchant_edit_email' => 'editEmail',
        'admin_edit'          => 'edit',
    ];

    const RELATIONS = [
        'admin_edit'          => ['roles', 'groups'],
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

    public static function getRelations($route)
    {
        $relations = null;

        if (array_key_exists($route, self::RELATIONS))
        {
            $relations = self::RELATIONS[$route];
        }

        return $relations;
    }
}
