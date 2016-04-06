<?php

namespace Models\Merchant\Webhook;

use Requests;
use Trace\TraceCode;
use Mail;
use Models\Merchant;

class Inferno
{
    protected $job;
    protected $trace;
    protected $repo;

    const HASH_ALGO = 'sha256';

    public function __construct()
    {
        $app = \App::getFacadeRoot();

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

        $repo = $this->repo;

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
            $this->webhookBumpFailureCount($webhook);
        }
    }


    public function sendEmail($webhook, $type)
    {
        $data = array();
        $toEmails = $webhook->merchant->getTransactionReportEmail();
        $data['to_emails'] = $toEmails;
        if($type === 'unsuccessful')
        {
            $data['subject'] = 'Unsuccessful webhook event';
        }
        else if($type === 'failure')
        {
            $data['subject'] = 'Disabled webhook event';
        }
        Mail::send('emails.webhook.'.$type, $data, function($message) use ($data)
        {
            $emails = $data['to_emails'];
            $message->from('support@razorpay.com', 'Razorpay Support');
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

        if (!empty($hmac))
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

        //
        // TODO: payload should be of type string.
        // Throws up an error otherwise. Should we handle?
        //
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
            $request);

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
            if ((\Gateway\Utility::checkTimeout($e) === false) and
                ($this->isKnowRequestsException($e) === false))
            {
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

        $code = TraceCode::WEBHOOK_FIRED;

        if ($response->success === false)
        {
            $code = TraceCode::WEBHOOK_RESPONSE_FAILURE;

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

        $hmac = $this->generateHMAC($event, $secret);

        $headers = $this->getRequestHeaders($hmac);

        $request = array(
            'url' => $webhook->getUrl(),
            'method' => 'post',
            'content' => $event,
            'headers' => $headers);

        $request['options'] = ['timeout' => 10];

        return $request;
    }

    protected function webhookSuccessfullyFired($webhook)
    {
        if ($webhook->getFailureCount() !== 0)
        {
            $this->repo->resetFailureCount($webhook);
        }

        $this->job->delete();
    }

    protected function webhookBumpFailureCount($webhook)
    {
        $job = $this->job;

        // It's a failure, increment failure count.
        $this->repo->bumpFailureCount($webhook);

        if (($webhook->isActive() === false) or ($job->attempts() >= 3))
        {
            $this->trace->info(
                TraceCode::WEBHOOK_DEACTIVATE,
                ['webhook' => $webhook->getId()]
            );

            $this->sendEmail($webhook,'failure');

            // Webhook is now inactive
            // So let's just delete the job
            $job->delete();
        }
        else
        {
            $this->sendEmail($webhook,'unsuccessful');

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

    protected function isKnowRequestsException($e)
    {
        $msg = $e->getMessage();
        $msg = strtolower($msg);

        //
        // check if timeout has occured
        //
        if ((strpos($msg, 'Empty reply from server') !== false) or
            (strpos($msg, 'SSL certificate problem: certificate has expired') !== false))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}