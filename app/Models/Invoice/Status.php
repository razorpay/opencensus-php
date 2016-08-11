<?php

namespace RZP\Models\Invoice;

class Status
{
    const DRAFT         = 'draft';
    const PENDING       = 'pending';
    const CREATED       = 'created';
    const PAID          = 'paid';
    const EXPIRED       = 'expired';
    const DELETED       = 'deleted';

    public static function isStatusValid($status)
    {
        return (defined(Status::class . '::' . strtoupper($status)));
    }
}