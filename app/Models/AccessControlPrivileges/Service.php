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
}
