<?php

namespace RZP\SqsFifoSubscriber\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Arr;
use RZP\SqsFifoSubscriber\Queue\SqsFifoQueue;
use Illuminate\Queue\Connectors\SqsConnector;

class SqsFifoConnector extends SqsConnector
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

        return new SqsFifoQueue(
            new SqsClient($config),
            $config['queue'],
            Arr::get($config, 'prefix', ''));
    }
}
