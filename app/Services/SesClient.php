<?php

namespace RZP\Services;

use Swift_Mailer;
use Illuminate\Mai\Mailer;

class SesClient
{
    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.ses');

        $this->app = $app;

        $this->mode = 'test';

        return $this->register();
    }

    public function register()
    {
        $this->app->singleton('ses.mailer', function ($app)
        {
            $swiftMailer =  new Swift_Mailer($app['swift.transport']->driver('ses'));

            $mailer = new Mailer(
                $app['view'], $swiftMailer, $app['events']
            );

            $mailer->setContainer($app);

            if ($app->bound('queue')) {
                $mailer->setQueue($app['queue.connection']);
            }

            return $mailer;
        });
    }
}
