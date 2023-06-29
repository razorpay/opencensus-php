<?php

namespace RZP\Models\PayoutSource;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

/**
 * @property Payout\Entity $payout
 */
class Entity extends Base\PublicEntity
{
    const ID              = 'id';
    const PAYOUT_ID       = 'payout_id';
    const SOURCE_ID       = 'source_id';
    const SOURCE_TYPE     = 'source_type';
    const PRIORITY        = 'priority';

    const PAYOUT_LINK                    = 'payout_links';
    const VENDOR_PAYMENTS                = 'vendor_payments';
    const TAX_PAYMENTS                   = 'tax_payments';
    const VENDOR_SETTLEMENTS             = 'vendor_settlements';
    const VENDOR_ADVANCE                 = 'vendor_advance';
    const SETTLEMENTS                    = 'settlements';
    const XPAYROLL                       = 'xpayroll';
    const REFUND                         = 'refund';
    const CAPITAL_COLLECTIONS            = 'capital_collections';
    const GENERIC_ACCOUNTING_INTEGRATION = 'generic_accounting_integration';

    // Relations
    const PAYOUT = 'payout';

    protected static $sign = 'poutsrc';

    protected $primaryKey = self::ID;

    protected $entity = 'payout_source';

    protected static $validSourceTypes = [
        self::PAYOUT_LINK,
        self::VENDOR_PAYMENTS,
        self::TAX_PAYMENTS,
        self::SETTLEMENTS,
        self::XPAYROLL,
        self::CAPITAL_COLLECTIONS
    ];

    protected $fillable   = [
        self::PAYOUT_ID,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::PRIORITY,
    ];

    protected $visible = [
        self::ID,
        self::PAYOUT_ID,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::PRIORITY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected static $generators = [
        self::ID,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $internal = [
        self::ID,
        self::PAYOUT_ID,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::PRIORITY,
    ];

    protected $ignoredRelations = [
        self::PAYOUT,
    ];

    protected $generateIdOnCreate = true;

    // ============================= RELATIONS =============================

    public function payout()
    {
        return $this->belongsTo(Payout\Entity::class);
    }

    // ============================= END RELATIONS =============================


    // ============================= GETTERS =============================

    public function getSourceId()
    {
        return $this->getAttribute(self::SOURCE_ID);
    }

    public function getSourceType()
    {
        return $this->getAttribute(self::SOURCE_TYPE);
    }

    public function getPriority()
    {
        return $this->getAttribute(self::PRIORITY);
    }

    public function getPayoutId()
    {
        return $this->getAttribute(self::PAYOUT_ID);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setSourceId($sourceId)
    {
        $this->setAttribute(self::SOURCE_ID, $sourceId);
    }

    public function setSourceType($sourceType)
    {
        $this->setAttribute(self::SOURCE_TYPE, $sourceType);
    }

    public function setPriority($priority)
    {
        $this->setAttribute(self::PRIORITY, $priority);
    }

    // ============================= END SETTERS =============================

    // ============================= HELPERS =============================

    public function toArrayInternal()
    {
        return array_only($this->toArray(), $this->internal);
    }
}
