<?php

namespace RZP\Models\Internal;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Currency\Currency;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID               = 'merchant_id';
    const TRANSACTION_ID            = 'transaction_id';
    const UTR                       = 'utr';
    const BANK_NAME                 = 'bank_name';
    const MODE                      = 'mode';
    const ENTITY_ID                 = 'source_entity_id';
    const ENTITY_TYPE               = 'source_entity_type';
    const TYPE                      = 'type';
    const AMOUNT                    = 'amount';
    const BASE_AMOUNT               = 'base_amount';
    const CURRENCY                  = 'currency';
    const REMARKS                   = 'remarks';
    const TRANSACTION_DATE          = 'transaction_date';
    const RECONCILED_AT             = 'reconciled_at';
    const STATUS                    = 'status';

    protected static $sign = 'intr';

    protected $entity = 'internal';

    protected $fillable = [
        self::ID,
        self::TYPE,
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::CURRENCY,
        self::UTR,
        self::MODE,
        self::BANK_NAME,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::REMARKS,
        self::TRANSACTION_DATE,
        self::MERCHANT_ID,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TRANSACTION_ID,
        self::UTR,
        self::MODE,
        self::BANK_NAME,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::REMARKS,
        self::TYPE,
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::TRANSACTION_DATE,
        self::RECONCILED_AT,
        self::STATUS,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::TRANSACTION_ID,
        self::UTR,
        self::MODE,
        self::BANK_NAME,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::TYPE,
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::CURRENCY,
        self::REMARKS,
        self::ENTITY,
        self::TRANSACTION_DATE,
        self::STATUS,
    ];

    protected $defaults = [
        self::CURRENCY => Currency::INR,
    ];

    protected $casts = [
        self::AMOUNT      => 'int',
        self::BASE_AMOUNT => 'int',
    ];

    protected static $generators = [
        self::ID,
    ];

    // ****************** Start of public getters ******************
    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getRemarks()
    {
        return $this->getAttribute(self::REMARKS);
    }

    public function getBankName()
    {
        return $this->getAttribute(self::BANK_NAME);
    }

    // ****************** End of public getters ******************

    //****************** Start of public setters ******************
    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    //
    // Relations with other entities
    //
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }
}
