<?php

namespace RZP\Models\Settlement\Details;

use RZP\Models\Base;
use RZP\Models\Settlement\Details;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::TYPE            => 'required|max:20',
        Entity::COUNT           => 'required|integer',
        Entity::AMOUNT          => 'required|integer',
        Entity::DESCRIPTION     => 'sometimes'
    );

    protected static $validateRules = array(
        Entity::TYPE,
    );

    protected function ValidateType($input)
    {
        $type = $input[Entity::TYPE];

        Details\Type::validateType($type);
    }
}