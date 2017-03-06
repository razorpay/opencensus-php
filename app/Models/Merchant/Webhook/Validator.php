<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Merchant;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::URL     => 'required|string|url|max:255|min:3',
        Entity::EVENTS  => 'required|array',
        Entity::SECRET  => 'sometimes|string|max:255',
    );

    protected static $createValidators = array('events', 'url');

    protected static $editRules = array(
        Entity::URL     => 'sometimes|filled|string|url|max:255|min:3',
        Entity::EVENTS  => 'sometimes|array',
        Entity::ACTIVE  => 'sometimes|in:0,1',
        Entity::SECRET  => 'sometimes|string|max:255',
    );

    protected static $editValidators = array('events', 'url');

    // Refer: http://www-archive.mozilla.org/projects/netlib/PortBanning.html#portlist
    const RESTRICTED_PORTS = [
        1, 7, 9, 11, 13, 15, 17, 19, 20, 21, 22, 23, 25, 37, 42, 43,
        53, 77, 79, 87, 95, 101, 102, 103, 104, 109, 110, 111, 113,
        115, 117, 119, 123, 135, 139, 143, 179, 389, 465, 512, 513,
        514, 515, 526, 530, 531, 532, 540, 556, 563, 587, 601, 636,
        993, 995, 2049, 4045, 6000
    ];

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

            // Not strict check on purpose.
            if (in_array($port, self::RESTRICTED_PORTS))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The provided port is restricted and cannot be used in a webhook URL.');
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
