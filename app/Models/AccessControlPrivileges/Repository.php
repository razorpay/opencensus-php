<?php

namespace  RZP\Models\AccessControlPrivileges;

use RZP\Models\Base;

use RZP\Constants;
use \RZP\Models\AccessPolicyAuthzRolesMap;

class Repository extends Base\Repository
{
    protected $entity = Constants\Table::ACCESS_CONTROL_PRIVILEGES;

    protected $merchantIdRequiredForMultipleFetch = false;

    public function findByName(string $name)
    {
        $query =  $this->newQuery()
            ->where(Entity::NAME, '=', $name);

        return $query->first();
    }

    public function findById(string $id)
    {
        $query =  $this->newQuery()
            ->where(Entity::ID, '=', $id);

        return $query->first();
    }

    public function fetchPrivileges($input)
    {
        $this->setBaseQueryIfApplicable(true);

        $data = parent::fetch($input);

        return $data;
    }

    protected function setBaseQueryIfApplicable(bool $useMasterConnection)
    {
        if ($useMasterConnection === true)
        {
            $mode = $this->app['rzp.mode'];
            $this->baseQuery = $this->newQueryWithConnection($mode)->useWritePdo();
        }
        else
        {
            $this->baseQuery = $this->newQueryWithConnection($this->getSlaveConnection());
        }
    }

    public function deleteAll()
    {
        $this->newQuery()->truncate();
    }
}
