<?php

namespace RZP\Models\Transaction\Statement\Ledger\Account;

use Db;

use RZP\Models\Base;

/**
 * Class Repository
 *
 * @package RZP\Models\Transaction\Statement
 */
class Repository extends Base\Repository
{
    /**
     * {@inheritDoc}
     */
    protected $entity = 'ledger_account';
}
