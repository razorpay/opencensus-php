<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice\Type;

class Event
{
    const INVOICE_ISSUED   = 'invoice_issued';
    const INVOICE_EXPIRED  = 'invoice_expired';
    const INVOICE_EXPIRING = 'invoice_expiring';

    const MAIL_SUBJECT_TEMPLATES = [
        self::INVOICE_ISSUED => [
            Type::LINK    => ' Payment requested by %s',
            Type::ECOD    => ' Payment requested by %s',
            Type::INVOICE => ' Invoice from %s',
        ],
        self::INVOICE_EXPIRED => [
            Type::LINK    => ' Payment requested from %s has expired',
            Type::ECOD    => ' Payment requested from %s has expired',
            Type::INVOICE => ' Invoice from %s has expired',
        ],
        self::INVOICE_EXPIRING => [
            Type::LINK    => ' Payment request from %s is expiring',
            Type::ECOD    => ' Payment request from %s is expiring',
            Type::INVOICE => ' Invoice from %s is expiring',
        ],
    ];
}
