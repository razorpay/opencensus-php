<?php

namespace RZP\SqsRawSubscriber\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Arr;
use RZP\SqsRawSubscriber\Queue\SqsRawQueue;
use Illuminate\Queue\Connectors\SqsConnector;

class SqsRawConnector extends SqsConnector
{
    /**
     * Establish a queue connection.
     *
     * @param array $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if ($config['key'] and $config['secret'])
        {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        return new SqsRawQueue(
            new SqsClient($config),
            $config['queue'],
            Arr::get($config, 'prefix', ''));
    }
}