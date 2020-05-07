<?php


namespace RZP\Models\VirtualVpaPrefixHistory;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\VirtualVpaPrefix;

class Entity extends Base\PublicEntity
{
    const VIRTUAL_VPA_PREFIX_ID             = 'virtual_vpa_prefix_id';
    const CURRENT_PREFIX                    = 'current_prefix';
    const PREVIOUS_PREFIX                   = 'previous_prefix';
    const TERMINAL_ID                       = 'terminal_id';
    const IS_ACTIVE                         = 'is_active';
    const DEACTIVATED_AT                    = 'deactivated_at';

    protected $fillable = [
        self::VIRTUAL_VPA_PREFIX_ID,
        self::MERCHANT_ID,
        self::CURRENT_PREFIX,
        self::PREVIOUS_PREFIX,
        self::TERMINAL_ID,
        self::IS_ACTIVE,
        self::DEACTIVATED_AT,
    ];

    protected $visible = [
        self::ID,
        self::VIRTUAL_VPA_PREFIX_ID,
        self::MERCHANT_ID,
        self::CURRENT_PREFIX,
        self::PREVIOUS_PREFIX,
        self::TERMINAL_ID,
        self::IS_ACTIVE,
        self::DEACTIVATED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::VIRTUAL_VPA_PREFIX_ID,
        self::CURRENT_PREFIX,
        self::PREVIOUS_PREFIX,
        self::IS_ACTIVE,
        self::DEACTIVATED_AT,
    ];

    protected static $sign = 'vvph';

    protected $entity = Constants\Entity::VIRTUAL_VPA_PREFIX_HISTORY;

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;

    public function buildEntity(VirtualVpaPrefix\Entity $virtualVpaPrefix, array $params = [])
    {
        $virtualVpaPrefixHistoryData = [
            self::VIRTUAL_VPA_PREFIX_ID     => $virtualVpaPrefix->getId(),
            self::MERCHANT_ID               => $virtualVpaPrefix->getMerchantId(),
            self::CURRENT_PREFIX            => $virtualVpaPrefix->getPrefix(),
            self::PREVIOUS_PREFIX           => isset($params[Entity::PREVIOUS_PREFIX]) ? $params[Entity::PREVIOUS_PREFIX] : null,
            self::TERMINAL_ID               => $virtualVpaPrefix->getTerminalId(),
            self::IS_ACTIVE                 => true,
        ];

        return parent::build($virtualVpaPrefixHistoryData);
    }
}
