<?php

namespace RZP\Models\Merchant;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Entity as E;

class EsRepository extends Base\EsRepository
{

    //
    // TODOs: (Before getting merchant into es)
    // - Identifying all the fields to be kept in es
    // - Overriding extend query to return following:
    //   - Merchant details
    //   - Groups - Hierarchically bottom to top > unique list
    // - Update corresponding repo's fetch param rules
    // - Send 'groups' list in search method, write method to use it and form
    //   filter query
    //

    //
    // Fields of merchant_details should be indexed in db as it'll come serialized
    // from normal eloquent model, ie:
    // {
    //      id: ..,
    //      name: ..,
    //      merchant_detail: {
    //          created_at: ...,
    //          submitted_at: ...,
    //          ...
    //      }
    // }
    // 
    // And the same should be added in 'fields' as dotted notation, eg:
    // [
    //      'id',
    //      'name',
    //      'merchant_detail.created_at',
    // ]
    //


    protected static $table = Table::MERCHANT;

    // protected $fields = [
    //     Entity::ID,
    //     Entity::NAME,
    //     Entity::EMAIL,
    //     Entity::ACTIVATED,

    //     E::MERCHANT_DETAIL . '.' . Detail\Entity::CREATED_AT,
    //     E::MERCHANT_DETAIL . '.' . Detail\Entity::SUBMITTED_AT,
    // ];

    // protected $queryFields = [
    //     Entity::NAME,
    //     Entity::EMAIL,
    // ];

    // public function setFieldMappings()
    // {
    //     $this->fieldMappings = [
    //         Entity::NAME                                           => Base\EsMappping::$textFieldMapping,
    //         Entity::EMAIL                                          => Base\EsMappping::$textFieldMapping,
    //         Entity::ACTIVATED                                      => Base\EsMappping::$booleanFieldMapping,

    //         E::MERCHANT_DETAIL . '.' . Detail\Entity::CREATED_AT   => Base\EsMappping::$dateFieldMapping,
    //         E::MERCHANT_DETAIL . '.' . Detail\Entity::SUBMITTED_AT => Base\EsMappping::$dateFieldMapping,
    //     ];
    // }

    // public function updateQuery(& $query)
    // {
    //     $query->with('merchantDetail');
    // }
}
