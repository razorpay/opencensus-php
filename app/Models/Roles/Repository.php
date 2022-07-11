<?php

namespace RZP\Models\Roles;

use RZP\Models\Base;
use RZP\Models\User\Role;
use RZP\Constants\Product;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'roles';

    public function listRoles($params)
    {
        $unionQuery = null;

        $types = array_pull($params,Entity::TYPE);

        unset($params[Entity::TYPE]);

        $selectAttr = [Entity::ID, Entity::MERCHANT_ID, Entity::NAME, Entity::DESCRIPTION, Entity::TYPE];

        foreach ($types as $type)
        {
            $query = $this->newQuery();

            $query->select($selectAttr);

            $query->where(Entity::TYPE, $type);

            if ($type === Entity::CUSTOM && isset($params[Entity::MERCHANT_ID]) == true )
            {
                $query->where(Entity::MERCHANT_ID, $params[Entity::MERCHANT_ID]);
            }
            unset($params[Entity::MERCHANT_ID]);

            $this->buildQueryWithParams($query, $params);

            $unionQuery = empty($unionQuery) ? $query : $unionQuery->union($query);
        }

        return $unionQuery->get();
    }

    public function fetchRole($id)
    {
        return $this->newQuery()
            ->where(Entity::ID, '=', $id)
            ->first();
    }

    public function insertRecord($input)
    {
        \DB::connection('live')->table('access_control_roles')->insert($input);
        \DB::connection('test')->table('access_control_roles')->insert($input);

    }

    public function findOrFailByPublicIdWithParams(
        string $id,
        array  $params = [],
        string $connectionType = null) : Base\PublicEntity
    {
        $query = $this->getQueryForFindWithParams($params, $connectionType);

        // check if the role is a standard role
        if ((new Core())->checkIfRoleIsStandardRole($id) === true) {
            $params[Entity::MERCHANT_ID] = Entity::STANDARD_ROLE_MERCHANT_ID;
        } else {
            $params[Entity::MERCHANT_ID] = $this->merchant->getId();
        }

        $query = $query->merchantId($params[Entity::MERCHANT_ID]);

        $entity = $query->findOrFailPublic($id);

        return $entity;
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

    public function findNameByStandRolesOrMerchantId($name, $merchantId)
    {
        return $this->newQuery()->where('name', '=' , $name)
            ->where(function($query) use ($merchantId) {
                $query->where(Entity::TYPE, '=', Entity::STANDARD)
                    ->orWhere(Entity::MERCHANT_ID, '=', $merchantId);
            })->first();
    }

    public function deleteAll()
    {
        $this->newQueryWithConnection('live')->truncate();

        $this->newQueryWithConnection('test')->truncate();
    }

    public function fetchRoleName($roleId)
    {
        $roleEntity =  $this->fetchRole($roleId);

        if(empty($roleEntity) === true)
        {
            return null;
        }
        return $roleEntity->getName();
    }
}


