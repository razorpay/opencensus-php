<?php

namespace RZP\Models\Merchant\Webhook;

use Requests;
use RZP\Trace\TraceCode;
use Mail;
use RZP\Models\Merchant;
use App;

class Inferno
{
    protected $job;

    protected $trace;

    protected $repo;

    protected $mode;

    protected $errorMessage;

    protected $event;

    const HASH_ALGO = 'sha256';

    const WEBHOOK_FAILURE_HOURS = 24;

    const WEBHOOK_MAXIMUM_ATTEMPTS = 24;

    const KNOWN_ERRORS = [
        'unable to get local issuer certificate',
        'empty reply from server',
        'ssl certificate problem: certificate has expired',
        '<url> malformed',
        'server error response',
    ];

    /**
     * We keep it internally as 7 seconds
     * but publicly we only say it's 5 seconds.
     */
    const WEBHOOK_TIMEOUT = 20;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->repo = new Repository;
    }

    /**
     * @param $job
     * @param $data
     */
    public function fire($job, $data)
    {
        $this->job = $job;

        $this->mode = $data['mode'];

        $this->event = $data['event'];

        $webhook = $this->getWebhook($data);

        if ($webhook->isActive() === false)
        {
            $job->delete();

            return;
        }

        $request = $this->getRequestArray($data['event'], $webhook);

        $success = $this->sendRequest($request, $webhook);

        $this->updateWebhookPostFiring($success, $webhook);
    }

    protected function updateWebhookPostFiring($success, $webhook)
    {
        if ($success === true)
        {
            $this->webhookSuccessfullyFired($webhook);
        }
        else
        {
            $this->webhookFailure($webhook);
        }
    }


    public function sendEmail($webhook, $type)
    {
        $data = array();

        $toEmails = $webhook->merchant->getTransactionReportEmail();

        $data['to_emails'] = $toEmails;

        $subjectName = $webhook->merchant->getBillingLabelElseName();

        $subject = 'Razorpay | ';

        $data['url'] = $webhook->getUrl();
        $data['error_message'] = $this->errorMessage;

        if (empty($data['error_message']))
        {
            $data['error_message'] = 'Internal Server Error. Please contact the Razorpay team for more details.';
        }

        $data['date'] = date('d-M-Y H:m:s T');

        $event = json_decode($this->event, true);

        $data['event'] = $event['event'];

        if ($type === 'failure')
        {
            $subject .= 'Webhook failed for ' . $subjectName;
        }
        else if ($type === 'deactivate')
        {
            $subject .= 'Webhook deactivated after 24 hours from last successful delivery for ' . $subjectName;
        }

        $data['subject'] = $subject;
        $data['mode'] = $this->mode;

        Mail::send('emails.webhook.'.$type, $data, function($message) use ($data)
        {
            $emails = $data['to_emails'];

            $message->from('alerts@razorpay.com', 'Razorpay Webhook Support');

            $message->replyTo('support@razorpay.com', 'Razorpay Support');

            $message->subject($data['subject']);

            $message->to($emails);
        });
    }

    public function getRequestHeaders($hmac)
    {
        $headers = array(
            'User-Agent'    => 'Razorpay-Webhook/v1',
            'Content-Type'  => 'application/json'
        );

        if (empty($hmac) === false)
        {
            $headers['X-Razorpay-Signature'] = $hmac;
        }

        return $headers;
    }

    public static function generateHMAC($payload, $secret)
    {
        // hmac doesn't throw up an exception for NULL values.
        if (($secret === null) or ($payload === null))
        {
            return null;
        }

        $hmac = hash_hmac(self::HASH_ALGO, $payload, $secret);

        return $hmac;
    }

    public function makeRequest($request)
    {
        $method = $request['method'];

        $response = Requests::$method(
                    $request['url'],
                    $request['headers'],
                    $request['content'],
                    $request['options']);

        return $response;
    }

    public function sendRequest($request, $webhook)
    {
        $success = true;
        $response = null;

        $this->trace->info(
            TraceCode::WEBHOOK_FIRING,
            [
                'webhook_id' => $webhook->getId(),
                'request'    => $request
            ]);

        try
        {
            $response = $this->makeRequest($request);
        }
        catch (\Requests_Exception $e)
        {
            //
            // Some error occurred.
            // Check that whether the gateway response timed out.
            // Mostly it should be gateway timeout only
            //
            if (\RZP\Gateway\Utility::checkTimeout($e))
            {
                $this->errorMessage = 'Webhook request timed out. We keep the timeout duration as ' .
                    round(self::WEBHOOK_TIMEOUT * 0.75) .
                    ' seconds. We will only retry 3 times before deactivating webhook.';
            }
            else if ($this->isKnownRequestsException($e))
            {
                $this->errorMessage = $e->getMessage();
            }
            else
            {
                $this->errorMessage = 'Internal Server Error. Please contact the Razorpay team for more details.';
                $this->trace->traceException($e);
            }

            $this->trace->info(
                TraceCode::WEBHOOK_RESPONSE_FAILURE,
                [
                    'webhook' => $webhook->getId(),
                    'exception' => $e->getMessage(),
                ]);

            return false;
        }

        if ($response->success === false)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_RESPONSE_FAILURE,
                [
                    'webhook' => $webhook->getId(),
                    'response_code' => $response->status_code,
                    'response_body' => $response->body,
                ]
            );

            $this->errorMessage = $response->body;

            $success = false;
        }
        else
        {
            $this->trace->info(
                TraceCode::WEBHOOK_FIRED,
                [
                    'webhook' => $webhook->getId(),
                    'response_code' => $response->status_code,
                ]);
        }

        return $success;
    }

    protected function getRequestArray($event, $webhook)
    {
        $secret = $webhook->getSecret();

        $hmac = static::generateHMAC($event, $secret);

        $headers = $this->getRequestHeaders($hmac);

        $request = array(
            'url' => $webhook->getUrl(),
            'method' => 'post',
            'content' => $event,
            'headers' => $headers);

        $request['options'] = ['timeout' => self::WEBHOOK_TIMEOUT];

        return $request;
    }

    protected function webhookSuccessfullyFired($webhook)
    {
        $this->repo->setLastSuccessfulAt($webhook);

        $this->job->delete();
    }

    /**
     * If the number of job attempts is greater than the max attempts,
     * we delete the job.
     * If the last successful webhook hit was more than 24 hours ago,
     * We deactivate the webhook. We send a deactivation email.
     * We do not send any failure email in this case.
     *
     * In every other case, we send a failure email.
     *
     * @param $webhook
     */
    protected function webhookFailure($webhook)
    {
        $job = $this->job;

        $sendFailureEmail = 1;
        $jobDeleted = 0;

        if (($job->attempts() > self::WEBHOOK_MAXIMUM_ATTEMPTS))
        {
            $job->delete();
            $jobDeleted = 1;
        }

        $lastSuccessfulAt = $webhook->getLastSuccessfulAt();
        $currentTime = time();

        if ($lastSuccessfulAt !== null)
        {
            $differenceHours = ($currentTime - $lastSuccessfulAt) / 3600;

            // If (LSA - current time) > 24hrs, mark deactivated.
            if (($differenceHours > self::WEBHOOK_FAILURE_HOURS))
            {
                $this->trace->info(
                    TraceCode::WEBHOOK_DEACTIVATE,
                    ['webhook' => $webhook->getId()]
                );

                $webhook->deactivate();

                $this->sendEmail($webhook,'deactivate');

                // Webhook is now inactive
                // So let's just delete the job
                if ($jobDeleted == 0)
                {
                    $job->delete();
                }

                $sendFailureEmail = 0;
            }
        }

        if ($sendFailureEmail === 1)
        {
            $this->sendEmail($webhook,'failure');

            // Attempt again after 1 hour
            $job->release(3600);
        }
    }

    protected function getWebhook($data)
    {
        $mode = $data['mode'];

        $webhook = $this->repo
                        ->connection($mode)
                        ->find($data['webhook_id']);

        if ($webhook === null)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_FIRING,
                ['data' => $data]);

            $this->job->delete();
        }

        return $webhook;
    }

    protected function isKnownRequestsException($e)
    {
        $msg = $e->getMessage();
        $msg = strtolower($msg);

        foreach (self::KNOWN_ERRORS as $errorMessage)
        {
            if (strpos($msg, $errorMessage) !== false)
            {
                return true;
            }
        }
        return false;
    }
}
