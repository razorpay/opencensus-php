<?php

namespace RZP\Models\PayoutsStatusDetails;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Payout;
use RZP\Constants\Table;

/**
 * @property Payout\Entity $payout
 */
class Entity extends PublicEntity
{
    const ID                   = 'id';

    const PAYOUT_ID            = 'payout_id';

    const STATUS               = 'status';

    const REASON              = 'reason';

    const DESCRIPTION          = 'description';

    const MODE                  = 'mode';

    const TRIGGERED_BY        = 'triggered_by';

    // Relations
    const PAYOUT = 'payout';

    protected $entity = Table::PAYOUTS_STATUS_DETAILS;

    protected $primaryKey = self::ID;

    protected $fillable   = [
        self::PAYOUT_ID,
        self::STATUS,
        self::REASON,
        self::DESCRIPTION,
    ];

    protected $visible = [
        self::ID,
        self::PAYOUT_ID,
        self::STATUS,
        self::REASON,
        self::DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];


    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::REASON => null,
        self::DESCRIPTION => null,
    ];

    protected $generateIdOnCreate = true;
    // ============================= RELATIONS =============================

    public function payout()
    {
        return $this->belongsTo(Payout\Entity::class);
    }

    // ============================= END RELATIONS =============================


    // ============================= GETTERS =============================

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getPayoutId()
    {
        return $this->getAttribute(self::PAYOUT_ID);
    }

    public function getReason()
    {
        return $this->getAttribute(self::REASON);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setReason($reason)
    {
        $this->setAttribute(self::REASON, $reason);
    }

    public function setDescription($description)
    {

        $tihs->setAttribute(self::DESCRIPTION, $description);
    }
    // ============================= END SETTERS =============================

}
