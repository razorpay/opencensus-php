<?php

namespace Models\Settlement;

use Queue;

class SlackNotification
{
    protected $queue;

    protected $slack;

    protected $operations = array(
        'mpr_generation',
        'mpr_reconciliation',
        'settlements');

    protected $messages = array(
        'mpr_generation' => 'Mpr file generated. Payments count: ',
        'mpr_reconciliation' => 'Mpr file reconciled. Payments count: ',
        'settlements' => 'Settlements sent out. Merchants count: ');

    public function __construct()
    {
        $this->queue = Queue::getFacadeRoot();
    }

    public function queueOperationSuccess($operation, $count)
    {
        $message = $this->messages[$operation] . $count;

        $func = __CLASS__ . '@sendSlackNotification';

        $this->queue->push($func, $message);
    }

    public function queueOperationFailure($operation, $e)
    {
        $message = 'Failed operation: ' . $operation . PHP_EOL;

        $message .= 'Exception class: ' . get_class($e) . ', ' .
                    'Exception message: ' . $e->getMessage();

        $func = __CLASS__ . '@sendSlackNotification';

        $this->queue->push($func, $message);
    }

    public function sendSlackNotification($job, $message)
    {
        $channel = '#settlements';
        $username = 'settlements';

        $job->delete();

        $app = \App::getFacadeRoot();
        $app['slack']->send($message, $channel, $username);
    }
}