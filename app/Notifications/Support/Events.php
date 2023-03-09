<?php

namespace RZP\Notifications\Support;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Events
{
    // below events need to be added in SUPPORTED_CHANNELS_FOR_EVENTS as applicable
    const TICKET_CREATED            = 'TICKET_CREATED';
    const TICKET_DELAY_UPDATE_24HRS = 'TICKET_DELAY_UPDATE_24HRS';
    const TICKET_DELAY_UPDATE_72HRS = 'TICKET_DELAY_UPDATE_72HRS';
    const TICKET_DETAILS_PENDING    = 'TICKET_DETAILS_PENDING';
    const TICKET_RESOLVED           = 'TICKET_RESOLVED';
    const TICKET_REOPENED           = 'TICKET_REOPENED';
    const AGENT_TICKET_CREATED      = 'AGENT_TICKET_CREATED';

    const SMS_TEMPLATES = [
        self::TICKET_CREATED            => 'sms.support.ticket_created',
        self::TICKET_DELAY_UPDATE_24HRS => 'sms.support.ticket_delay_update_24hrs',
        self::TICKET_DELAY_UPDATE_72HRS => 'sms.support.ticket_delay_update_72hrs',
        self::TICKET_DETAILS_PENDING    => 'sms.support.ticket_details_pending',
        self::TICKET_RESOLVED           => 'sms.support.ticket_resolved',
        self::TICKET_REOPENED           => 'sms.support.ticket_reopened',
        self::AGENT_TICKET_CREATED      => 'sms.support.agent_ticket_created'
    ];

    // The below text messages have to exactly match what is registered in the whatsapp messaging providers portal
    // Even spacing differences will lead to delivery failures. So test all changes in these templates
    const WHATSAPP_TEMPLATES = [
        self::TICKET_CREATED      => [
            'Your support ticket {ticket_id} has been registered. We are working to resolve your issue and will update you within 3 working days. ',
            'You can track the support ticket by logging in to the dashboard : {url} ',
            'Team Razorpay',
        ],
        self::TICKET_DELAY_UPDATE_24HRS => [
            'It is taking more time than usual to resolve your issue {ticket_id}. We are working on it and will update you within the next 24 hrs. ',
            'You can track the support ticket by logging in to the dashboard : {url} ',
            'Team Razorpay'
        ],
        self::TICKET_DELAY_UPDATE_72HRS => [
            'There is a short delay in resolving your issue {ticket_id}. We are working on it and will update you within the next 72 hrs. ',
            'You can track the support ticket by logging in to the dashboard : {url} ',
            'Team Razorpay',
        ],
        self::TICKET_DETAILS_PENDING => [
            'Action required: ',
            'We need a few more details from you on the support ticket {ticket_id}. ',
            'You can track the support ticket and submit the required details by logging in to the dashboard : {url} ',
            'Team Razorpay',
        ],
        self::TICKET_RESOLVED => [
            'Your issue has been resolved  {ticket_id}. ',
            'You can track the support ticket by logging into the dashboard : {url} ',
            'Team Razorpay',
        ],
        self::TICKET_REOPENED => [
            'Your support ticket {ticket_id} has been reopened as per your request. We will take it up on priority and get back to you within 24 hrs. ',
            'You can track the support ticket by logging in to the dashboard : {url} ',
            'Team Razorpay',
        ],
        self::AGENT_TICKET_CREATED => [
            'Action required: ',
            'We need a few more details from you. Respond sooner for faster resolution. ',
            'You can track the support ticket and submit the required details by logging in to the dashboard : {url} ',
            'Team Razorpay',
        ],
      ];

    public static function isValid($eventString): bool
    {
        return defined(get_class() . '::' . strtoupper($eventString));
    }

    public static function validateEvent($eventString)
    {
        if (self::isValid($eventString) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, 'event');
        }
    }
}
