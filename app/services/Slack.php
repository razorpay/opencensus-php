<?php

namespace Services;

use Requests;

class Slack
{
    protected $team = 'razorpay';

    protected $token;

    protected $url = 'https://%s.slack.com/services/hooks/incoming-webhook?token=%s';

    protected $pretend;

    public function __construct()
    {
        $config = \Config::getFacadeRoot();

        $this->token = $config->get('slack.token');

        $this->team = $config->get('slack.team');

        $this->pretend = $config->get('slack.pretend');
    }

    /**
     * Send the Slack message.
     *
     * @return void
     */
    public function send($message, $channel, $username)
    {
        $payload = array(
            'text' => $message,
            'channel' => $channel,
            'username' => $username);

        $url = sprintf($this->url, $this->team, $this->token);

        if ($this->pretend === false)
        {
            $response = Requests::post($url, array(), $payload);

            $content = json_decode($response->getContent(), true);

            if ($content['ok'] === false)
            {
                throw new Exception\LogicException(
                    'Posting to slack failed with error message: ' . $content['error']);
            }
        }
    }
}
