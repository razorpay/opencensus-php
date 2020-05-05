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
     * $addOrRemoveFromList = 1 the merchant needs to be added to the list.
     * $addOrRemoveFromList = 0 the merchant needs to be removed from the list.
     */

    /**
     * @var string
     */
    protected $queueConfigKey = 'mailing_list_update';

    /**
     * @var array
     */
    protected $chunks;

    /**
     * @var boolean
     */
    protected $addOrRemoveFromList = true;

    /**
     * @var boolean
     */
    protected $list = 'live';

    // time (in seconds) after which the job is killed.
    public $timeout = 300;

    /**
     * @param string|void $mode
     * @param array $chunks
     * @param true $addOrRemoveFromList
     * @param string $list
     */
    public function __construct(string $mode , array $chunks, $addOrRemoveFromList = true, string $list = 'live')
    {
        parent::__construct($mode);

        $this->addOrRemoveFromList = $addOrRemoveFromList;

        $this->chunks    = $chunks;

        $this->list      = $list;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            if($this->addOrRemoveFromList === false)
            {
                (new Mailgun)->deleteMemberFromMailingList($this->chunks[0], $this->list);
            }
            else
            {
                (new Mailgun)->addMemberToMailingList($this->chunks, $this->list);
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
                    'add_to_list'   => $this->addOrRemoveFromList,
                ]);
        }
    }
}
