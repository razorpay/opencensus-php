<?php

namespace RZP\Models\Transaction\Statement\Ledger\Journal;

use Db;
use Illuminate\Database\Query\JoinClause;

use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\External;
use RZP\Models\Transaction;
use RZP\Models\FundAccount;
use RZP\Base\ConnectionType;
use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundAccount\Validation;
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
    protected $entity = 'journal';
}
