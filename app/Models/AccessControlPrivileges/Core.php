<?php

namespace RZP\Models\AccessControlPrivileges;;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Models\AccessPolicyAuthzRolesMap;
use RZP\Exception;


class Core extends Base\Core
{
    public function create(array $input) :array
    {
        $this->trace->info(TraceCode::ACCESS_CONTROL_PRIVILEGE_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        $privilegeEntity = (new Entity)->build($input);

        if (empty($input['parent_id']) === false and $this->checkIfParentEntityExists($input['parent_id']) === false)
        {
            $this->trace->error(TraceCode::ACCESS_CONTROL_PRIVILEGE_INVALID_PARENT_ID,
                [
                    'input' => $input
                ]);

            throw new Exception\BadRequestValidationFailureException("Invalid Parent Id for input " ,
                 $input);
        }

        $this->repo->saveOrFail($privilegeEntity);

        $this->trace->info(TraceCode::ACCESS_CONTROL_PRIVILEGE_CREATE_RESPONSE,
            ['privilege_id' => $privilegeEntity->getId()]);

        return $privilegeEntity->toArrayPublic();

    }

    public function fetchPrivileges($input)
    {
        $privileges = $this->repo->access_control_privileges->fetchPrivileges($input);

        return $privileges;
    }

    public function generateResponseTemplate(& $privileges)
    {
        foreach ($privileges['items'] as & $privilege)
        {
            $privilege = $this->expandValueForKey($privilege, Entity::EXTRA_DATA);

            $actions = [];
            foreach ($privilege[Entity::ACTIONS]['items'] as $key => $actionData)
            {
                $action = $actionData[AccessPolicyAuthzRolesMap\Entity::ACTION];
                unset($actionData[AccessPolicyAuthzRolesMap\Entity::ACTION]);

                $actions[$action] = $this->expandValueForKey($actionData, AccessPolicyAuthzRolesMap\Entity::META_DATA);
            }

            $privilege[Entity::ACTIONS] = $actions;
        }

        $privileges = $this->makeHierarchicalStructure($privileges);
    }

    protected function expandValueForKey($array, $key)
    {
        if (empty($array[$key]) === true)
        {
            unset($array[$key]);
            return $array;
        }

        $data = $array[$key];
        unset($array[$key]);

        return array_merge($array, $data);
    }

    protected function makeHierarchicalStructure($privileges)
    {
        $items = [];
        array_walk($privileges['items'], function($value, $key) use (& $items){
            $items[$value[Entity::ID]] = $value;
            $items[$value[Entity::ID]][Entity::PRIVILEGE_DATA] = null;
        });

        foreach ($items as $id => $privilege)
        {
            if (empty($privilege[Entity::PARENT_ID]) === false)
            {
                $items[$privilege[Entity::PARENT_ID]][Entity::PRIVILEGE_DATA][] = $privilege;
                unset($items[$id]);
            }
        }

        $responseData = [];

        array_walk($items, function($value, $key) use (& $responseData){
            $responseData[] = $value;
        });

        return $responseData;
    }

    private function checkIfNameAlreadyExists(string $name) :bool
    {
        $tableName = Table::ACCESS_CONTROL_PRIVILEGES;

        $entity = $this->repo->{$tableName}->findByName($name);

        if (empty($entity) === true)
        {
            return false;
        }
        return true;
    }

    private function checkIfParentEntityExists(string $parentId) :bool
    {

        $tableName = Table::ACCESS_CONTROL_PRIVILEGES;

        $entity = $this->repo->{$tableName}->findById($parentId);

        if (empty($entity))
        {
            return false;
        }

        return true;
    }

}
