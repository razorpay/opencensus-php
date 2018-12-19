<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Models\Transaction;
use RZP\Models\Base\PublicCollection;

/**
 * Class Repository
 *
 * @package RZP\Models\Transaction\Statement
 */
class Repository extends Transaction\Repository
{
    /**
     * {@inheritDoc}
     */
    protected $entity = 'statement';

    /**
     * {@inheritDoc}
     */
    protected $expands = [
        Entity::SOURCE,
        Entity::ACCOUNT_BALANCE,
    ];

    /**
     * {@inheritDoc}
     */
    public function fetch(array $input, string $merchantId = null): PublicCollection
    {
        $statements = parent::fetch($input, $merchantId);

        // Todo: Update these after 'payout-on-fa' branch is merged.
        // After fetching settlement collection, we lazy load source relations for payout.
        // $statements->where(Entity::TYPE, E::PAYOUT)
        //            ->load(
        //                 [
        //                     'source.customer',
        //                     'source.destination',
        //                 ]);

        return $statements;
    }
}
