<?php

namespace RZP\Models\Settlement\Details;

use RZP\Models\Base;
use RZP\Models\Settlement\Details;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::TYPE            => 'required|max:20',
        Entity::COUNT           => 'sometimes|integer',
        Entity::AMOUNT          => 'required|integer',
        Entity::DESCRIPTION     => 'sometimes|max:255'
    );

    protected static $validateRules = array(
        Entity::TYPE,
        Entity::COUNT,
    );

    protected function validateType($input)
    {
        $type = $input[Entity::TYPE];

        Details\Type::validateType($type);
    }

    protected function validateCount($input)
    {
        $count = $input[Entity::COUNT];

        if (($type === Type::FEE) or
            ($type === Type::SERVICE_TAX))
        {
            assert ($count === null);
        }
        else
        {
            assert (is_int($count));
        }
    }
}