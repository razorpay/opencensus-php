<?php

namespace Services;

use EE\Exception;
use Requests;

class Slack
{
    protected $team = 'razorpay';

    protected $token;

    protected $url = 'https://%s.slack.com/services/hooks/incoming-webhook?token=%s';

    protected $mock;

    protected $config;

    protected $instance;

    public function __construct($app)
    {
        $this->env = $app['env'];
        $this->context = $app['config']->get('app.context');

        $slackConfig = $app['config']->get('applications.slack');
        $this->initSlackConfig($slackConfig);

        $this->initInstanceData($app);
    }

    protected function initSlackConfig($config)
    {
        $this->token = $config['token'];
        $this->team = $config['team'];
        $this->mock = $config['mock'];
    }

    protected function initInstanceData($app)
    {
        $this->instanceId = $app['instance']->getInstanceId();

        $this->cloud = $app['config']->get('app.cloud');
    }

    /**
     * Send the Slack message.
     *
     * @return void
     */
    public function send($message, $channel, $username)
    {
        $message .= $this->getGenericMessage();

        $payload = array(
            'text' => $message,
            'channel' => $channel,
            'username' => $username,
            'link_names' => 1);

        $content = array('payload' => json_encode($payload));

        if ($this->token === null)
        {
            throw new Exception\InvalidArgumentException(
                'Slack token is null. Provide a meaninful token');
        }

        $url = sprintf($this->url, $this->team, $this->token);

        if ($this->mock === true)
        {
            return;
        }

        $this->postRequest($url, $content);
    }

    protected function postRequest($url, $content)
    {
        $response = Requests::post($url, array(), $content);

        if ($response->status_code !== 200)
        {
            throw new Exception\LogicException(
                'Posting to slack failed with error message: ' . $response->body);
        }
    }

    protected function getGenericMessage()
    {
        $message = ' Env: ' . $this->env . PHP_EOL . ' Context: ' . $this->context . PHP_EOL;

        $cloud = ($this->cloud) ? 'true' : 'false';
        $message .= ' Cloud: ' .  $cloud . ', ';

        if ($this->cloud)
        {
            $message .= ' Instance Id: ' . $this->instanceId . PHP_EOL;
        }

        return $message;
    }
}
