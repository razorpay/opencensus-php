<?php

namespace RZP\Models\Admin;

use Config;
use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Constants\HashAlgo;
use RZP\Exception;
use RZP\Error;
use RZP\Models\Base;

/**
 * Defines functions to process Mailgun webhook requests
 */
class Mailgun extends Base\Core
{
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

        if (time() - $input['timestamp'] > 30 or
            hash_hmac(HashAlgo::SHA256, $hashData, $apiKey) !== $input['signature'])
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_INVALID_MAILGUN_SIGNATURE);
        }
    }

    protected function failureCallback($input)
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

        if (isset($input['reason']))
        {
            $params['reason'] = $input['reason'];
        }

        $notifyChannel = Config::get('slack.channels.settlements');

        $this->app->slack->queue($message, $params, ['channel' => $notifyChannel]);
    }
}
