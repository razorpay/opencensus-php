<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Partner\Config as PartnerConfig;

class BackFillReferredApplication extends Job
{
    const RETRY_INTERVAL = 300;

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
                TraceCode::REFERRED_APPLICATION_BACKFILL_REQUEST,
                [
                    'mode' => $this->mode,
                    'merchant_ids' => $this->merchantIds,
                ]
            );

            foreach ($this->merchantIds as $merchantId) {
                try
                {
                    $merchant = (new Merchant\Repository())->fetchMerchantOnConnection($merchantId, $this->mode);

                    $appIds = (new Merchant\Core)->getPartnerApplicationIds($merchant);

                    if (count($appIds) === 1)
                    {
                        $isAppPresent = (new MerchantApplications\Core())->isMerchantAppPresent($appIds[0]);

                        if ($isAppPresent === true)
                        {
                            $merchantApp = (new MerchantApplications\Repository())->fetchMerchantApplication($appIds[0], Merchant\Constants::APPLICATION_ID);

                            if ($merchantApp->first()->getApplicationType() === MerchantApplications\Entity::MANAGED)
                            {
                                (new Merchant\Core())->createReferredAppAndPartnerConfigForManaged($merchant);

                            }
                        }
                        else
                        {
                            throw new \RZP\Exception\DbQueryException(['Merchant application not present']);
                        }
                    }
                    else if ((count($appIds) === 2) and ($merchant->isFullyManagedPartner() === true))
                    {
                        $config = [
                            PartnerConfig\Entity::DEFAULT_PLAN_ID       => Pricing\DefaultPlan::SUBMERCHANT_PRICING_OF_ONBOARDED_PARTNERS,
                            PartnerConfig\Entity::IMPLICIT_PLAN_ID      => Pricing\DefaultPlan::PARTNER_COMMISSION_PLAN_ID,
                            PartnerConfig\Entity::COMMISSIONS_ENABLED   => true,
                            PartnerConfig\Constants::PARTNER_ID         => $merchant->getId(),
                        ];

                        // create new partner config for referred app for full_managed partners
                        $referredApp = (new Merchant\Core())->fetchPartnerApplication($merchant, MerchantApplications\Entity::REFERRED);

                        $referredConfig = (new PartnerConfig\Core())->fetch($referredApp);

                        if ($referredConfig === null)
                        {
                            (new PartnerConfig\Core)->create($referredApp, $config);
                        }
                    }
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::REFERRED_APPLICATION_CREATE_ERROR,
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
                TraceCode::REFERRED_APPLICATION_BACKFILL_JOB_ERROR,
                [
                    'mode' => $this->mode,
                    'merchant_ids' => $this->merchantIds,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::REFERRED_APPLICATION_BACKFILL_QUEUE_DELETE, [
                'id' => $this->merchantIds,
                'job_attempts' => $this->attempts(),
                'message' => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
