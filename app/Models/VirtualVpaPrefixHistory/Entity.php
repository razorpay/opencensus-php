<?php


namespace RZP\Models\VirtualVpaPrefixHistory;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const VIRTUAL_VPA_PREFIX_ID             = 'virtual_vpa_prefix_id';
    const CURRENT_PREFIX                    = 'current_prefix';
    const PREVIOUS_PREFIX                   = 'previous_prefix';
    const TERMINAL_ID                       = 'terminal_id';
    const IS_ACTIVE                         = 'is_active';
    const DEACTIVATED_AT                    = 'deactivated_at';

    protected $generateIdOnCreate = true;

    protected $primaryKey = self::ID;

    protected $entity = Constants\Entity::VIRTUAL_VPA_PREFIX_HISTORY;
}
