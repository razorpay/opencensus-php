<?php

namespace RZP\Models\Admin\Org\FieldMap;

use RZP\Base;
use RZP\Constants;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_NAME         => 'required|string|max:250|custom',
        Entity::ORG_ID              => 'required|string|max:14',
        Entity::FIELDS              => 'required|array',
    ];

    protected static $editRules = [
        Entity::ENTITY_NAME         => 'sometimes|string|max:250|custom',
        Entity::ORG_ID              => 'sometimes|string|max:14',
        Entity::FIELDS              => 'sometimes|array',
    ];

    protected static $createValidators = [
        'fields',
    ];

    protected static $editValidators = [
        'fields',
    ];

    protected function validateEntityName($attribute, $entity)
    {
        // Only validate the entities for ehich namespace is defined
        $namespaces = Constants\Entity::$namespace;

        if (in_array($entity, array_keys($namespaces), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The entity name is not registered in the api', 'entity',
                $entity);
        }
    }

    protected function validateFields(array $input)
    {
        if (isset($input[Entity::ENTITY_NAME]) === false)
        {
            // if called by edit validator
            $entityName = $this->entity->getNameOfEntity();
        }
        else
        {
            $entityName = $input[Entity::ENTITY_NAME];
        }

        $class = Constants\Entity::$namespace[$entityName] . '\Entity';

        $entity = new $class;

        $entityFields = $entity->getInputFields();

        // We only consider fillable fields for addition or deletion
        // In case of entities like admin-lead we fetch from validators
        $diffArray = array_diff($input[Entity::FIELDS], $entityFields);

        if (empty($diffArray) === false)
        {
            $data = ['entity' => $class, 'invalidFields' => $diffArray];

            throw new Exception\BadRequestValidationFailureException(
                'Few fields are invalid for the given entity', 'entity',
                $entity);
        }
    }
}
