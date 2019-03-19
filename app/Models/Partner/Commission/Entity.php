<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Exception\LogicException;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Partner\Config as PartnerConfig;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                = 'id';

    // inclusive of tax
    const FEE               = 'fee';

    const TAX               = 'tax';
    const TYPE              = 'type';
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

    const SOURCE            = 'source';
    const MERCHANT          = 'merchant';
    const SOURCE_MERCHANT   = 'source.merchant';

    protected $entity = 'commission';

    protected static $sign = 'comm';

    protected $primaryKey  = self::ID;

    protected $fillable = [
        self::FEE,
        self::TAX,
        self::TYPE,
        self::DEBIT,
        self::NOTES,
        self::CREDIT,
        self::CURRENCY,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::FEE,
        self::TAX,
        self::TYPE,
        self::DEBIT,
        self::CREDIT,
        self::STATUS,
        self::CURRENCY,
        self::PARTNER_ID,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::CREATED_AT,
        self::MERCHANT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::MERCHANT,
    ];

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::TYPE   => Type::IMPLICIT,
        self::STATUS => Status::CREATED,
        self::NOTES  => [],
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

    public function setPublicMerchantAttribute(array &$array)
    {
        // payment, refund, etc
        $sourceRelation = $this->relationLoaded('source') ? $this->getRelation('source') : null;

        // corresponding merchant entity
        $merchant = optional($sourceRelation)->getAttribute('merchant');

        if ($merchant !== null)
        {
            $array[self::MERCHANT] = $merchant->toArrayPublic();
        }
    }

    /**
     * @param string $next
     *
     * @throws LogicException
     */
    public function setStatus(string $next)
    {
        $current = $this->getStatus();

        if (Status::isValidStateTransition($current, $next) === false)
        {
            throw new LogicException(
                'Invalid status transition',
                null,
                [
                    'current' => $current,
                    'next'    => $next,
                ]
            );
        }

        $this->setAttribute(self::STATUS, $next);
    }

    public function getStatus(): string
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getFee(): int
    {
        return $this->getAttribute(self::FEE);
    }

    public function getTax(): int
    {
        return $this->getAttribute(self::TAX);
    }

    public function getType(): string
    {
        return $this->getAttribute(self::TYPE);
    }
}
