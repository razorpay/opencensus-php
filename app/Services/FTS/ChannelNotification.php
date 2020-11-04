<?php

namespace RZP\Services\FTS;

use Mail;
use RZP\Mail\Payout\DowntimeNotification;

class ChannelNotification
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
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

        Mail::queue($downtimeNotification);
    }
}
