<?php

namespace RZP\Models\Merchant\Credits\Transaction;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const TRANSACTION_ID = 'transaction_id';
    const CREDITS_ID     = 'credits_id';
    const CREDITS_USED   = 'credits_used';
    const ENTITY_ID      = 'entity_id';
    const ENTITY_TYPE    = 'entity_type';

    protected $entity = 'credit_transaction';

    protected $generateIdOnCreate = true;

    protected $casts = [
        self::CREDITS_USED => 'integer',
    ];

    protected $fillable = [
        self::CREDITS_USED,
    ];

    protected $ignoredRelations = [
        'entity',
    ];

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function credits()
    {
        return $this->belongsTo('RZP\Models\Merchant\Credits\Entity');
    }

    public function getCreditsUsed()
    {
        return $this->getAttribute(self::CREDITS_USED);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function updateCreditsUsed(int $used)
    {
        $usedCount = $this->getCreditsUsed() + $used;

        $this->setAttribute(self::CREDITS_USED, $used);
    }
}
