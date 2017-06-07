<?php

namespace RZP\Models\Credits\Transaction;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const TRANSACTION_ID = "transaction_id";
    const CREDIT_ID      = "credit_id";
    const CREDITS_USED   = "credits_used";

    protected $entity = "credits_transaction";

    protected $casts = [
        self::CREDITS_USED => 'integer',
    ];

    protected $fillable = [
        self::TRANSACTION_ID,
        self::CREDIT_ID,
        self::CREDITS_USED,
    ];

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function credits()
    {
        return $this->belongsTo('RZP\Models\Merchant\Credits\Entity');
    }
}
