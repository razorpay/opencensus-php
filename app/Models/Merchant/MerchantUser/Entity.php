<?php

namespace RZP\Models\Merchant\MerchantUser;

use RZP\Base\Pivot;
use RZP\Constants\Table;

/**
 * This class extends a Pivot model which is designed to handle
 * pivot tables as regular model classes. This makes it easy to
 * access them directly and not necessarily via associated
 * entities. We can also force this class in to a regular public
 * entity by adding a unique id and defining all the relations
 * explicitly (like AccessMap entity) but this feels cleaner.
 *
 * Class Entity
 * @package RZP\Models\Merchant\MerchantUser
 */

class Entity extends Pivot
{
    const MERCHANT_ID = 'merchant_id';
    const USER_ID     = 'user_id';
    const ROLE        = 'role';
    const PRODUCT     = 'product';
    const CREATED_AT  = 'created_at';
    const UPDATED_AT  = 'updated_at';

    protected $table = Table::MERCHANT_USERS;

    public function getRole()
    {
        return $this->getAttribute(self::ROLE);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

}
