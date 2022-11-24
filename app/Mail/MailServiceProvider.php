<?php

namespace RZP\Mail;

use Mail;
use \Swift_Mailer;
use Illuminate\Mail\MailServiceProvider as BaseMailServiceProvider;

class MailServiceProvider extends BaseMailServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
        $this->registerSwiftSesTransport();
        $this->registerMailgunTransport();
    }

//    protected function registerSwiftSesTransport()
//    {
//        s("check this message");
//
//        $swiftMailer = app('mailer')->getSwiftMailer();
//
//        $swiftTransport = $swiftMailer->getTransport();
//
//        $this->app->singleton('swift.ses_mailer', function ($swiftTransport) {
//            return new Swift_Mailer($swiftTransport->driver('ses'));
//        });
//    }

    protected function registerSwiftSesTransport()
    {
        $this->app->singleton('swift.ses_mailer', function ($app) {
            return new Swift_Mailer($app['swift.transport']->driver('ses'));
        });
    }

    protected function registerMailgunTransport()
    {
        $this->app->singleton('swift.mailgun_mailer', function ($app) {
            return new Swift_Mailer($app['swift.transport']->driver('mailgun'));
        });
    }

//    protected function registerMailgunTransport()
//    {
//        $swiftMailer = app('mailer')->getSwiftMailer();
//
//        $swiftTransport = $swiftMailer->getTransport();
//
//        $this->app->singleton('swift.mailgun_mailer', function ($swiftTransport) {
//            return new Swift_Mailer($swiftTransport->driver('mailgun'));
//        });
//    }
}
