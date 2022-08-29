<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

class TicketStatus
{
    const PROCESSING             = 'Processing';
    const PENDING                = 'Pending';
    const RESOLVED               = 'Resolved';
    const CLOSED                 = 'Closed';
    const WAITING_ON_CUSTOMER    = 'Waiting on Customer';
    const WAITING_ON_THIRD_PARTY = 'Waiting on Third Party';
    const OPEN                   = 'Open';

    static $ticketStatusMapping = [
        2 => self::PROCESSING,
        3 => self::PENDING,
        4 => self::RESOLVED,
        5 => self::CLOSED,
        6 => self::WAITING_ON_CUSTOMER,
        7 => self::WAITING_ON_THIRD_PARTY,
    ];

    static $ticketStatusMappingForNodalStructure = [
        2 => self::OPEN,
        5 => self::CLOSED,
        6 => self::WAITING_ON_CUSTOMER,
    ];

    static $dbTicketStatusMapping = [
        2 => self::OPEN,
        3 => self::PENDING,
        4 => self::RESOLVED,
        5 => self::CLOSED,
        6 => self::WAITING_ON_CUSTOMER,
        7 => self::WAITING_ON_THIRD_PARTY,
    ];

    public static function getStatusMappingForStatusString($ticketStatusString)
    {
        $values = array_flip(self::$ticketStatusMapping);

        return $values[$ticketStatusString];
    }

    public static function gettStatusStringForStatusMapping($ticketStatusTicketStatusMapping)
    {
        return self::$ticketStatusMapping[$ticketStatusTicketStatusMapping];
    }

    public static function getDatabaseStatusMappingForStatusString($ticketStatusString)
    {
        $values = array_flip(self::$dbTicketStatusMapping);

        return $values[$ticketStatusString];
    }

    public static function getStatusStringForDatabaseStatusMapping($ticketStatusTicketStatusMapping)
    {
        return self::$dbTicketStatusMapping[$ticketStatusTicketStatusMapping];
    }

    public static function isValidStatusFromFreshdesk($fdStatus)
    {
        $values = array_flip(self::$dbTicketStatusMapping);

        return array_key_exists($fdStatus, $values);
    }

}
