<?php

namespace RZP\Jobs;

use RZP\Diag\EventCode;
use RZP\Models\TrustedBadge\Core;
use RZP\Models\TrustedBadge\Entity;
use RZP\Trace\TraceCode;
use App;

class TrustedBadge extends Job
{
    protected $queueConfigKey = 'trusted_badge';

    public $timeout = 3600;

    /** @var Core */
    protected $rtbCore;

    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        $this->rtbCore = new Core();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        parent::handle();

        try
        {
            $this->trace->info(TraceCode::RTB_ELIGIBILITY_CRON_STARTED);

            $blacklistedMIDs = $this->repoManager->trusted_badge->fetchRTBBlacklistedMerchantIds();

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_blacklisted_mid',
                'blacklistedMerchantCount' => count($blacklistedMIDs),
            ]);

            /**
             * Merchant id query, check the following
             * 1. activation date < 90 days
             * 2. is razorpay org
             * 3. category2 not in (government, lending, govt education)
             * 4. registered - details table - business_type not in ('2','11')
             * 5. kyc done - details table - activation_status = 'activated
             * 6. merchant not in RTB blacklist
             */
            $merchantIdListWithInitialChecksPassed = $this->repoManager->merchant->getMerchantListEligibleForRTB($blacklistedMIDs);

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_merchants_with_initial_checks_passed',
                'merchantCountWithInitialChecks' => count($merchantIdListWithInitialChecksPassed),
            ]);

            $standardCheckoutEligibleMIDs = array_flip($this->rtbCore->getStandardCheckoutEligibleMerchantsList());

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_eligible_standard_checkout_merchants',
                'standardCheckoutEligibleMerchantsCount' => count($standardCheckoutEligibleMIDs),
            ]);

            $dmtMIDs = array_flip($this->rtbCore->getDMTMerchantsList());

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_dmt_merchants',
                'dmtMerchantsCount' => count($dmtMIDs),
            ]);

            $disputedMIDs = array_flip($this->rtbCore->getMerchantsWithDisputeLossRateGreaterThanThreshold());

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_disputed_merchants',
                'disputedMerchantsCount' => count($disputedMIDs),
            ]);

            foreach ($merchantIdListWithInitialChecksPassed as $merchantId)
            {
                $eligibilityChecks = [
                    Entity::STANDARD_CHECKOUT_ELIGIBLE => array_key_exists($merchantId, $standardCheckoutEligibleMIDs),
                    Entity::IS_DMT_MERCHANT            => array_key_exists($merchantId, $dmtMIDs),
                    Entity::IS_DISPUTE_MERCHANT        => array_key_exists($merchantId, $disputedMIDs),
                ];

                $this->processMerchantWithEligibilityChecks($merchantId, $eligibilityChecks);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::RTB_CRON_FAILURE);
        }

        $this->delete();
    }

    /**
     * @param string $merchantId
     * @param array  $eligibilityChecks
     */
    protected function processMerchantWithEligibilityChecks(string $merchantId, array $eligibilityChecks): void
    {
        try
        {
            $isMerchantEligibleForRTB = $eligibilityChecks[Entity::STANDARD_CHECKOUT_ELIGIBLE] === true &&
                                        $eligibilityChecks[Entity::IS_DMT_MERCHANT] === false &&
                                        $eligibilityChecks[Entity::IS_DISPUTE_MERCHANT] === false;

            $status  = $isMerchantEligibleForRTB ? Entity::ELIGIBLE : Entity::INELIGIBLE;

            $this->rtbCore->upsertStatus($merchantId, $status);

            $this->trace->info(
                TraceCode::RTB_CRON_MERCHANT_PROCESSED,
                [
                    'mode'            => $this->mode,
                    'merchantId'      => $merchantId,
                    'status'          => $status,
                    'eligibilityChecks' => $eligibilityChecks,
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::RTB_CRON_MERCHANT_PROCESSING_FAILED,
                [
                    'mode'        => $this->mode,
                    'merchantId'  => $merchantId,
                    'eligibilityChecks' => $eligibilityChecks,
                ]
            );

            app('diag')->trackTrustedBadgeEvent(EventCode::TRUSTED_BADGE_CRON_FAILURE, [
                'merchantId'      => $merchantId,
            ]);
        }
    }
}
