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

    // The below text messages have to exactly match what is registered in the whatsapp messaging providers portal
    // Even spacing differences will lead to delivery failures. So test all changes in these templates
    const WHATSAPP_TEMPLATES = [
        self::TICKET_CREATED      => [
            'Hi, ',
            'Thank you for reaching out. This is to inform you that your ticket number {ticket_id} has been registered. Our team is working on your request and will get back to you within 3 working days. ',
            'Team Razorpay',
        ],
        self::TICKET_DELAY_UPDATE_24HRS => [
            'Hi, ',
            'We are sorry about the delay regarding your ticket {ticket_id}. We will revert back to you with a resolution for the same in the next 24 hrs. Please bear with us. ',
            'Team Razorpay',
        ],
        self::TICKET_DELAY_UPDATE_72HRS => [
            'Hi, ',
            'We are sorry about the delay regarding your ticket {ticket_id}. We will revert back to you with a resolution for the same in the next 72 hrs. Please bear with us.',
            'Team Razorpay',
        ],
        self::TICKET_DETAILS_PENDING => [
            'Hi, ',
            'We require a few details from your end on the ticket {ticket_id}. Request you to check your email and respond with the details for us to check and resolve the concern raised.',
            'Team Razorpay',
        ],
        self::TICKET_RESOLVED => [
            'Hi, ',
            'Your issue regarding the ticket {ticket_id} has been resolved and a response has been sent over to your email. If you are not satisfied with the resolution provided feel free to reopen the ticket by replying to the same email. ',
            'Team Razorpay',
        ],
        self::TICKET_REOPENED => [
            'Hi, ',
            'We believe that your issue regarding the ticket {ticket_id} is still not resolved. Your ticket has been reopened and our team will take it up on priority and get back to you within 24 hrs.',
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
