<?php

namespace RZP\Models\Settlement;

use Queue;

class SlackNotification
{
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
                'channel'   => '#settlements',
                'username'  => 'settlements',
                'color'     => $color
            ]);
    }
}
