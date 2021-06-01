<?php

namespace RZP\Notifications\Support;

use RZP\Notifications\Channel;
use RZP\Notifications\BaseHandler;
use RZP\Exception\InvalidArgumentException;

class Handler extends BaseHandler
{
    const SUPPORTED_CHANNELS_FOR_EVENTS = [
        Events::TICKET_CREATED            => [Channel::WHATSAPP],
        Events::TICKET_DELAY_UPDATE_24HRS => [Channel::WHATSAPP],
        Events::TICKET_DELAY_UPDATE_72HRS => [Channel::WHATSAPP],
        Events::TICKET_DETAILS_PENDING    => [Channel::WHATSAPP],
        Events::TICKET_RESOLVED           => [Channel::WHATSAPP],
        Events::TICKET_REOPENED           => [Channel::WHATSAPP],
    ];

    protected function getSupportedchannels(string $event)
    {
        if(isset(self::SUPPORTED_CHANNELS_FOR_EVENTS[$event]) === true)
        {
            return self::SUPPORTED_CHANNELS_FOR_EVENTS[$event];
        }

        $message = $event . ' is not a valid event';

        throw new InvalidArgumentException($message);
    }
}
