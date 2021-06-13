<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use RZP\Models\Merchant\FreshdeskTicket\Entity;
use RZP\Models\Merchant\FreshdeskTicket\Constants;
use RZP\Notifications\Support as SupportNotifications;
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
    ];

    protected static $websiteCheckerReplyRules = [
        Entity::TICKET_ID   => 'required',
    ];

    protected static $notifyMerchantRules = [
        Entity::TICKET_ID             => 'required',
        Constants::NOTIFICATION_EVENT => 'required|custom:NotificationEvent',
        Constants::FD_INSTANCE        => 'required|custom:fd_instance',
    ];

    protected function validateNotificationEvent($attribute, $value)
    {
        SupportNotifications\Events::validateEvent($value);
    }
}
