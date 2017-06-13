<?php

namespace RZP\Models\Admin;

use Config;
use Carbon\Carbon;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\MailTags;
use RZP\Constants\HashAlgo;

/**
 * Defines functions to process Mailgun webhook requests
 */
class Mailgun extends Base\Core
{
    const EVENT         = 'event';
    const BOUNCED_EVENT = 'bounced';
    const DROPPED_EVENT = 'dropped';

    /**
     * Process request and notify on slack channel
     * Response - Status 200 = Accept / Status 406 = Reject. No retry made
     * Any other status will result in the webhook being retried
     *
     * @param string $type Callback type
     * @param array $input Request input
     * @return int statusCode
     * @throws Exception\BadRequestException
     */
    public function processCallback($type, $input)
    {
        $mailgunKey = Config::get('applications.mailgun.key');

        $this->authenticateSignature($mailgunKey, $input);

        $functionName = $type . 'Callback';

        if (method_exists($this, $functionName))
        {
            return $this->$functionName($input);
        }

        throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_INVALID_MAILGUN_WEBHOOK_TYPE);

    }

    protected function authenticateSignature($apiKey, $input)
    {
        $hashData = $input['timestamp'] . $input['token'];

        if (((time() - $input['timestamp']) > 30) or
            hash_hmac(HashAlgo::SHA256, $hashData, $apiKey) !== $input['signature'])
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_INVALID_MAILGUN_SIGNATURE);
        }
    }

    protected function failureCallback($input)
    {
        switch ($input[self::EVENT])
        {
            case self::BOUNCED_EVENT:
                return $this->bouncedCallback($input);

            case self::DROPPED_EVENT:
                return $this->droppedCallback($input);
        }

        return 406;
    }

    protected function droppedCallback($input)
    {
        if ((isset($input[MailTags::HEADER]) === true) and
            (in_array($input[MailTags::HEADER], MailTags::$setlNotifyTags, true)))
        {
            $this->notifyDrop($input);

            return 200;
        }

        return 406;
    }

    protected function bouncedCallback($input)
    {
        $this->notifyBounce($input);

        return 200;
    }

    protected function notifyDrop($input)
    {
        $message = '*ALERT*: Email delivery dropped, for tag: ' . $input[MailTags::HEADER];

        $channel = Config::get('slack.channels.settlements');

        $this->notifyOnSlack($message, $channel, $input);
    }

    protected function notifyBounce($input)
    {
        $message = '*ALERT*: Email delivery bounced';

        $channel = Config::get('slack.channels.tech_logs_mail');

        $this->notifyOnSlack($message, $channel, $input);
    }

    protected function notifyOnSlack($message, $channel, $input)
    {
        $sentDate = Carbon::createFromTimestamp($input['timestamp'], 'Asia/Kolkata')->format('d-M-Y H:i:s');

        $params = [
            'recipient' => $input['recipient'],
            'sent_at'   => $sentDate,
            'code'      => $input['code']   ?? null,
            'reason'    => $input['reason'] ?? null,
            'error'     => $input['error']  ?? null,
        ];

        $this->app->slack->queue($message, $params, ['channel' => $channel]);
    }
}
