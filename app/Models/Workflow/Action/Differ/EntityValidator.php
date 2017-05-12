<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Admin;

class EntityValidator
{
    const ENTITY_NAME_KEY       = 'entity_name';
    const VALIDATOR_KEY         = 'validator';
    const ENTITY_CLASS_KEY      = 'entity_class';

    /**
     * Route to [] map to ensure a bunch of things like:
     *
     * - Automatic creation of Diff data.
     * - Automatic validation of incoming payload over the entity.
     * - Automatic resolution and creation of relational diff.
     *
     * The map can be index based or key based:
     *
     * 0 or entity_name for Entity Name.
     * 1 or validator for Validator.
     * 2 or entity_class for Entity Class that gives relations for differ.
     */
    const WORKFLOW_MAP = [
        'merchant_edit_email'   => [ 'merchant', 'editEmail' ],

        'admin_edit'            => [ 'admin', 'edit', Admin\Admin\Entity::class ],

        'role_edit'             => [
            self::ENTITY_NAME_KEY   => 'role',
            self::VALIDATOR_KEY     => 'edit',
            self::ENTITY_CLASS_KEY  => Admin\Role\Entity::class,
        ],
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
        $entityClass = null;

        if (array_key_exists($route, self::WORKFLOW_MAP))
        {
            $map = self::WORKFLOW_MAP[$route];

            if ((static::indexBasedMap($map)) and (empty($map[0]) === false))
            {
                $entityClass = $map[2];
            }
            else if (empty($map[self::ENTITY_CLASS_KEY]) === false)
            {
                $entityClass = $map[self::ENTITY_CLASS_KEY];
            }
        }

        if (empty($entityClass) === true)
        {
            return [];
        }

        // If entity class has been found then get an array
        // of relations on which the differ has to be generated

        $relations = (new $entityClass)->getRelationsForDiffer();

        return $relations;
    }
}
