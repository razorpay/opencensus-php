<?php


namespace RZP\Models\VirtualVpaPrefix;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $validateRules = [
        Entity::PREFIX              => 'required|string|alpha_num|min:4|max:10',
    ];

    protected static $createRules = [
        Entity::MERCHANT_ID         => 'filled|string|size:14',
        Entity::PREFIX              => 'required|string|alpha_num|min:4|max:10',
        Entity::TERMINAL_ID         => 'filled|string|size:14',
    ];

    protected static $editRules = [
        Entity::PREFIX              => 'required|string|alpha_num|min:4|max:10',
        Entity::TERMINAL_ID         => 'filled|string|size:14',
    ];
}
