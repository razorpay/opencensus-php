<?php

namespace RZP\Models\AccessPolicyAuthzRolesMap;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception;


class Core extends Base\Core
{
    public function create(array $input)
    {
        $this->trace->info(TraceCode::ACCESS_POLICY_AUTHZ_ROLES_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        $entity = (new Entity)->build($input);

        // check if privilege exists in access_control_privilege entity
        $privilegeEntity = $this->repo->access_control_privileges->findById($input['privilege_id']);

        if(empty($privilegeEntity) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Privilege Id for input " ,
             $input);
        }

        // check if action is valid
        if(Validator::isValidAction($entity['action']) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Action name for input " ,
             $input);
        }

        $existingEntity = $this->checkIfPrivilegeAndActionAlreadyExists($input['privilege_id'], $input['action']);

        if($existingEntity)
        {
            $this->trace->info(TraceCode::ACCESS_POLICY_PRIVILEGE_ACTION_ALREADY_EXISTS,
                [
                    'input' => $input
                ]);

            throw new Exception\BadRequestValidationFailureException("Access Policy Already Exists " ,
                 $input);
        }

        $this->repo->saveOrFail($entity);

        $this->trace->info(TraceCode::ACCESS_POLICY_AUTHZ_ROLES_CREATE_RESPONSE,
            ['access_policy_id' => $entity->getId()]);
    }

    public function checkIfPrivilegeAndActionAlreadyExists($privilegeId, $action) :bool
    {
        $entity = (new Repository())->findByPrivilegeIdAndAction($privilegeId, $action);

        if (empty($entity) === true)
        {
            return false;
        }
        return true;
    }

    public function checkIfAllAccessPolicyIdsExists($ids) :bool
    {
        $idsCount = count($ids);

        $accessPolicyEntitiesCount = $this->repo->access_policy_authz_roles_map->getAccessPoliciesCountByIds($ids);

        return $idsCount === $accessPolicyEntitiesCount;

    }
}
