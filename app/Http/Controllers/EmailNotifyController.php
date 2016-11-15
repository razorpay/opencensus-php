<?php

namespace RZP\Http\Controllers;

use RZP\Constants\MailTags;
use Request;
use ApiResponse;
use Carbon\Carbon;
use Config;

class EmailNotifyController extends Controller
{
    public function postEmailStatusCallback()
    {
        $input = Request::all();

        $responseStatus = $this->processEmailNotifyCheck($input);

        return ApiResponse::json([], $responseStatus);
    }
    
    /**
     * Process request and notify on slack channel
     * Response - Status 200 = Accept / Status 406 = Reject. No retry made
     * Any other status will result in the webhook being retried
     * 
     * @param type $input Request input
     * @return int statusCode
     */
    protected  function processEmailNotifyCheck($input)
    {
        if (isset($input['X-Mailgun-Tag']) === false)
        {
            return 406;
        }
        
        if (in_array($input['X-Mailgun-Tag'], MailTags::$notifyTags, true)) 
        {
            $this->notifyEventOnSlack($input);

            return 200;
        }

        return 406;
    }
    
    protected function notifyEventOnSlack($input)
    {
        $sentDate = Carbon::createFromTimestamp($input['timestamp'], 'Asia/Kolkata')->format('d-M-Y H:i:s');
        
        $message = '*ALERT*: Email status: ' . $input['event'] . ', for tag: ' . $input['X-Mailgun-Tag'];
        
        $params = [
            'recipient' => $input['recipient'],
            'sent_at'   => $sentDate
        ];
        
        $notifyChannel = Config::get('slack.channels.settlements');
        
        $this->app->slack->queue($message, $params, ['channel' => $notifyChannel]);
    }
    
}