<?php

namespace RZP\Models\Transaction\Statement\Ledger\LedgerEntry;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Exception\LogicException;

/**
 * Class Entity
 *
 * @package RZP\Models\Transaction\Statement\Ledger\LedgerEntry
 *
 *
 */
class Entity extends \RZP\Models\Transaction\Statement\Ledger\Journal\Entity
{
    protected $entity   = 'ledger_entry';

    const MERCHANT_ID       = 'merchant_id';
    const JOURNAL_ID        = 'journal_id';
    const ACCOUNT_ID        = 'account_id';
    const AMOUNT            = 'amount';
    const BASE_AMOUNT       = 'base_amount';
    const CURRENCY          = 'currency';
    const TENANT            = 'tenant';
    const TYPE              = 'type';
    const BALANCE           = 'balance';

    // Relation names/attributes
    const ACCOUNT_DETAIL    = 'account_detail';
    const ACCOUNT           = 'account';
    const JOURNAL           = 'journal';
    const SOURCE            = 'source';

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::BASE_AMOUNT,
        self::TENANT,
        self::JOURNAL_ID,
        self::ACCOUNT_ID,
        self::TYPE,
        self::BALANCE,
    ];

    protected $public = [
        self::ID,
        self::TENANT,
        self::JOURNAL_ID,
        self::ACCOUNT_ID,
        self::TYPE,
        self::BASE_AMOUNT,
        self::AMOUNT,
        self::CURRENCY,
        self::CREATED_AT,
    ];

    /**
     * Relations to be returned when receiving expand[] query param in fetch
     *
     * @var array
     */
    protected $expanded = [
        self::ACCOUNT,
        self::ACCOUNT_DETAIL,
        self::JOURNAL,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::BALANCE               => null,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::BALANCE,
    ];

    protected $casts = [
        self::AMOUNT              => 'int',
        self::BASE_AMOUNT         => 'int',
        self::BALANCE             => 'int',
    ];

    protected $ignoredRelations = [
    ];

    protected $appends = [
       // self::SOURCE,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function AccountDetail()
    {
        return $this->belongsTo('RZP\Models\Transaction\Statement\Ledger\AccountDetail\Entity');
    }

    public function Account()
    {
        return $this->belongsTo('RZP\Models\Transaction\Statement\Ledger\Account\Entity');
    }

    public function Journal()
    {
        return $this->belongsTo('RZP\Models\Transaction\Statement\Ledger\Journal\Entity');
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getBalance()
    {
        return $this->getAttribute(self::BALANCE);
    }

    public function getJournalId()
    {
        return $this->getAttribute(self::JOURNAL_ID);
    }

    public function getAccountId()
    {
        return $this->getAttribute(self::ACCOUNT_ID);
    }

    public function getTransactorTypeAttribute()
    {
        if ($this->journal)
        {
            return $this->journal->transactor_type;
        }
    }

    public function getTransactorInternalIdAttribute()
    {
        if ($this->journal)
        {
            return $this->journal->transactor_internal_id;
        }
    }
}
