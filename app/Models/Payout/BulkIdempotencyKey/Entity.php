<?php

namespace RZP\Models\Payout\BulkIdempotencyKey;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    protected $entity = Constants\Entity::BULK_IDEMPOTENCY_KEYS;

    protected $table = Table::BULK_IDEMPOTENCY_KEYS;

    const ID                   = 'id';
    const MERCHANT_ID          = 'merchant_id';
    const CREATED_AT           = 'created_at';
    const UPDATED_AT           = 'updated_at';
}
