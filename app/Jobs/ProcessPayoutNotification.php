<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\PayoutDowntime\PayoutDowntimeMail;
use RZP\Models\PayoutDowntime\Repository as PayoutDowntimeRepository;
use RZP\Mail\Base\Constants as BaseConstants;
use RZP\Models\PayoutDowntime\Constants as Constant;
use RZP\Models\Merchant;
use Mail;

class ProcessPayoutNotification extends Job
{

    private $input;

    private $downtimeId;

    private $channel;

    const MAX_ALLOWED_ATTEMPTS = 3;

    const LIMIT = 200;

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

        $this->channel = $input[Constant::CHANNEL];

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

        $repo = new PayoutDowntimeRepository();

        try
        {

            $i = 0;

            while (true)
            {
                $merchantIds = array();

                $offset = $i * self::LIMIT;

                $merchantIdsInChunk = $repo->fetchMerchantIdsInChunk($offset, self::LIMIT);

                if(empty($merchantIdsInChunk) === true)
                {
                    break;
                }

                if ($this->channel === Constant::POOL_NETWORK)
                {
                    $merchantIds = $repo->fetchActiveVirtualAccountForMerchantIds($merchantIdsInChunk);
                }
                else if ($this->channel === Constant::RBL)
                {
                    $merchantIds = $repo->fetchActiveRblAccountForMerchantIds($merchantIdsInChunk);
                }
                else if ($this->channel === Constant::ALL)
                {
                    $merchantIds = $merchantIdsInChunk;
                }

                $userIds = $repo->findAllUserIdsForMerchantIds($merchantIds);

                if (empty($userIds) === false)
                {
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

                $i++;
            }
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
