<?php

namespace RZP\Models\Pricing;

use RZP\Models\Settlement;
use RZP\Exception\BadRequestValidationFailureException;

class SourceChannel
{
    //
    // For now, these channels are used to distinguish between online payments and in-person payments (POS device payments)
    const IN_PERSON = 'in_person';
    const ONLINE    = 'online';

    /**
     * This array has a list of acceptable source channels values
     *
     * @var array
     */
    protected static array $sourceChannels = [
        self::IN_PERSON,
        self::ONLINE,
    ];

    public static function isValid(string $channel): bool
    {
        $key = __CLASS__ . '::' . strtoupper($channel);

        return ((defined($key) === true) and (constant($key) === $channel));
    }

    public static function validate(string $channel)
    {
        if (self::isValid($channel) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid source channel: ' . $channel);
        }
    }

    public static function getAll(): array
    {
        return self::$sourceChannels;
    }
}
