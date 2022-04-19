<?php

namespace RZP\Models\External;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use RZP\Models\BankingAccountStatement;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const BANK_ACCOUNT_STATEMENT_ID = 'banking_account_statement_id';
    const MERCHANT_ID               = 'merchant_id';
    const TRANSACTION_ID            = 'transaction_id';
    const CHANNEL                   = 'channel';
    const BANK_REFERENCE_NUMBER     = 'bank_reference_number';
    const UTR                       = 'utr';
    const TYPE                      = 'type';
    const AMOUNT                    = 'amount';
    const CURRENCY                  = 'currency';
    const BALANCE_ID                = 'balance_id';
    const REMARKS                   = 'remarks';

    protected static $sign = 'ext';

    protected $entity = 'external';

    protected $fillable = [
        self::CHANNEL,
        self::BANK_REFERENCE_NUMBER,
        self::TYPE,
        self::AMOUNT,
        self::CURRENCY,
        self::UTR,
        self::REMARKS,
    ];

    protected $visible = [
        self::ID,
        self::BANK_ACCOUNT_STATEMENT_ID,
        self::MERCHANT_ID,
        self::TRANSACTION_ID,
        self::CHANNEL,
        self::BANK_REFERENCE_NUMBER,
        self::UTR,
        self::REMARKS,
        self::TYPE,
        self::AMOUNT,
        self::CURRENCY,
        self::BALANCE_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::TRANSACTION_ID,
        self::CHANNEL,
        self::BANK_REFERENCE_NUMBER,
        self::UTR,
        self::TYPE,
        self::AMOUNT,
        self::CURRENCY,
        self::REMARKS,
        self::ENTITY,
    ];

    protected $defaults = [
        self::CURRENCY => Currency::INR,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected static $generators = [
        self::ID,
    ];

    protected $ignoredRelations = [
        // BAS entity is not saved still while external entity is being saved.
        // This is fine because the BAS entity save and external entity save
        // happen in a DB transaction. If it was not in a DB transaction,
        // external entity would have gotten saved with BAS ID and BAS entity
        // save could have failed, which would result in a bad foreign key in external.
        'bankingAccountStatement',
    ];

    //
    // Relations with other entities
    //

    public function bankingAccountStatement()
    {
        return $this->belongsTo(BankingAccountStatement\Entity::class);
    }

    public function balance()
    {
        return $this->belongsTo(Balance\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction\Entity::class);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setRemarks($remarks)
    {
        $this->setAttribute(self::REMARKS, $remarks);
    }

    public function getEntitySign(): string
    {
        return self::$sign;
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getBaseAmount()
    {
        return $this->getAmount();
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
    }

    public function hasTransaction()
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID) === true);
    }
}
