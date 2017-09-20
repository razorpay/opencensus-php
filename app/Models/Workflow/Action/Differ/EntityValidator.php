<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Admin;
use RZP\Constants\Entity;

class EntityValidator
{
    const ENTITY_NAME_KEY       = 'entity_name';
    const VALIDATOR_KEY         = 'validator';

    /**
     * Route to [] map to ensure a bunch of things like:
     *
     * - Automatic creation of Diff data.
     * - Automatic validation of incoming payload over the entity.
     *
     * The map can be index based or key based:
     *
     * 0 or entity_name for Entity Name.
     * 1 or validator for Validator.
     */
    const WORKFLOW_MAP = [
        'merchant_edit_email'   => [ Entity::MERCHANT, 'editEmail' ],

        'admin_edit'            => [ Entity::ADMIN, 'edit' ],

        'role_edit'             => [
            self::ENTITY_NAME_KEY   => Entity::ROLE,
            self::VALIDATOR_KEY     => 'edit'
        ],
        'dispute_edit'          => [
            self::ENTITY_NAME_KEY   => Entity::DISPUTE,
            self::VALIDATOR_KEY     => 'edit',
        ]
    ];

    const RELATIONS_WHITELIST = [
        'admin_edit',
        'role_edit',
    ];

    private static function indexBasedMap($map)
    {
        if (isset($map[0]))
        {
            return true;
        }

        return false;
    }

    public static function getEntityName($route)
    {
        $entityName = null;

        if (array_key_exists($route, self::WORKFLOW_MAP))
        {
            $map = self::WORKFLOW_MAP[$route];

            // Get 0th index or the ENTITY_NAME_KEY value

            if ((static::indexBasedMap($map)) and (empty($map[0]) === false))
            {
                $entityName = $map[0];
            }
            else if (empty($map[self::ENTITY_NAME_KEY]) === false)
            {
                $entityName = $map[self::ENTITY_NAME_KEY];
            }
        }

        return $entityName;
    }

    public static function getValidator($route)
    {
        $validator = null;

        if (array_key_exists($route, self::WORKFLOW_MAP))
        {
            $map = self::WORKFLOW_MAP[$route];

            // Get 1th index or the VALIDATOR_KEY value

            if ((static::indexBasedMap($map)) and (empty($map[0]) === false))
            {
                $validator = $map[1];
            }
            else if (empty($map[self::VALIDATOR_KEY]) === false)
            {
                $validator = $map[self::VALIDATOR_KEY];
            }
        }

        return $validator;
    }

    public static function getRelations($route)
    {
        $entityName = null;

        // If the route is in the relations whitelist then
        // only resolve the relations for which differ has
        // to be computed.

        if (in_array($route, self::RELATIONS_WHITELIST))
        {
            $entityName = self::getEntityName($route);
        }

        // Entity name has been resolved through which
        // we will resolve the Entity class of the entity.

        if (empty($entityName) === false)
        {
            $classNamespace = Entity::$namespace[$entityName];

            $entityClass = $classNamespace.'\Entity';

            // If entity class has been found then get an array
            // of relations on which the differ has to be generated

            $relations = (new $entityClass)->getRelationsForDiffer();

            return $relations;
        }

        return [];
    }
}
