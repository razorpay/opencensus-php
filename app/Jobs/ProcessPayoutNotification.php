<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\PayoutDowntime\PayoutDowntimeMail;
use RZP\Models\PayoutDowntime\Repository as Repository;
use RZP\Mail\Base\Constants as BaseConstants;
use RZP\Models\PayoutDowntime\Constants as Constant;
use Mail;

class ProcessPayoutNotification extends Job
{

    private $input;

    private $mids;

    private $downtimeId;

    const MAX_ALLOWED_ATTEMPTS = 3;

    const LIMIT = 500;

    public    $timeout        = 3600;

    protected $queueConfigKey = 'payout_downtime';

    /**
     * Create a new job instance.
     *
     * @param string $mode
     * @param array  $input
     * @param String $downtimeId
     */
    public function __construct(string $mode, array $input, String $downtimeId)
    {
        parent::__construct($mode);

        $this->input = $input;

        $this->mids = $input[Constant::MID_LIST];

        $this->downtimeId = $downtimeId;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::PAYOUT_DOWNTIME_MID, $this->mids);

        $repo = new Repository();

        try
        {
            $userIds = $repo->findAllUserIdsForMerchantIds($this->mids);

            $emails = $repo->fetchUserEmails($userIds);

            $data = [
                Constant::FROM          => BaseConstants::MAIL_ADDRESSES[BaseConstants::X_SUPPORT],
                Constant::CC            => BaseConstants::MAIL_ADDRESSES[BaseConstants::X_SUPPORT],
                Constant::BCC           => $emails,
                Constant::SUBJECT       => $this->input[Constant::SUBJECT],
                Constant::EMAIL_MESSAGE => $this->input[Constant::EMAIL_MESSAGE],
                Constant::EMAIL_TYPE    => $this->input[Constant::STATUS],
            ];

            $payoutDowntimeMail = new PayoutDowntimeMail($data, $this->downtimeId);

            Mail::queue($payoutDowntimeMail);
        }
        catch (\Throwable $e)
        {
            if ($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(1);
            }
            else
            {
                $this->delete();
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PROCESS_PAYOUT_NOTIFICATION_ERROR
            );
        }
    }
}
