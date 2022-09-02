<?php

namespace RZP\SqsRawSubscriber\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;
use RZP\SqsRawSubscriber\Queue\Jobs\SqsRawJob;

class SqsRawQueue extends SqsQueue
{
    /**
     * The Job command routes by Subject
     *
     * @var array
     */
    protected $routes;

    /**
     * Create a new Amazon SQS Raw subscription queue instance
     *
     * @param \Aws\Sqs\SqsClient $sqs
     * @param string $default
     * @param string $prefix
     * @param array $routes
     */
    public function __construct(SqsClient $sqs, $default, $prefix = '')
    {
        parent::__construct($sqs, $default, $prefix);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     * @return SqsRawJob
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $response = $this->sqs->receiveMessage(
            [
                'QueueUrl' => $queue,
                'AttributeNames' => ['ApproximateReceiveCount'],
            ]);

        if (is_array($response['Messages']) and count($response['Messages']) > 0)
        {
            return new SqsRawJob(
                $this->container,
                $this->sqs,
                $response['Messages'][0],
                $this->connectionName,
                $queue,
                $this->prefix);
        }
    }
}
