<?php

namespace RZP\Models\Gateway\File;

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
    public function send(string $operation, array $data, $e = null, $failureCount = 0, string $slackChannel = null, string $username = null)
    {
        try
        {
            $info = 'Operation:: '.$operation;

            $color = self::BAD;

            $icon = ':boom:';

            $username =  ($username === null) ? 'NB_CLAIM_FILE' : $username;

            if ($e !== null)
            {
                $data += [
                    'exception_class'   => get_class($e),
                    'exception_message' => $e->getMessage(),
                ];
            }

            $channel = Config::get('slack.channels.' . $slackChannel);

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
