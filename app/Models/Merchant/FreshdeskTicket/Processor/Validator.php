<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use RZP\Base;
use RZP\Models\Merchant\FreshdeskTicket;

class Validator extends FreshdeskTicket\Validator
{
    protected static $supportDashboardTicketReplyRules = [
        FreshdeskTicket\Constants::TICKET_ID        => 'required',
        FreshdeskTicket\Constants::PRIORITY         => 'required|custom:priorityString',
        FreshdeskTicket\Constants::CUSTOM_FIELDS    => 'required|array',
    ];
}
