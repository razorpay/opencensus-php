<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use RZP\Models\Merchant\FreshdeskTicket\Entity;
use RZP\Models\Merchant\FreshdeskTicket\Constants;
use RZP\Models\Merchant\FreshdeskTicket\Validator as BaseValidator;


class Validator extends BaseValidator
{
    protected static $supportTicketFirstAgentReplyRules = [
        Constants::TICKET_ID        => 'required',
        Constants::PRIORITY         => 'required|custom:priorityString',
        Constants::CUSTOM_FIELDS    => 'required|array',
    ];

    protected static $ticketCreateCallbackRules = [
        Entity::TICKET_ID                                         => 'required',
        Entity::MERCHANT_ID                                       => 'required',
        Entity::TYPE                                              => 'required|custom',
        Entity::TICKET_DETAILS                                    => 'required|array',
        Entity::TICKET_DETAILS . '.' . Constants::FD_INSTANCE     => 'required|custom:fd_instance',
        Entity::TICKET_DETAILS . '.' . Constants::FR_DUE_BY       => 'required',
    ];
}
