<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Admin\Group;
use Razorpay\Trace\Logger as Trace;

class SFAllMerchantToUnclaimedGroup extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 1;

    public $timeout = 1800;

    protected $queueConfigKey = 'poc_update';

    protected $value;

    protected $unClaimedGroupId;

    public function __construct(string $mode, array $value)
    {
        parent::__construct($mode);

        $this->value = $value;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $group = (new Group\Entity())->getSalesForceGroupId();

            $this->unClaimedGroupId = $group[Group\Constant::SALESFORCE_UNCLAIMED_GROUP_ID];

            foreach ($this->value as $merchantId)
            {
                $this->trace->info(TraceCode::MERCHANT_POC_UPDATE_REQUEST,
                                   [
                                       'message'    => 'Merchant POC update request for Unclaimed',
                                       'merchantId' => $merchantId,
                                   ]
                );

                try
                {
                    $this->moveAllMerchantsToUnclaimed($merchantId);
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::SF_POC_UPDATE_ERROR,
                        [
                            'message'    => "failed for the bellow merchants",
                            'merchantId' => $merchantId,
                        ]
                    );
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SF_POC_UPDATE_ERROR,
                [
                    'mode' => $this->mode,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::SF_POC_UPDATE_QUEUE_DELETE, [
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

    /**
     * @param string $merchantId
     *
     * @throws \Throwable
     */
    public function moveAllMerchantsToUnclaimed(string $merchantId)
    {
        $merchant = $this->repoManager->merchant->findOrFailPublic($merchantId);

        (new Group\Core)->addMerchantToGroups($merchant, [$this->unClaimedGroupId]);;
    }
}

