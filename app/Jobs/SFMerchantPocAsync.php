<?php

namespace RZP\Jobs;

use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\{Base\EsRepository};

class SFMerchantPocAsync extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 1;

    protected $queueConfigKey = 'poc_update';

    protected $input;

    protected $historicalSmeAdminsIds;

    protected $historicalClaimedMerchantIds;

    protected $currentClaimedMerchantsIds = array();

    protected $currentSmeAdminIds         = array();

    public function __construct(string $mode, array $input,
                                array $historicalSmeAdminsIds, array $historicalClaimedMerchantIds)
    {
        parent::__construct($mode);

        $this->input = $input;

        $this->historicalSmeAdminsIds = $historicalSmeAdminsIds;

        $this->historicalClaimedMerchantIds = $historicalClaimedMerchantIds;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            (new Admin\Service)->fetchAndDispatchPocOperation(
                $this->input, $this->currentSmeAdminIds, $this->currentClaimedMerchantsIds);

            (new Admin\Service)->removeHistoricalAdminsFromGroup($this->historicalSmeAdminsIds, $this->currentSmeAdminIds);

            $deltaUnclaimedAccounts = array_diff($this->historicalClaimedMerchantIds,
                                                 array_unique($this->currentClaimedMerchantsIds));

            if (sizeof($deltaUnclaimedAccounts) > 0)
            {
                foreach ($deltaUnclaimedAccounts as $merchantId)
                {
                    SFMerchantPocRemoval::dispatch($this->mode, $merchantId);
                }
            }

            EsSync::dispatch($this->mode, EsRepository::UPDATE, Entity::MERCHANT, $deltaUnclaimedAccounts);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SF_POC_ASYNC_ERROR,
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
            $this->trace->error(TraceCode::SF_POC_ASYNC_QUEUE_DELETE, [
                'job_attempts' => $this->attempts(),
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
