<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Admin\Mailgun;
use Razorpay\Trace\Logger as Trace;

class MailingListUpdate extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;

    const RELEASE_WAIT_SECS    = 30;

    /**
     * @var
     * $suspended = 1 the merchant is the suspended merchant and should not get the holiday notification mail
     * $suspended = 0 the merchant is the activated merchant and should get the holiday notification mail.
     */
    protected $suspended;

    /**
     * @var string
     */
    protected $queueConfigKey = 'mailing_list_update';

    /**
     * @var array
     */
    protected $chunks;

    /**
     * @param string|void $mode
     * @param array $chunks
     * @param true $suspended
     */
    public function __construct(string $mode , array $chunks, $suspended = false)
    {
        parent::__construct($mode);

        $this->suspended = $suspended;

        $this->chunks    = $chunks;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            if($this->suspended === true)
            {
                (new Mailgun)->deleteMemberFromMailingList($this->chunks[0]);
            }
            else
            {
                (new Mailgun)->addMemberToMailingList($this->chunks);
            }
        }
        catch (\Throwable $e)
        {
            if ($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(self::RELEASE_WAIT_SECS);
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MERCHANT_MAIL_UPDATE_FAIL,
                [
                    'merchant'      => $this->chunks,
                    'suspended'     => $this->suspended,
                ]);
        }
    }
}
