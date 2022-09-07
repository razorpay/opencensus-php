<?php

namespace RZP\Models\Checkout\Order;

use RZP\Exception\BadRequestValidationFailureException;

/**
 * An Enum representing the various reasons why the Checkout\Order entity was closed.
 */
final class CloseReason
{
    public const EXPIRED = 'expired'; // Reached expire_at time
    public const FAILED_IN_APP = 'failed_in_app'; // Payment failed in UPI app
    public const MERCHANT_TIMEOUT = 'merchant_timeout'; // Checkout timed out based on timeout config passed by
                                                        // merchant during std. checkout initialisation
    public const MONEY_DEDUCTED = 'money_deducted'; // Money got deducted but payment is still processing
    public const OPT_OUT = 'opt_out'; // Payment made using a different payment method
    public const PAID = 'paid';

    public const REASONS = [
        self::EXPIRED => 1,
        self::FAILED_IN_APP => 2,
        self::MERCHANT_TIMEOUT => 3,
        self::MONEY_DEDUCTED => 4,
        self::OPT_OUT => 5,
        self::PAID => 6,
    ];

    public static function isValidCloseReason(string $closeReason): bool
    {
        return array_key_exists($closeReason, self::REASONS);
    }

    public static function checkCloseReason(string $closeReason)
    {
        if (self::isValidCloseReason($closeReason) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid closing reason: ' . $closeReason);
        }
    }
}
