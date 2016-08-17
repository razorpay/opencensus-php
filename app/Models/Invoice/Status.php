<?php

namespace RZP\Models\Invoice;

class Status
{
    // ------ Email/SMS Statuses -----------
    const PENDING       = 'pending';
    const SENT          = 'sent';
    const DELIVERED     = 'delivered';
    const FAILED        = 'failed';

    // -------- Invoice Statuses -----------
    const CREATED       = 'created';
    const PAID          = 'paid';
    const EXPIRED       = 'expired';
    const DELETED       = 'deleted';

    public static function isStatusValid($status)
    {
        return (defined(Status::class . '::' . strtoupper($status)));
    }

    public static function checkStatus($status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new \InvalidArgumentException('Not a valid status: ' . $status);
        }
    }
}