<?php

namespace RZP\Models\Settlement;

use Queue;
use Config;

use RZP\Models\Base;
use RZP\Constants\Mode;

class SlackNotification extends Base\Core
{
    const BAD  = 'bad';

    const GOOD = 'good';

    protected $operations = array(
        'setl_initiate',
        'setl_reconciled');

    protected $messages = array(
        'setl_initiate'           => 'Settlements initiated.',
        'setl_reconciliation'     => 'Settlements reconciled. ',
        'reconcile_file'          => 'Reconciliation file processed.',
        'setl_return'             => 'Settlements returns occurred. ',
        'fta_recon_report'        => 'Today\'s settlements recon report',
        'insufficient_fund'       => 'Insufficient Fund alert');

    public function success($operation, $data)
    {
        $data = [
            'message' => $this->messages[$operation],
            'status'  => self::GOOD
        ] + $data;

        $this->send($data);
    }

    public function failure($operation, $e)
    {
        $data = [
            'message'           => 'Failed operation: ' . $operation,
            'exception_class'   => get_class($e),
            'exception_message' => $e->getMessage(),
            'status'            => self::BAD
        ];

        $this->send($data);
    }

    public function send($data)
    {
        // Send Slack Notification only for Live mode in Production
        if ($this->mode === Mode::LIVE)
        {
            $message = $data['message'];
            $color   = $data['status'];

            unset($data['message'], $data['status']);

            $this->app['slack']->queue(
                $message,
                $data,
                [
                    'channel'   => Config::get('slack.channels.settlements'),
                    'username'  => 'settlements',
                    'color'     => $color
                ]);
        }
    }
}
