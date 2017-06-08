<?php

namespace RZP\Services;

use RZP\Services\Mailer;
use Illuminate\Mail\MailServiceProvider as LaravelMailServiceProvider;

class MailServiceProvider extends LaravelMailServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSwiftMailer();

        $this->app->singleton('mailer', function ($app)
        {
            // Once we have create the mailer instance, we will set a container instance
            // on the mailer. This allows us to resolve mailer classes via containers
            // for maximum testability on said classes instead of passing Closures.
            $mailer = new Mailer(
                $app['view'],
                $app['swift.mailer'],
                $app['events']
            );

            $this->setMailerDependencies($mailer, $app);

            // If a "from" address is set, we will set it on the mailer so that all mail
            // messages sent by the applications will utilize the same "from" address
            // on each one, which makes the developer's life a lot more convenient.
            $from = $app['config']['mail.from'];

            if ((is_array($from)) and (isset($from['address'])))
            {
                $mailer->alwaysFrom($from['address'], $from['name']);
            }

            $to = $app['config']['mail.to'];

            if ((is_array($to) and (isset($to['address']))))
            {
                $mailer->alwaysTo($to['address'], $to['name']);
            }

            return $mailer;
        });
    }

    /**
     * Set a few dependencies on the mailer instance.
     *
     * @param  \Illuminate\Mail\Mailer  $mailer
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function setMailerDependencies($mailer, $app)
    {
        $mailer->setContainer($app);

        $mode = 'live';

        if (isset($app['rzp.mode']))
        {
            $mode = $this->app['rzp.mode'];
        }

        if ($app['config']->get('queue.mock') === true)
        {
            $connectionName = $app['config']->get('queue.default');

            $queueName = null;
        }
        else
        {
            $connectionName = $app['config']->get('queue.mail.connection');

            $queueName = $app['config']->get('queue.mail.' . $mode);
        }

        if ($app->bound('queue'))
        {
            $mailer->setQueue($this->getQueue($connectionName, $queueName));
        }
    }

    protected function getQueue($connection, $queue)
    {
        return $this->app['queue']->connection($connection, $queue);
    }
}
