<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Base;
use RZP\Constants\Entity as E;

/**
 * Class Repository
 *
 * @package RZP\Models\FundAccount
 */
class Repository extends Base\Repository
{
    protected $entity = 'fund_account';

    protected $expands = [
        Entity::ACCOUNT,
    ];

    protected function addQueryParamCustomerId($query, $params)
    {
        $query->where(Entity::SOURCE_ID, $params[Entity::CUSTOMER_ID])
              ->where(Entity::SOURCE_TYPE, E::CUSTOMER);
    }

    protected function addQueryParamContactId($query, $params)
    {
        $query->where(Entity::SOURCE_ID, $params[Entity::CONTACT_ID])
              ->where(Entity::SOURCE_TYPE, E::CONTACT);
    }
}
