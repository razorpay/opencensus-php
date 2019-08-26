<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Base;
use RZP\Constants\Es;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
        Entity::CREATED_AT,
    ];

    protected $esFetchParams = [
        Entity::NOTES,
    ];

    public function buildQueryForNotes(array & $query, string $value)
    {
        //
        // - Notes search is again on an specific object (unlike 'q') and so
        //   we give boost of 2.
        // - The query construct is same as above (for 'q') but the fields here
        //   are all keys of notes object (denoted as notes.*).
        //

        $clause = [
            Es::MULTI_MATCH => [
                Es::QUERY                => $value,
                Es::TYPE                 => Es::BEST_FIELDS,
                Es::FIELDS               => 'notes.*',
                Es::BOOST                => 2,
                Es::MINIMUM_SHOULD_MATCH => '100%',
            ],
        ];

        $this->addMust($query, $clause);
    }
}
