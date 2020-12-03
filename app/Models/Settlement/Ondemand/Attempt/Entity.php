<?php

namespace RZP\Models\Settlement\Ondemand\Attempt;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $entity = 'settlement.ondemand.attempt';

    protected $generateIdOnCreate = true;

    const ID_LENGTH = 14;

    const SETTLEMENT_ONDEMAND_TRANSFER_ID  = 'settlement_ondemand_transfer_id';
    const PAYOUT_ID                        = 'payout_id';
    const STATUS                           = 'status';
    const FAILURE_REASON                   = 'failure_reason';
    const CREATED_AT                       = 'created_at';
    const UPDATED_AT                       = 'updated_at';
    const DELETED_AT                       = 'deleted_at';

    protected $fillable = [
        self::STATUS,
        self::SETTLEMENT_ONDEMAND_TRANSFER_ID,
        self::PAYOUT_ID,
    ];

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getSettlementOndemandTransferId()
    {
        return $this->getAttribute(self::SETTLEMENT_ONDEMAND_TRANSFER_ID);
    }

    public function setStatus($status)
    {
        $currentStatus = $this->getStatus();

        Status::validateStatusUpdate($status, $currentStatus);

        $this->setAttribute(self::STATUS, $status);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function setFailureReason($failureReason)
    {
        $this->setAttribute(self::FAILURE_REASON, $failureReason);
    }

    public function settlementOndemandTransfer()
    {
        return $this->belongsTo(\RZP\Models\Settlement\Ondemand\Transfer\Entity::class);
    }

    public function setPayoutId($id)
    {
        return $this->setAttribute(self::PAYOUT_ID, $id);
    }

}
