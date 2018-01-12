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
     */
    const WORKFLOW_MAP = [
        // Middleware

        'merchant_edit_email'   => [
            self::ENTITY_NAME_KEY   => Entity::MERCHANT,
            self::VALIDATOR_KEY     => 'editEmail'
        ],

        'admin_edit'            => [
            self::ENTITY_NAME_KEY   => Entity::ADMIN,
            self::VALIDATOR_KEY     => 'edit'
        ],

        'role_edit'             => [
            self::ENTITY_NAME_KEY   => Entity::ROLE,
            self::VALIDATOR_KEY     => 'edit'
        ],

        'dispute_edit'          => [
            self::ENTITY_NAME_KEY   => Entity::DISPUTE,
            self::VALIDATOR_KEY     => 'edit',
        ],

        'permission_create'          => [
            self::ENTITY_NAME_KEY   => Entity::PERMISSION,
            self::VALIDATOR_KEY     => 'create'
        ],

        // Non-middleware

        'admin_create'          => [
            self::ENTITY_NAME_KEY   => Entity::ADMIN,
            self::VALIDATOR_KEY     => 'create'
        ],
    ];

    const RELATIONS_WHITELIST = [
        // Middleware
        'admin_edit',
        'role_edit',
        'permission_create',

        // Non-middleware
        'admin_create',
    ];

    public static function getEntityName($route)
    {
        $entityName = null;

        if (empty(self::WORKFLOW_MAP[$route]) === false)
        {
            $map = self::WORKFLOW_MAP[$route];

            if (empty($map[self::ENTITY_NAME_KEY]) === false)
            {
                $entityName = $map[self::ENTITY_NAME_KEY];
            }
        }

        return $entityName;
    }

    public static function getValidator($route)
    {
        $validator = null;

        if (empty(self::WORKFLOW_MAP[$route]) === false)
        {
            $map = self::WORKFLOW_MAP[$route];

            // Get 1th index or the VALIDATOR_KEY value

            if (empty($map[self::VALIDATOR_KEY]) === false)
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
