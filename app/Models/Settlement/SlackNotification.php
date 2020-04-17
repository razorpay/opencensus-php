<?php

namespace RZP\Models\Settlement;

use Queue;
use Config;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class SlackNotification extends Base\Core
{
    const BAD  = 'bad';

    const GOOD = 'good';

    const SETTLEMENT = 'settlements';

    protected $operations = array(
        'setl_initiate',
        'setl_reconciled');

    protected $messages = [
        'setl_skipped'            => 'Settlement Skipped. Check for Retry.',
        'setl_initiate'           => 'Settlements initiated.',
        'reconcile_file'          => 'Reconciliation file processed.',
        'setl_return'             => 'Settlements returns occurred. ',
        'fta_recon_report'        => 'Fund Transfer Potential Failures',
        'bene_reg_status'         => 'Beneficiaries Registration status',
        'critical_failure'        => 'Critical failure summary',
        'setl_verify'             => 'Settlement verification complete',
        'low_balance_alert'       => 'Account balance is below threshold',
        'setl_balance_alert'      => 'Settlement Amount',
        'merchant_invoice_alert'  => 'Merchant invoice creation skipped'
    ];

    /**
     * Used to send slack notifications
     * A failure notification is triggered when either failureCount > 0 or an exception(e) is raised
     * In all other cases, considered as success.
     *
     * @param $operation
     * @param $data
     * @param $e
     * @param int $failureCount
     * @param string $slackChannel
     */
    public function send(string $operation, array $data, $e = null, $failureCount = 0, string $slackChannel = null)
    {
        try
        {
            $info = $this->messages[$operation] ?? 'Operation:: '.$operation;

            $color = self::BAD;

            $icon = ':boom:';

            $username =  'Settlements';

            $channel = Config::get('slack.channels.settlements');

            if ($e !== null)
            {
                $data += [
                    'exception_class'   => get_class($e),
                    'exception_message' => $e->getMessage(),
                ];
            }

            if ($slackChannel !== null)
            {
                $channel = Config::get('slack.channels.' . $slackChannel);
            }

            $data += [
                'mode' => $this->mode,
            ];

            // Send Slack Notification only for Live mode in Production
            if ($this->mode === Mode::LIVE)
            {
                $this->app['slack']->queue(
                    $info,
                    $data,
                    [
                        'color'     => $color,
                        'icon'      => $icon,
                        'username'  => $username,
                        'channel'   => $channel,
                    ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SLACK_NOTIFICATION_SEND_FAILED,
                [
                    'operation' => $operation,
                    'data'      => $data,
                ]);
        }

    }
}
