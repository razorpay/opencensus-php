<?php

namespace RZP\Models\Invoice;

class Status
{
    const PENDING       = 'pending';
    const CREATED       = 'created';
    const SENT          = 'sent';
    const DELIVERED     = 'delivered';
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