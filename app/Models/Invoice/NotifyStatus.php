<?php

namespace RZP\Models\Invoice;

class NotifyStatus
{
    const PENDING       = 'pending';
    const SENT          = 'sent';
    const DELIVERED     = 'delivered';
    const FAILED        = 'failed';

    public static function isStatusValid($status)
    {
        return (defined(__CLASS__ . '::' . strtoupper($status)));
    }

    public static function checkStatus($status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new \InvalidArgumentException('Not a valid status: ' . $status);
        }
    }
}
