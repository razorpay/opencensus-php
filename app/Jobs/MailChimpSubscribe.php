<?php

namespace RZP\Jobs;

use Config;
use DrewM\MailChimp\MailChimp;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;

/**
 * Class MailChimp
 *
 * Sends request to mailchimp for adding entries to users mailing list.
 * @package RZP\Jobs
 */
class MailChimpSubscribe extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;
    const RELEASE_WAIT_SECS    = 60;

    const JOB_DELETED          = 'job_deleted';
    const JOB_RELEASED         = 'job_released';


    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        parent::__construct();

        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $config = Config::get('app.mailchimp');

            $apiKey = $config['api_key'];
            $listId = $config['list_id'];

            // Mock can be false or null for falsy cases
            if (empty($config['mock']) === true)
            {
                $mailchimp = new MailChimp($apiKey);

                $mailchimp->post("lists/$listId/members", [
                    'email_address' => $this->data['email'],
                    'status'        => 'subscribed',
                    'merge_fields'  => $this->breakName($this->data['name']),
                ]);
            }
        }
        catch(\Throwable $e)
        {
            $this->handleException($e);
        }
    }

    private function breakName($name)
    {
        $data = ['FNAME' => $name];

        $index = strpos($name, ' ');

        if ($index !== false)
        {
            $data['FNAME'] = substr($name, 0, $index);
            $data['LNAME'] = substr($name, $index + 1);
        }

        return $data;
    }

    /**
     * When an exception occurs, the job gets deleted if it has
     * exceeded the maximum attempts. Otherwise it is released back
     * into the queue after the set release wait time
     *
     * @param Throwable $e
     */
    protected function handleException(\Throwable $e)
    {
        $jobAction = self::JOB_DELETED;

        if ($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);

            $jobAction = self::JOB_RELEASED;
        }

        $this->trace->traceException(
            $e,
            Trace::ERROR,
            TraceCode::MAILCHIMP_JOB_ERROR,
            ['job_action' => $jobAction]);
    }
}
