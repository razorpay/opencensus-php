<?php

namespace RZP\Models\Admin;

use RZP\Constants\MailTags;
use Carbon\Carbon;
use Config;

/**
 * Defines functions to process Mailgun webhook requests
 */
class Mailgun
{
    protected $app;
    
    public function __construct()
    {
        $this->app = \App::getFacadeRoot();
    }
    
    /**
     * Process request and notify on slack channel
     * Response - Status 200 = Accept / Status 406 = Reject. No retry made
     * Any other status will result in the webhook being retried
     * 
     * @param type $input Request input
     * @return int statusCode
     */
    public function processEmailNotifyCheck($input)
    {
        if (isset($input['X-Mailgun-Tag']) === false)
        {
            return 406;
        }
        
        if (in_array($input['X-Mailgun-Tag'], MailTags::$notifyTags, true)) 
        {
            $this->notifyFailureOnSlack($input);

            return 200;
        }

        return 406;
    }
    
    protected function notifyFailureOnSlack($input)
    {
        $sentDate = Carbon::createFromTimestamp($input['timestamp'], 'Asia/Kolkata')->format('d-M-Y H:i:s');
        
        $message = '*ALERT*: Email delivery failed/bounced, for tag: ' . $input['X-Mailgun-Tag'];
        
        $params = [
            'recipient' => $input['recipient'],
            'sent_at'   => $sentDate
        ];
        
        $notifyChannel = Config::get('slack.channels.settlements');
        
        $this->app->slack->queue($message, $params, ['channel' => $notifyChannel]);
    }
}