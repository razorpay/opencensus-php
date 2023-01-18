<?php

namespace RZP\SqsFifoSubscriber\Queue;

use App;
use Aws\Sqs\SqsClient;
use RZP\Trace\TraceCode;
use Illuminate\Queue\SqsQueue;
use Illuminate\Queue\Jobs\SqsJob;

class SqsFifoQueue extends SqsQueue
{
    protected $trace;

    protected $job;
    /**
     * Create a new Amazon SQS FIFO subscription queue instance
     *
     * @param \Aws\Sqs\SqsClient $sqs
     * @param string $default
     * @param string $prefix
     * @param array $routes
     */
    public function __construct(SqsClient $sqs, $default, $prefix = '')
    {
        parent::__construct($sqs, $default, $prefix);
        $app = App::getFacadeRoot();
        $this->trace = $app['trace'];
    }

    public function push($job, $data = '', $queue = null)
    {
        $this->job = $job;
        return $this->pushRaw($this->createPayload($job, $queue ?: $this->default, $data), $queue);
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $messageGroup = ($this->job->messageGroup) ? $this->job->messageGroup : uniqid();
        $dedupId = uniqid('', true);

        $response = $this->sqs->sendMessage([
            'QueueUrl' => $this->getQueue($queue),
            'MessageBody' => $payload,
            'MessageGroupId' => $messageGroup,
            'MessageDeduplicationId' => $dedupId,
        ]);

        $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_DEBUG, [
            'response' => $response,
            'message_id' => $response->get('MessageId'),
            'message_group' => $messageGroup,
            'dedupId' => $dedupId
        ]);

        return $response->get('MessageId');
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue,
            'AttributeNames' => ['All'],
            'WaitTimeSeconds' => 1,
        ]);

        if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
            return new SqsJob(
                $this->container, $this->sqs, $response['Messages'][0],
                $this->connectionName, $queue
            );
        }
    }
}
