<?php

namespace RZP\Models\Settlement\Destination;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Constants\Entity as EntityConstant;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                 = 'id';
    const SETTLEMENT_ID      = 'settlement_id';
    const DESTINATION_TYPE   = 'destination_type';
    const DESTINATION_ID     = 'destination_id';

    protected $entity = EntityConstant::SETTLEMENT_DESTINATION;

    protected $fillable = [
        self::MERCHANT_ID,
        self::SETTLEMENT_ID,
        self::DESTINATION_TYPE,
        self::DESTINATION_ID,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::SETTLEMENT_ID,
        self::DESTINATION_TYPE,
        self::DESTINATION_ID,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::SETTLEMENT_ID,
        self::DESTINATION_TYPE,
        self::DESTINATION_ID,
    ];

    public function settlement()
    {
        return $this->belongsTo(
            'RZP\Models\Settlement\Entity',
            self::SETTLEMENT_ID);
    }

    public function destination()
    {
        return $this->morphTo('destination', self::DESTINATION_TYPE, self::DESTINATION_ID);
    }

    public function getSettlementId()
    {
        return $this->getAttribute(self::SETTLEMENT_ID);
    }

    public function getDestinationType()
    {
        return $this->getAttribute(self::DESTINATION_TYPE);
    }

    public function getDestinationId()
    {
        return $this->getAttribute(self::DESTINATION_ID);
    }
}
