<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Partner\Config as PartnerConfig;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                = 'id';

    // inclusive of tax
    const FEE               = 'fee';

    const TAX               = 'tax';
    const DEBIT             = 'debit';
    const NOTES             = 'notes';
    const CREDIT            = 'credit';
    const STATUS            = 'status';
    const CURRENCY          = 'currency';
    const SOURCE_ID         = 'source_id';
    const PARTNER_ID        = 'partner_id';
    const SOURCE_TYPE       = 'source_type';
    const TRANSACTION_ID    = 'transaction_id';
    const PARTNER_CONFIG_ID = 'partner_config_id';

    protected $entity = 'commission';

    protected static $sign = 'comm';

    protected $primaryKey = self::ID;

    protected $fillable = [
        self::FEE,
        self::TAX,
        self::DEBIT,
        self::NOTES,
        self::CREDIT,
        self::STATUS,
        self::CURRENCY,
    ];

    protected $visible = [
        self::ID,
        self::FEE,
        self::TAX,
        self::DEBIT,
        self::NOTES,
        self::CREDIT,
        self::STATUS,
        self::CURRENCY,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::PARTNER_ID,
        self::TRANSACTION_ID,
        self::PARTNER_CONFIG_ID,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::FEE,
        self::TAX,
        self::DEBIT,
        self::CREDIT,
        self::STATUS,
        self::CURRENCY,
        self::PARTNER_ID,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
    ];

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::NOTES => [],
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction\Entity::class);
    }

    public function partnerConfig()
    {
        return $this->belongsTo(PartnerConfig\Entity::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function partner()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }
}
