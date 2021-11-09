<?php


namespace RZP\Notifications\Dashboard;

use RZP\Notifications\BaseHandler;
use RZP\Models\Merchant\Constants;

class Handler extends BaseHandler
{
    private $event;

    private $merchant;

    public function __construct(array $args)
    {
        parent::__construct($args);

        $this->merchant = $args[Constants::MERCHANT];

        $this->event = $args[Events::EVENT];
    }

    public function send()
    {
        $this->sendForEvent($this->event, false);
    }

    protected function getSupportedchannels(string $event)
    {
        return Events::SUPPORTED_CHANNELS_FOR_EVENTS[$event];
    }
}
