<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Admin;
use Razorpay\Trace\Logger as Trace;

class SFMerchantPocRemoval extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 1;

    protected $queueConfigKey = 'poc_update';

    protected $claimedMerchantsGroupId;

    protected $smeGroupId;

    protected $unClaimedGroupId;

    protected $merchantId;

    public function __construct(string $mode, string $value)
    {
        parent::__construct($mode);

        $this->merchantId = $value;
    }

    /**
     * 1. Remove from Claimed merchant
     * 2. Remove from SME claimed merchant if exists.
     * 3. Remove from Merchant Admin if exits.
     * 4. Adding to unclaimed Group
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $group = (new Group\Entity())->getSalesForceGroupId();

            $this->claimedMerchantsGroupId = $group[Group\Constant::SALESFORCE_CLAIMED_MERCHANTS_GROUP_ID];

            $this->smeGroupId = $group[Group\Constant::SALESFORCE_CLAIMED_SME_GROUP_ID];

            $this->unClaimedGroupId = $group[Group\Constant::SALESFORCE_UNCLAIMED_GROUP_ID];

            $this->trace->info(TraceCode::MERCHANT_POC_UPDATE_REQUEST,
                               [
                                   'Merchant POC removal request',
                                   'merchantId' => $this->merchantId,
                               ]
            );

            $merchant = $this->repoManager->merchant->findOrFailPublic($this->merchantId);

            //Removing From Claimed Merchants Group
            (new Group\Core)->removeMerchantFromGroups($merchant, [$this->claimedMerchantsGroupId]);

            //One claimed merchant not necessarily SME merchant
            //Trying to removing From Claimed SME Merchants Group
            (new Group\Core)->removeMerchantFromGroups($merchant, [$this->smeGroupId]);

            //Removing Previous Admin access of the Merchants
            //One claimed merchant not necessarily Non-SME merchant
            $historicalAdminIds = $merchant->admins->getIds();

            if (count($historicalAdminIds) > 0)
            {
                (new Admin\Core)->removeMerchantFromAdmins($merchant, $historicalAdminIds);
            }

            //Adding to  Unclaimed Merchants Group
            (new Group\Core)->addMerchantToGroups($merchant, [$this->unClaimedGroupId]);

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
                'id'           => $this->merchantId,
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
