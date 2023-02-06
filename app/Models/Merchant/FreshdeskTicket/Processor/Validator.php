<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use RZP\Exception;
use RZP\Models\Merchant\FreshdeskTicket\Entity;
use RZP\Models\Merchant\FreshdeskTicket\Constants;
use RZP\Models\Merchant\FreshdeskTicket\TicketCreatedBy;
use RZP\Models\Merchant\FreshdeskTicket\TicketStatus;
use RZP\Notifications\Support as SupportNotifications;
use RZP\Models\Merchant\FreshdeskTicket\Validator as BaseValidator;


class Validator extends BaseValidator
{
    protected static $supportTicketFirstAgentReplyRules = [
        // Make ticket ID required and string in future
        Constants::TICKET_ID        => 'required',
        Constants::PRIORITY         => 'required|custom:priorityString',
        Constants::CUSTOM_FIELDS    => 'required|array',
    ];

    protected static $ticketCreateCallbackRules = [
        // Make ticket ID required and string in future
        Entity::TICKET_ID                                         => 'required',
        Entity::MERCHANT_ID                                       => 'required',
        Entity::TYPE                                              => 'required|custom',
        Entity::TICKET_DETAILS                                    => 'required|array',
        Entity::TICKET_DETAILS . '.' . Constants::FD_INSTANCE     => 'required|custom:fd_instance',
        Entity::CREATED_BY                                        => 'sometimes|custom:created_by',
        Entity::STATUS                                            => 'sometimes|custom:status',
    ];

    protected static $ticketStatusUpdateCallbackRules = [
        // Make ticket ID required and string in future
        Entity::TICKET_ID                                         => 'required',
        Entity::MERCHANT_ID                                       => 'required',
        Entity::TYPE                                              => 'required|custom',
        Entity::STATUS                                            => 'required|custom:status',
        Entity::TICKET_DETAILS                                    => 'required|array',
        Entity::TICKET_DETAILS . '.' . Constants::FD_INSTANCE     => 'required|custom:fd_instance',
    ];

    protected static $websiteCheckerReplyRules = [
        // Make ticket ID required and string in future
        Entity::TICKET_ID   => 'required',
    ];

    protected static $notifyMerchantRules = [
        // Make ticket ID required and string in future
        Entity::TICKET_ID             => 'required',
        Constants::NOTIFICATION_EVENT => 'required|custom:NotificationEvent',
        Constants::FD_INSTANCE        => 'required|custom:fd_instance',
        Constants::CUSTOM_FIELDS      => 'required|array',
    ];

    protected function validateNotificationEvent($attribute, $value)
    {
        SupportNotifications\Events::validateEvent($value);
    }

    protected static $getAgentCreatedTicketRules = [];

    protected function validateCreatedBy($attribute, string $createdBy)
    {
        if (TicketCreatedBy::isValidCreatedBy($createdBy) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid fd ticket created by: ' . $createdBy,
                Entity::CREATED_BY
            );
        }
    }

    protected function validateStatus($attribute, string $status)
    {
        if (TicketStatus::isValidStatusFromFreshdesk($status) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid fd ticket Status: ' . $status,
                Entity::STATUS
            );
        }
    }
}
