<?php

namespace RZP\Models\BharatQr;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::VISA_IDENTIFIER         => 'required|alpha_num',
        Entity::MASTER_CARD_IDENTIFIER  => 'required|alpha_num',
        Entity::AMOUNT                  => 'sometimes|integer',
        Entity::METHOD                  => 'required|max:30',
        Entity::QR_STRING               => 'required|string|max:255',
    ];
}
