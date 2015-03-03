<?php

namespace Services;

use EE\Exception;
use Requests;
use Mailgun\Mailgun as MgClient;

class Mailgun
{
    protected $config;

    protected $mgClient;

    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.mailgun');
        $this->mode = $app['rzp.mode'];
    }

    public function sendAutoCaptureEmail($to, $message)
    {
        $mailData = array(
            'from'    => 'Razorpay Auto Capture <autocaptured@razorpay.com>',
            'to'      => $to,
            'bcc'     => 'autocapturereport@razorpay.com',
            'subject' => 'Auto Captured Payments Report for ' . $this->getMode() . ' mode',
            'text'    => $message,
        );

        $this->sendMessage($mailData);
    }

    protected function getMailgunInstance()
    {
        if ($this->mgClient !== null)
        {
            return $this->mgClient;
        }

        $key = $this->config['key'];

        $this->mgClient = new MgClient($key);

        return $this->mgClient;
    }

    protected function sendMessage(array $mailData)
    {
        $domain = $this->config['url'];

        $res = null;

        if ($this->config['mock'] === false)
        {
            $res = $this->getMailgunInstance()->sendMessage($domain, $mailData);

            if ((isset($res['message']) === false) or
                (isset($res['id']) === false))
            {
                throw new Exception\RuntimeException(
                    'Failed to send email message via mailgun');
            }
        }

        return $res;
    }

    protected function getMode()
    {
        return $this->mode;
    }
}