<?php


namespace RZP\Models\VirtualVpaPrefix;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const PREFIX                            = 'prefix';
    const TERMINAL_ID                       = 'terminal_id';

    protected $generateIdOnCreate = true;

    protected $primaryKey = self::ID;

    protected $entity = Constants\Entity::VIRTUAL_VPA_PREFIX;
}
