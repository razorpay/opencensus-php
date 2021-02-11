<?php

namespace RZP\Models\PayoutMeta;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Constants\Table;

/**
 * @property Payout\Entity $payout
 */
class Entity extends Base\PublicEntity
{
    const ID              = 'id';
    const PAYOUT_ID       = 'payout_id';
    const PARTNER_ID      = 'partner_id';
    const APPLICATION_ID  = 'application_id';

    // Relations
    const PAYOUT = 'payout';

    protected static $sign = 'poutmeta';

    protected $primaryKey = self::ID;

    protected $entity = Table::PAYOUTS_META;

    protected $fillable   = [
        self::PAYOUT_ID,
        self::PARTNER_ID,
        self::APPLICATION_ID,
    ];

    protected $visible = [
        self::ID,
        self::PAYOUT_ID,
        self::PARTNER_ID,
        self::APPLICATION_ID,
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
        self::PARTNER_ID,
        self::APPLICATION_ID,
    ];

    protected $generateIdOnCreate = true;

    // ============================= RELATIONS =============================

    public function payout()
    {
        return $this->belongsTo(Payout\Entity::class);
    }

    // ============================= END RELATIONS =============================


    // ============================= GETTERS =============================

    public function getPartnerId()
    {
        return $this->getAttribute(self::PARTNER_ID);
    }

    public function getPayoutId()
    {
        return $this->getAttribute(self::PAYOUT_ID);
    }

    public function getApplicationId()
    {
        return $this->getAttribute(self::APPLICATION_ID);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setPartnerId(string $partnerId)
    {
        $this->setAttribute(self::PARTNER_ID, $partnerId);
    }

    public function setApplicationId(string $applicationId)
    {
        $this->setAttribute(self::APPLICATION_ID, $applicationId);
    }

    // ============================= END SETTERS =============================

    // ============================= HELPERS =============================

    public function toArrayInternal()
    {
        return array_only($this->toArray(), $this->internal);
    }
}
