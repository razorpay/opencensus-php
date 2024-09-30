<?php

namespace RZP\Services\EzetapNotification\Mock;

use RZP\Models\Event\Entity;
use RZP\Services\EzetapNotification\EzetapNotification as BaseEzetapNotification;


class EzetapNotification extends BaseEzetapNotification
{
    const TIMEOUT = 60;

    const CONNECT_TIMEOUT = 10;

    const HEADERS = 'headers';
    const CONTENT = 'content';
    const OPTIONS = 'options';
    const STATUS_CODE = 'status_code';

    protected $app;

    protected $trace;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config'];

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

    }

    public function sendEzetapRequest(
        Entity $event,
        int $timeout = self::TIMEOUT,
        int $connectTimeout = self::CONNECT_TIMEOUT)
    {

        if ($this->isEzetapNotificationEvent($event) === true)
        {
            $this->actualCall($event);
        }

        return null;
    }

    public function actualCall(Entity $event)
    {
        return null;
    }

}
