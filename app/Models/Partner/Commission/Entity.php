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
    const MODEL             = 'model';
    const DEBIT             = 'debit';
    const NOTES             = 'notes';
    const CREDIT            = 'credit';
    const STATUS            = 'status';
    const CURRENCY          = 'currency';
    const SOURCE_ID         = 'source_id';
    const PARTNER_ID        = 'partner_id';
    const SOURCE_TYPE       = 'source_type';
    const RECORD_ONLY       = 'record_only';
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
        self::RECORD_ONLY,
        self::MODEL,
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
        self::RECORD_ONLY,
        self::MODEL,
        self::CREATED_AT,
        self::MERCHANT,
        self::SOURCE,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::MERCHANT,
    ];

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::TYPE        => Type::IMPLICIT,
        self::MODEL       => PartnerConfig\CommissionModel::COMMISSION,
        self::NOTES       => [],
        self::STATUS      => Status::CREATED,
        self::CURRENCY    => 'INR',
        self::RECORD_ONLY => 0,
    ];

    protected $casts = [
        self::RECORD_ONLY => 'bool',
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

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class, self::PARTNER_ID);
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

    public function isCaptured(): bool
    {
        return $this->getStatus() === Status::CAPTURED;
    }

    public function isRecordOnly(): bool
    {
        return ($this->getAttribute(self::RECORD_ONLY) === true);
    }

    public function isSubventionModel(): bool
    {
        return ($this->getAttribute(self::MODEL) === PartnerConfig\CommissionModel::SUBVENTION);
    }

    public function getFee(): int
    {
        return $this->getAttribute(self::FEE);
    }

    public function getTax(): int
    {
        return $this->getAttribute(self::TAX);
    }

    public function getCredit()
    {
        return $this->getAttribute(self::CREDIT);
    }

    public function getDebit()
    {
        return $this->getAttribute(self::DEBIT);
    }

    public function getType(): string
    {
        return $this->getAttribute(self::TYPE);
    }

    public function hasTransaction()
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID));
    }

    /**
     * Defining this function helps us to add a filter for merchant_id in a query -
     * Eg: $this->newQuery()->merchantId($merchant->getId())
     *
     * This function overrides the function defined in Base\EloquentEx class.
     * The base function is used for fetching entities which have merchant_id. It is tightly
     * coupled with RepositoryFetch class's fetch(), fetchByIdAndMerchantId() etc methods.
     *
     * The same behaviour is required for commissions but instead of adding a filter
     * for merchant_id, the filter is required for partner_id. Defining a separate function named
     * scopePartnerId and usage $this->newQuery()->partnerId($partnerMerchant->getId()) would have
     * been an ideal case, but would require the new function to be supported in all the above
     * mentioned functions of RepositoryFetch class. Hence, overriding the function definition here.
     *
     * Though the name is scopeMerchantId, it actually adds a filter for partner_id.
     *
     * @param $query
     * @param $merchantId
     */
    public function scopeMerchantId($query, $merchantId)
    {
        $partnerIdColumn = $this->dbColumn(Entity::PARTNER_ID);

        $query->where($partnerIdColumn, $merchantId);
    }

    public function getMetricDimensions(array $extra = []): array
    {
        return $extra + [
                'type'   => $this->getType(),
                'status' => $this->getStatus(),
            ];
    }
}
