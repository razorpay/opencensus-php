<?php


namespace RZP\Models\VirtualVpaPrefixHistory;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::VIRTUAL_VPA_PREFIX_ID   => 'required|string|size:14',
        Entity::MERCHANT_ID             => 'required|string|size:14',
        Entity::CURRENT_PREFIX          => 'required|string|alpha_num|min:4|max:10',
        Entity::PREVIOUS_PREFIX         => 'sometimes|string|nullable|alpha_num|min:4|max:10',
        Entity::TERMINAL_ID             => 'required|string|size:14',
        Entity::IS_ACTIVE               => 'required|bool|nullable',
        Entity::DEACTIVATED_AT          => 'sometimes|int|nullable',
    ];
}
