<?php

namespace RZP\Models\Settlement;

use Queue;
use Config;

class SlackNotification
{
    protected $operations = array(
        'setl_initiate',
        'setl_reconciled');

    protected $messages = array(
        'setl_initiate'         => 'Settlements initiated.',
        'setl_reconciliation'   => 'Settlements reconciled. ',
        'setl_return'           => 'Settlements returns occurred. ');

    public function success($operation, $data)
    {
        $data = [
            'message' => $this->messages[$operation],
            'status'  => 'good'
        ] + $data;

        $this->send($data);
    }

    public function failure($operation, $e)
    {
        $data = [
            'message'           => 'Failed operation: ' . $operation,
            'exception_class'   => get_class($e),
            'exception_message' => $e->getMessage(),
            'status'            => 'bad'
        ];

        $this->send($data);
    }

    public function send($data)
    {
        $message = $data['message'];
        $color   = $data['status'];
        $app = \App::getFacadeRoot();

        unset($data['message'], $data['status']);

        $app['slack']->queue(
            $message,
            $data,
            [
                'channel'   => Config::get('slack.channels.settlements'),
                'username'  => 'settlements',
                'color'     => $color
            ]);
    }
}
