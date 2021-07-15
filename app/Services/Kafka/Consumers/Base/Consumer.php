<?php

namespace RZP\Services\Kafka\Consumers\Base;

use App;

abstract class Consumer
{
    // group.id is responsible for setting your consumer group ID and it should be unique
    // (and should not change). Kafka uses it to recognize applications and store offsets for them.

    // Therefore add group_id in comments. Adding that here in comments
    // will help us to remember what was group id for this consumer
    // group_id : sample_consumer_group_id

    public abstract function handle($topic, $payload, $mode): bool;

    public abstract function getPayloadWithSensitiveDetailsMasked(array $payload): array;

    public function __construct(Config $baseConfig)
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];

        $this->config = $baseConfig->getConfig();
    }

    public function getConfig()
    {
        return $this->config;
    }
}
