<?php

namespace RZP\Notifications\Support;

use RZP\Notifications\Channel;
use RZP\Notifications\BaseHandler;
use RZP\Exception\InvalidArgumentException;

class Handler extends BaseHandler
{
    const SUPPORTED_CHANNELS_FOR_EVENTS = [
        Events::TICKET_CREATED            => [Channel::SMS, Channel::WHATSAPP],
        Events::TICKET_DELAY_UPDATE_24HRS => [Channel::SMS, Channel::WHATSAPP],
        Events::TICKET_DELAY_UPDATE_72HRS => [Channel::SMS, Channel::WHATSAPP],
        Events::TICKET_DETAILS_PENDING    => [Channel::SMS, Channel::WHATSAPP],
        Events::TICKET_RESOLVED           => [Channel::SMS, Channel::WHATSAPP],
        Events::TICKET_REOPENED           => [Channel::SMS, Channel::WHATSAPP],
        Events::AGENT_TICKET_CREATED      => [Channel::SMS, Channel::WHATSAPP],
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
