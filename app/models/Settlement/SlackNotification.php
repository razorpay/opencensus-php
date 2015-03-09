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
        $data = ['message' => $this->messages[$operation]] + $data;

        $str = json_encode($data, JSON_PRETTY_PRINT);
        $message = '```' . $str . '```';

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