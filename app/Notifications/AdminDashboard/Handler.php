<?php

namespace RZP\Notifications\AdminDashboard;

use RZP\Notifications\Channel;
use RZP\Notifications\BaseHandler;
use RZP\Exception\InvalidArgumentException;

class Handler extends BaseHandler
{
    const SUPPORTED_CHANNELS_FOR_EVENTS = [
        Events::NEEDS_CLARIFICATION => [Channel::WHATSAPP]
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
