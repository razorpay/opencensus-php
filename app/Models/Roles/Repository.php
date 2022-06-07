<?php

namespace RZP\Models\Roles;


use RZP\Base;

class Repository extends Base\Repository
{
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

        return $query->get();
    }
}


/*
select count(merchant_users.user_id), roles.* from roles join merchant_users
on roles.id = merchant_users.role_id where roles.merchant_id = ? and roles.type=? and roles.name=? group by role_id;
*/

