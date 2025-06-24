<?php

namespace RZP\Models\Payout\SourceRequestIDMapping;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    protected $entity = Constants\Entity::PAYOUT;

    protected $table = 'source_request_id_mapping';

    const ID                   = 'id';
    const SOURCE_TYPE          = 'source_type';
    const SOURCE_ID            = 'source_id';
    const REQUEST_ID           = 'request_id';
    const CREATED_AT           = 'created_at';
    const UPDATED_AT           = 'updated_at';

    public function getSourceType()
    {
        return $this->getAttribute(self::SOURCE_TYPE);
    }

    public function getSourceId()
    {
        return $this->getAttribute(self::SOURCE_ID);
    }

    public function getRequestId()
    {
        return $this->getAttribute(self::REQUEST_ID);
    }
} 