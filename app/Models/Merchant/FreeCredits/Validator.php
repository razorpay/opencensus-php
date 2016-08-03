<?php

namespace RZP\Models\Merchant\FreeCredits;

use RZP\Models\Base;
use RZP\Models\Merchant\FreeCredits;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::CAMPAIGN                    => 'required|alpha_dash|max:255',
        Entity::CREDITS                     => 'required|integer',
        Entity::MERCHANT_ID                 => 'sometimes|alpha_num',
        Entity::NOTES                       => 'sometimes|notes',
    );

    protected static $editRules = array(
        Entity::CREDITS                     => 'sometimes|integer',
    );
}
