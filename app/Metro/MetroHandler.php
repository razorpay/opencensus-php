<?php

namespace RZP\Metro;

use RZP\Trace\TraceCode;
use Illuminate\Support\Facades\App;
use Google\Cloud\PubSub\PubSubClient;

class MetroHandler
{
    protected $pubSubClient;

    protected $trace;

    protected $mock;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $config = $app['config']['metro'];

        $config['transport'] = 'rest';

        $this->mock = $config['mock'];

        $config['restOptions'] = [
            'headers' => [
                'Authorization' => 'Basic '. base64_encode($config['username'].':'.$config['password'])
            ]
        ];

        putenv('PUBSUB_EMULATOR_HOST='.$config['apiEndpoint']);

        $this->pubSubClient = new PubSubClient($config);

        unset($config['username']);

        unset($config['password']);
    }

    public function publish(string $topicName, array $message)
    {
        if ($this->mock) {

            $this->trace->info(TraceCode::METRO_MOCKED, [
                'topic' => $topicName
            ]);

            return [];
        }

        $topic = $this->pubSubClient->topic($topicName);

        $this->trace->info(TraceCode::MESSAGE_PUBLISHED_TO_TOPIC, [
            'topic' => $topicName
        ]);

        $response = $topic->publish($message);

        $this->trace->info(TraceCode::MESSAGE_PUBLISHED_TO_TOPIC, [
            'topic'     => $topicName,
            'response'  => $response,
        ]);

        return $response;
    }

}
