<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
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
    protected $entity  = 'statement';

    /**
     * {@inheritDoc}
     */
    protected $expands = [
        Entity::SOURCE,
    ];

    /**
     * {@inheritDoc}
     */
    public function fetch(array $input, string $merchantId = null): PublicCollection
    {
        $statements = parent::fetch($input, $merchantId);

        // After fetching settlement collection, we lazy load source relations for payout.
        $statements->where(Entity::TYPE, E::PAYOUT)
                   ->load(
                        [
                            'source.customer',
                            'source.destination',
                        ]);

        return $statements;
    }

    /**
     * {@inheritDoc}
     */
    protected function buildFetchQueryAdditional($params, $query)
    {
        // Applies balance_id filter implicitly basis current context's product type
        $balanceId = $this->repo->balance->getBalanceForRequestContext()->getId();
        $query->where(Entity::BALANCE_ID, $balanceId);
    }
}
