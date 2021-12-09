<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

class TicketCreatedBy
{
    const MERCHANT             = 'merchant';
    const AGENT                = 'agent';

    public static function isValidCreatedBy($fdTicketCreatedBy)
    {
        $key = __CLASS__ . '::' . strtoupper($fdTicketCreatedBy);

        return ((defined($key) === true) and (constant($key) === $fdTicketCreatedBy));
    }
}