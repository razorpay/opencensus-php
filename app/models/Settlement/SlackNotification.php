<?php

namespace Models\Settlement;

use Services\Slack;
use Queue;

class SlackNotification
{
    use Slack;
    protected $queue;

    protected $slack;

    protected $operations = array(
        'mpr_generation',
        'mpr_reconciliation',
        'setl_initiate',
        'setl_reconciled');

    protected $messages = array(
        'mpr_generation'        => 'Mpr file generated. ',
        'mpr_reconciliation'    => 'Mpr file reconciled. ',
        'setl_initiate'         => 'Settlements initiated.',
        'setl_reconciliation'   => 'Settlements reconciled. ',
        'setl_return'           => 'Settlements returns occurred. ');

    public function __construct()
    {
        $this->queue = Queue::getFacadeRoot();
    }

    public function queueOperationSuccess($operation, $data)
    {
        $data = [
            'message' => $this->messages[$operation],
            'status'  => 'good'
        ] + $data;

        $func = __CLASS__ . '@sendSlackNotification';

        $this->queue->push($func, $data);
    }

    public function queueOperationFailure($operation, $e)
    {
        $data = [
            'message'           => 'Failed operation: ' . $operation,
            'exception_class'   => get_class($e),
            'exception_message' => $e->getMessage(),
            'status'            => 'bad'
        ];

        $func = __CLASS__ . '@sendSlackNotification';

        $this->queue->push($func, $data);
    }

    public function sendSlackNotification($job, $message)
    {
        $job->delete();

        $message = $data['message'];
        $color   = $data['status']
        unset($data['message'], $data['status']);

        $this->slackPost($message, $data, '@harshil @shk', [
            'channel'   => '#settlements'
            'username'  => 'settlements'
            'color'     => $color
        ]);
    }
}
