<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\MerchantApplications;

class BackFillMerchantApplications extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 1;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    public $timeout = 1000;

    protected $merchantIds;

    public function __construct(string $mode, array $merchantIds)
    {
        parent::__construct($mode);

        $this->merchantIds = $merchantIds;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::MERCHANT_APPLICATIONS_TABLE_BACKFILL_REQUEST,
                [
                    'mode' => $this->mode,
                    'merchant_ids' => $this->merchantIds,
                ]
            );

            foreach ($this->merchantIds as $merchantId)
            {
                try
                {
                    $merchant = (new Merchant\Repository())->fetchMerchantOnConnection($merchantId['id'], $this->mode);

                    $appType = (new MerchantApplications\Core())->getDefaultAppTypeForPartner($merchant);

                    $appIds = (new Merchant\Core)->getPartnerApplicationIds($merchant);

                    foreach ($appIds as $appId)
                    {
                        $isAppPresent = (new MerchantApplications\Core())->isMerchantAppPresent($appId);

                        if ($isAppPresent === false)
                        {
                            (new Merchant\Core())->createMerchantApplication($merchant, $appId, $appType);
                        }
                    }
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::MERCHANT_APPLICATIONS_CREATE_ERROR,
                        [
                            'merchant_id' => $merchantId,
                        ]
                    );
                }
            }

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MERCHANT_APPLICATIONS_BACKFILL_JOB_ERROR,
                [
                    'mode'          => $this->mode,
                    'merchant_ids'  => $this->merchantIds,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::MERCHANT_APPLICATIONS_BACKFILL_QUEUE_DELETE, [
                'id'           => $this->merchantIds,
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
}
