<?php

namespace RZP\Models\Event;

use RZP\Base;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Webhook;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    /** {@inheritDoc} */
    protected static $createRules = [
        Entity::EVENT         => 'required|string|custom',
        Entity::ACCOUNT_ID    => 'required|public_id',
        Entity::CONTAINS      => 'required|sequential_array|min:1|custom',
        Entity::CONTAINS.'.*' => 'required|string',
        Entity::PAYLOAD       => 'required|associative_array',
        Entity::CREATED_AT    => 'required|epoch',
    ];

    /** {@inheritDoc} */
    protected static $createValidators = [
        // Requires access to complete input to assert that it contains all
        // entities listed in 'contains' list.
        Entity::PAYLOAD,
    ];

    public function validateEvent($attribute, $value)
    {
        if (Webhook\Event::validateEventName($value) === false)
        {
            throw new BadRequestValidationFailureException("Invalid event name: {$value}");
        }
    }

    public function validateContains($attribute, $value)
    {
        foreach ($value as $entityName)
        {
            E::validateEntityOrFailPublic($entityName);
        }
    }

    public function validatePayload(array $input)
    {
        // Asserts that payload contains all entities listed in 'contains' list.
        $expected = $input[Entity::CONTAINS];
        $actual   = array_keys($input[Entity::PAYLOAD]);
        sort($expected);
        sort($actual);

        if ($expected !== $actual)
        {
            throw new BadRequestValidationFailureException(
                'Invalid event payload, expected contents strictly for '.implode(', ', $expected)
            );
        }
    }
}
