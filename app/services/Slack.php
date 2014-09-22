<?php

namespace Services;

use EE\Exception;
use Requests;

class Slack
{
    protected $team = 'razorpay';

    protected $token;

    protected $url = 'https://%s.slack.com/services/hooks/incoming-webhook?token=%s';

    protected $pretend;

    protected $config;

    protected $instance;

    public function __construct()
    {
        $this->initSlackConfig();

        $this->initInstanceData();
    }

    protected function initSlackConfig()
    {
        $config = \Config::get('slack');

        $this->token = $config['token'];
        $this->team = $config['team'];
        $this->pretend = $config['pretend'];
    }

    protected function initInstanceData()
    {
        $app = \App::getFacadeRoot();

        $this->instanceId = $app['instance']->getInstanceId();

        $this->cloud = \Config::get('app.cloud');
    }

    /**
     * Send the Slack message.
     *
     * @return void
     */
    public function send($message, $channel, $username)
    {
        $message .= ' Env: ' . \App::environment();

        $cloud = ($this->cloud) ? 'true' : 'false';

        $message .= ' Cloud: ' .  $cloud;

        if ($this->cloud)
        {
            $message .= ' Instance Id: ' . $this->instanceId;
        }

        $payload = array(
            'text' => $message,
            'channel' => $channel,
            'username' => $username);

        $content = array('payload' => json_encode($payload));

        $url = sprintf($this->url, $this->team, $this->token);

        if ($this->pretend === false)
        {
            $response = Requests::post($url, array(), $content);

            if ($response->status_code !== 200)
            {
                throw new Exception\LogicException(
                    'Posting to slack failed with error message: ' . $response->body);
            }
        }
    }
}
