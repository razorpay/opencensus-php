<?php

namespace RZP\Models\Merchant;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Entity as E;

class EsRepository extends Base\Es\Repository
{

    //
    // Before getting merchant into ES (Immediate next pr):
    // Making new entity to be search-able via es is very small effort. In case
    // of merchant it's little tricky because of heimdall acl things, but we have
    // tried to structure the code in such a way that it will be extensible.
    //
    // Following steps are for every (For most of the entities, few of below steps
    // would be optional) new entity to be put in index:
    //
    // - Write corresponding EsRepository class. Ref. Models/Invoice/EsRepository.php
    //   It has list of fields to be indexed into es and their mappings.
    //
    // - Override EsRepository->{updateQuery, serialize} to include all data
    //   to be indexed and to avoid unnecessary mysql queries.
    //
    //   In case of merchant:
    //   - Fetch merchant_details and decide on a structure to keep it in index
    //   - Fetch groups hierarchically bottom to top > unique list to achieve acl in search
    //
    // - Update Repository's fetch rules
    //
    // - Write custom query builders (if needed) in your EsRepository.
    //   Ref. Models/Base/EsQuery.php
    //
    // - At last (Not likely situation) as every method is broken down into chunks
    //   you can just override ->buildQueryAndSearch and ->search methods of
    //   Base/EsRepository to suit your own use case.
    //
    //   In case of merchant:
    //   - We will need to fetch groups of logged in admin and pass it to search
    //     method somehow (additional args, better update params) and then write
    //     a query builder (a filter in this case) for this field (easy deal).
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
    //         Entity::NAME                                           => Base\Es\Mapping::$textFieldMapping,
    //         Entity::EMAIL                                          => Base\Es\Mapping::$textFieldMapping,
    //         Entity::ACTIVATED                                      => Base\Es\Mapping::$booleanFieldMapping,

    //         E::MERCHANT_DETAIL . '.' . Detail\Entity::CREATED_AT   => Base\Es\Mapping::$dateFieldMapping,
    //         E::MERCHANT_DETAIL . '.' . Detail\Entity::SUBMITTED_AT => Base\Es\Mapping::$dateFieldMapping,
    //     ];
    // }

    // public function updateQuery(& $query)
    // {
    //     $query->with('merchantDetail');
    // }
}
