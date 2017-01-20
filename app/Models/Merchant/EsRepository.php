<?php

namespace RZP\Models\Merchant;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Entity as E;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::MERCHANT;

    protected $fields = [
        Entity::ID,
        Entity::NAME,
        Entity::EMAIL,
        Entity::ACTIVATED,

        E::MERCHANT_DETAIL . '.' . Detail\Entity::CREATED_AT,
        E::MERCHANT_DETAIL . '.' . Detail\Entity::SUBMITTED_AT,
    ];

    protected $queryFields = [
        Entity::NAME,
        Entity::EMAIL,
    ];

    public function setFieldMappings()
    {
        $this->fieldMappings = [
            Entity::NAME                                           => Base\EsMappping::$defaultTextFieldMapping,
            Entity::EMAIL                                          => Base\EsMappping::$defaultTextFieldMapping,
            Entity::ACTIVATED                                      => Base\EsMappping::$booleanFieldMapping,

            E::MERCHANT_DETAIL . '.' . Detail\Entity::CREATED_AT   => Base\EsMappping::$dateFieldMapping,
            E::MERCHANT_DETAIL . '.' . Detail\Entity::SUBMITTED_AT => Base\EsMappping::$dateFieldMapping,
        ];
    }

    public function updateQuery(& $query)
    {
        $query->with('merchantDetail');

        /**
         * TODO
         * Get merchant's group results to be indexed too.
         * That will be used for acl.
         */
    }
}
