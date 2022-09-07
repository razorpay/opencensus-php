<?php

namespace RZP\Models\Checkout\Order;

use RZP\Exception\BadRequestValidationFailureException;

/**
 * An Enum representing the various statuses the CheckoutOrder entity goes through
 */
final class Status
{
    public const ACTIVE = 'active';
    public const PAID = 'paid';
    public const CLOSED = 'closed';

    public const STATUSES = [
        self::ACTIVE => 1,
        self::PAID => 2,
        self::CLOSED => 3,
    ];

    public static function isValidStatus(string $status): bool
    {
        return array_key_exists($status, self::STATUSES);
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public static function checkStatus(string $status): void
    {
        if (!self::isValidStatus($status))
        {
            throw new BadRequestValidationFailureException('Not a valid status: ' . $status);
        }
    }
}
