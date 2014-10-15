<?php

namespace Dashboard;

class Notification
{
    public static function send($resource, $payload)
    {
        $app = \App::getFacadeRoot();
        $app['dashboard']->queueRecord($resource, $payload);
    }
}