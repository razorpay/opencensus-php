<?php

namespace RZP\Models\Workflow\Action\Differ;

class EntityValidator
{
    const VALIDATOR = [
        'merchant_edit_email' => 'editEmail',
        'admin_edit'          => 'edit',
    ];

    const RELATIONS = [
        'admin_edit'          => \RZP\Models\Admin\Admin::class,
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
        if (array_key_exists($route, self::RELATIONS) === false)
        {
            return [];
        }

        $entityClass = self::RELATIONS[$route] . '\Entity';

        return (new $entityClass)->getRelationsForDiffer();
    }
}
