<?php

namespace RZP\Services\FTS;

use Mail;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Payout\DowntimeNotification;

class ChannelNotification
{
    protected $app;

    protected $trace;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];
    }

    /**
     *  Here, input contains mode and channel
     *  {'mode': 'IMPS', 'channel': 'ICICI', type: 'partner'}
     * @param array $input
     */
    public function channelNotify(array $input)
    {
        // TODO: Add notification specific logic here and fill the data section accordingly
        $result = $input;

        $this->sendEmail($result);
    }

    protected function sendEmail($result)
    {
        // Extract info from result section and fill the data section array accordingly
        $data = [
            'to' => 'fts-team@razorpay.com',
            'subject' => 'Downtime/Uptime Notification',
            'body' => $result,
            'template' => 'emails.payout.downtime_default',
        ];

        $downtimeNotification = new DowntimeNotification($data);

        try
        {
            $this->trace->info(
                TraceCode::FTS_DOWNTIME_NOTIFY_EMAIL_INIT,
                [
                    'request' => $data,
                ]);

            Mail::send($downtimeNotification);

            $this->trace->info(
                TraceCode::FTS_DOWNTIME_NOTIFY_EMAIL_COMPLETE,
                [
                    'response' => $data,
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::FTS_DOWNTIME_NOTIFY_EMAIL_FAILURE,
                [
                    'data' => $data,
                ]
            );
        }
    }
}
