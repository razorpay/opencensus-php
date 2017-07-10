<?php

namespace RZP\Models\Merchant;

use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class EsRepository extends Base\EsRepository
{
    protected $fields = [
        Entity::ID,
        Entity::ORG_ID,
        Entity::NAME,
        Entity::EMAIL,
        Entity::PARENT_ID,
        Entity::ACTIVATED,
        Entity::ACTIVATED_AT,
        Entity::SUSPENDED_AT,

        // @todo: Think about following:
        // - How will repository fetch work: Validations and ES fetch?
        // - How will people query on merchant details' fields?
        //   I think I will get more clarity as I go through. :(
    ];

    protected $queryFields = [
    ];

    protected $merchantDetailFields = [
        DetailEntity::MERCHANT_ID,
        DetailEntity::BUSINESS_NAME,
    ];

    protected $groupFields = [
        Common::MERCHANT_ID,
        Common::ENTITY_ID,
    ];

    protected $adminFields = [
        Common::MERCHANT_ID,
        Common::ENTITY_ID,
    ];

    public function getMerchantDetailFields()
    {
        return $this->merchantDetailFields;
    }

    public function getGroupFields()
    {
        return $this->groupFields;
    }

    public function getAdminFields()
    {
        return $this->adminFields;
    }
}
