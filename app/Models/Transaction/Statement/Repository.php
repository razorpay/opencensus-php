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
    protected $entity = 'statement';

    protected $expands = [
        Entity::SOURCE,
    ];

    public function fetch(array $input, string $merchantId = null): PublicCollection
    {
        $statements = parent::fetch($input, $merchantId);

        $statements->where('type', 'payout')->load(['source.customer', 'source.destination']);

        return $statements;
    }
}
