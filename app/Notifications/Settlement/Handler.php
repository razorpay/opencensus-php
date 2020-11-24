<?php

namespace RZP\Notifications\Settlement;

use RZP\Notifications\Channel;
use RZP\Notifications\BaseHandler;

class Handler extends BaseHandler
{
    const SUPPORTED_CHANNELS_FOR_EVENTS = [
        Events::PROCESSED  => [Channel::SMS, Channel::WHATSAPP],
        Events::FAILED     => [Channel::SMS, Channel::WHATSAPP],
    ];

    protected function getSupportedchannels(string $event)
    {
        if(isset(self::SUPPORTED_CHANNELS_FOR_EVENTS[$event]))
        {
            return self::SUPPORTED_CHANNELS_FOR_EVENTS[$event];
        }
        // TODO: throw exception
    }
}
