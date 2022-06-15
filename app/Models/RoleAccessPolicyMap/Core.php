<?php

namespace RZP\Models\RoleAccessPolicyMap;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create($input) :Entity
    {
        $this->trace->info(TraceCode::ROLE_ACCESS_POLICY_MAP_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        $entity = (new Entity)->build($input);

        $this->repo->saveOrFail($entity);

        $this->trace->info(TraceCode::ROLE_ACCESS_POLICY_MAP_CREATE_RESPONSE,
            ['mapping_id' => $entity->getId()]);

        return $entity;
    }

    public function edit($input) :Entity
    {
        $roleMap = $this->repo->role_access_policy_map->findByRoleId($input['role_id']);

        if(empty($roleMap) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Request" ,
                $input);
        }

        $roleMap->edit($input);

        $this->trace->info(TraceCode::ROLE_ACCESS_POLICY_MAP_UPDATE_REQUEST,
            [
                'input' => $input
            ]);

        $this->repo->saveOrFail($roleMap);

        $this->trace->info(TraceCode::ROLE_ACCESS_POLICY_MAP_UPDATE_RESPONSE,
            ['mapping_id' => $roleMap->getId()]);

        return $roleMap;
    }
}
