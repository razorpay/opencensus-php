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

    const SMS_TEMPLATES = [
        self::TICKET_CREATED            => 'sms.support.ticket_created',
        self::TICKET_DELAY_UPDATE_24HRS => 'sms.support.ticket_delay_update_24hrs',
        self::TICKET_DELAY_UPDATE_72HRS => 'sms.support.ticket_delay_update_72hrs',
        self::TICKET_DETAILS_PENDING    => 'sms.support.ticket_details_pending',
        self::TICKET_RESOLVED           => 'sms.support.ticket_resolved',
        self::TICKET_REOPENED           => 'sms.support.ticket_reopened'
    ];

    // The below text messages have to exactly match what is registered in the whatsapp messaging providers portal
    // Even spacing differences will lead to delivery failures. So test all changes in these templates
    const WHATSAPP_TEMPLATES = [
        self::TICKET_CREATED      => [
            'Hi, ',
            'Thank you for reaching out. This is to inform you that your ticket number {ticket_id} has been registered. Our team is working on your request and will get back to you within 3 working days. You can track your ticket updates by logging into the dashboard : {url} ',
            'Team Razorpay',
        ],
        self::TICKET_DELAY_UPDATE_24HRS => [
            'Hi, ',
            'We are sorry about the delay regarding your ticket {ticket_id}. We will revert back to you with a resolution for the same in the next 24 hrs. Please bear with us. You can track your ticket updates by logging into the dashboard : {url} ',
            'Team Razorpay',
        ],
        self::TICKET_DELAY_UPDATE_72HRS => [
            'Hi, ',
            'We are sorry about the delay regarding your ticket {ticket_id}. We will revert back to you with a resolution for the same in the next 72 hrs. Please bear with us. You can track your ticket updates by logging into the dashboard : {url} ',
            'Team Razorpay',
        ],
        self::TICKET_DETAILS_PENDING => [
            'Hi, ',
            'We require a few details from you on the ticket {ticket_id}. Request you to check and respond with the details for us to resolve the concern raised. You can track your ticket updates by logging into the dashboard : {url} ',
            'Team Razorpay',
        ],
        self::TICKET_RESOLVED => [
            'Hi, ',
            'Your issue regarding the ticket {ticket_id} has been resolved and a response has been sent over to you. If you are not satisfied with the resolution provided, feel free to reopen the ticket by replying to the same ticket. You can track your ticket updates by logging into the dashboard : {url} ',
            'Team Razorpay',
        ],
        self::TICKET_REOPENED => [
            'Hi, ',
            'We believe that your issue regarding the ticket {ticket_id} is still not resolved. Your ticket has been reopened and our team will take it up on priority and get back to you within 24 hrs. You can track your ticket updates by logging into the dashboard : {url} ',
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
