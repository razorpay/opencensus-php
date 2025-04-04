<?php

namespace RZP\Models\AccessControlPrivileges;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $entityRepo;

    /**
     * @var Core
     */
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function createPrivilege(array $input) :array
    {
        return $this->core->create($input);
    }

    public function listDashboardPrivileges()
    {
        if ($this->merchant->checkCACMigrationExperimentEnabled())
        {
            return $this->core->fetchPrivilegesFromAuthz();
        }

        $input = [
            Entity::VISIBILITY => 1,
            'expand' => ['actions'],
            'count'  => Entity::PRIVILEGES_FETCH_DATA_COUNT
        ];

        $privileges = $this->core->fetchPrivileges($input);

        $privilegeIds = $privileges->pluck(Entity::ID)->toArray();

        $privileges = $privileges->toArrayPublicWithExpand();

        array_multisort(array_column($privileges['items'], Entity::VIEW_POSITION), $privileges['items']);

        $this->core->generateResponseTemplate($privileges);

        return [Entity::PRIVILEGE_DATA => $privileges];
    }

    public function addNewPrivilegeAndItsDependencies($input)
    {
        return $this->core->addNewPrivilegeAndItsDependencies($input);
    }

    public function addPrivilegeOnAuthz($input)
    {
        (new Validator())->validateInput(Validator::CREATE_ON_AUTHZ, $input);

        return $this->core->addPrivilegeOnAuthz($input);
    }

    public function updatePrivilegeOnAuthz($id, $input)
    {
        $input[Entity::ID] = $id;

        (new Validator())->validateInput(Validator::UPDATE_ON_AUTHZ, $input);

        return $this->core->updatePrivilegeOnAuthz($input);
    }

    public function addPrivilegeRoleMappingOnAuthz($input)
    {
        (new Validator())->validateInput(Validator::CREATE_PRIVILEGE_ROLE_MAPPING_ON_AUTHZ, $input);

        return $this->core->addPrivilegeRoleMappingOnAuthz($input);
    }

    public function updatePrivilegeRoleMappingOnAuthz($id, $input)
    {
        $input[Entity::ID] = $id;

        (new Validator())->validateInput(Validator::UPDATE_PRIVILEGE_ROLE_MAPPING_ON_AUTHZ, $input);

        return $this->core->updatePrivilegeRoleMappingOnAuthz($input);
    }
}
