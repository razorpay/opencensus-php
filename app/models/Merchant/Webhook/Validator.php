<?php

namespace Models\Merchant\Webhook;

use EE\Exception;
use Models\Base;
use Models\Merchant;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::URL     => 'required|string|url|max:255',
        Entity::EVENTS  => 'required|array',
    );

    protected static $createValidators = array('events');

    protected static $editRules = array(
        Entity::URL     => 'sometimes|string|url|max:255',
        Entity::EVENTS  => 'sometimes|array',
        Entity::ACTIVE  => 'sometimes|in:0,1',
    );

    protected function validateEvents($input)
    {
        $events = $input[Entity::EVENTS];

        foreach ($events as $event => $value)
        {
            if (Name::validateEventName($event) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Not a valid event name: ' . $event,
                    Entity::EVENTS);
            }

            if (($value !== '0') and
                ($value !== '1'))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Not a valid event value',
                    Entity::EVENTS);
            }
        }
    }
}
