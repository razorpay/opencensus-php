<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Admin;

class EntityValidator
{
    // Mapping specifying the validation rule name
    // against each route.
    const VALIDATOR = [
        'merchant_edit_email' => 'editEmail',
        'admin_edit'          => 'edit',
        'role_edit'           => 'edit',
    ];

    // Array maintaining the entity class against
    // each route in which relations has to be checked for
    // to include in differ.
    const RELATIONS = [
        'admin_edit'          => Admin\Admin\Entity::class,
        'role_edit'           => Admin\Role\Entity::class,
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

        $entityClass = self::RELATIONS[$route];

        $relations = (new $entityClass)->getRelationsForDiffer();

        return $relations;
    }
}
