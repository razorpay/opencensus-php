<?php

namespace RZP\Models\External;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\BankingAccountStatement;
use RZP\Models\Currency\Currency;

class Entity extends Base\PublicEntity
{
    const BANK_ACCOUNT_STATEMENT_ID = 'banking_account_statement_id';
    const MERCHANT_ID               = 'merchant_id';
    const TRANSACTION_ID            = 'transaction_id';
    const CHANNEL                   = 'channel';
    const BANK_REFERENCE_NUMBER     = 'bank_reference_number';
    const TYPE                      = 'type';
    const AMOUNT                    = 'amount';
    const CURRENCY                  = 'currency';
    const BALANCE_ID                = 'balance_id';

    protected static $sign = 'ext';

    protected $entity = 'external';

    protected $fillable = [
        self::CHANNEL,
        self::BANK_REFERENCE_NUMBER,
        self::TYPE,
        self::AMOUNT,
        self::CURRENCY,
    ];

    protected $visible = [
        self::ID,
        self::BANK_ACCOUNT_STATEMENT_ID,
        self::MERCHANT_ID,
        self::TRANSACTION_ID,
        self::CHANNEL,
        self::BANK_REFERENCE_NUMBER,
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
        self::TYPE,
        self::AMOUNT,
        self::CURRENCY,
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
}
