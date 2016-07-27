<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::URL     => 'required|string|url|max:255|min:3',
        Entity::EVENTS  => 'required|array',
        Entity::SECRET  => 'sometimes|string|max:255',
    );

    protected static $createValidators = array('events', 'url');

    protected static $editRules = array(
        Entity::URL     => 'sometimes|string|url|max:255|min:3',
        Entity::EVENTS  => 'sometimes|array',
        Entity::ACTIVE  => 'sometimes|in:0,1',
        Entity::SECRET  => 'sometimes|string|max:255',
    );

    protected static $editValidators = array('events', 'url');

    protected function validateUrl($input)
    {
        if (isset($input[Entity::URL]) === false)
        {
            return;
        }

        $components = parse_url($input[Entity::URL]);

        if (isset($components['scheme']) === true)
        {
            $scheme = strtolower($components['scheme']);

            if (($scheme !== 'http') and
                ($scheme !== 'https'))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Only http or https schemes are allowed in webhook url.');
            }
        }

        if (isset($components['port']) === true)
        {
            $port = $components['port'];

            if (($port !== '80') and
                ($port !== '443'))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Only 80 or 443 port is currently allowed in webhook url.');
            }
        }
    }

    protected function validateEvents($input)
    {
        $events = $input[Entity::EVENTS];

        foreach ($events as $event => $value)
        {
            if (Event::validateEventName($event) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Not a valid event name: ' . $event,
                    Entity::EVENTS);
            }

            if (($value !== '0') and ($value !== '1'))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Not a valid event value',
                    Entity::EVENTS);
            }
        }
    }
}
