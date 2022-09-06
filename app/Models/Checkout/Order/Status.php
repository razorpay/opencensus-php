<?php

namespace RZP\Models\Checkout\Order;

/**
 * An Enum representing the various statuses the CheckoutOrder entity goes through
 */
final class Status
{
    public const ACTIVE = 'active';
    public const PAID = 'paid';
    public const CLOSED = 'closed';
}
