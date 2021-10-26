<?php

namespace RZP\Metro;

use RZP\Trace\TraceCode;
use Illuminate\Support\Facades\App;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\MessageBuilder;

class MetroHandler
{
    protected $pubSubClient;

    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $config = $app['config']['metro'];

        $config['transport'] = 'rest';

        $config['restOptions'] = [
            'headers' => [
                'Authorization' => 'Basic '. base64_encode($config['username'].':'.$config['password'])
            ]
        ];

        $this->trace->info(TraceCode::PUB_SUB_CLIENT_CREATING, [
            'options' => $config['restOptions'],
        ]);

        $this->pubSubClient = new PubSubClient($config);

        unset($config['username']);
        unset($config['password']);
    }

    public function publish(string $topicName, string $message)
    {
        $topic = $this->pubSubClient->topic($topicName);

        $this->trace->info(TraceCode::MESSAGE_PUBLISHED_TO_TOPIC, [
            'topic' => $topicName
        ]);

        return $topic->publish((new MessageBuilder)->setData($message)->build());
    }

}