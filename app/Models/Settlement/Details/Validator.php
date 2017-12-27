<?php

namespace RZP\Models\Settlement\Details;

use RZP\Base;
use RZP\Models\Settlement\Details;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::COMPONENT       => 'required|max:20',
        Entity::TYPE            => 'required|in:debit,credit',
        Entity::COUNT           => 'sometimes|nullable|integer',
        Entity::AMOUNT          => 'required|numeric|min:0',
        Entity::DESCRIPTION     => 'sometimes|max:255'
    );

    protected static $validateRules = array(
        Entity::COMPONENT,
        Entity::COUNT,
    );

    protected function validateComponent($input)
    {
        $component = $input[Entity::COMPONENT];

        Details\Component::validateComponent($type);
    }

    protected function validateCount($input)
    {
        $count = $input[Entity::COUNT];

        if (($type === Component::FEE) or
            ($type === Component::TAX))
        {
            assert ($count === null);
        }
        else
        {
            assert (is_int($count));
        }
    }
}