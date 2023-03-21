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

    /** @var bool if dry run = true, that means we don't update DB, just log the results */
    protected $dryRun = false;

    public function __construct(string $mode, bool $dryRun = false)
    {
        parent::__construct($mode);

        $this->dryRun = $dryRun;
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
            $this->trace->info(TraceCode::RTB_ELIGIBILITY_CRON_STARTED, [
                'dryRun' => $this->dryRun,
            ]);

            $blacklistedOrWhitelistedMIDs = $this->repoManager->trusted_badge->fetchRTBBlacklistedOrWhitelistedMerchantIds();

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_blacklisted_or_whitelisted_mid',
                'blacklistedOrWhitelistedMerchantCount' => count($blacklistedOrWhitelistedMIDs),
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
            $merchantIdListWithInitialChecksPassed = $this->repoManager->merchant->getMerchantListEligibleForRTB($blacklistedOrWhitelistedMIDs);

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_merchants_with_initial_checks_passed',
                'merchantCountWithInitialChecks' => count($merchantIdListWithInitialChecksPassed),
            ]);

            $merchantIdListWithRiskTags = array_flip($this->repoManager->merchant_detail->getMerchantsWithRiskTags());

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_merchants_with_risk_tags',
                'merchantCountWithRiskTags' => count($merchantIdListWithRiskTags),
            ]);

            $standardCheckoutEligibleMIDs =
                array_flip($this->repoManager->trusted_badge->getStandardCheckoutEligibleMerchantsList());

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_eligible_standard_checkout_merchants',
                'standardCheckoutEligibleMerchantsCount' => count($standardCheckoutEligibleMIDs),
            ]);

            $highTransactingVolumeMIDs =
                array_flip($this->repoManager->trusted_badge->getHighTransactingVolumeMerchantsList());

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_merchants_with_high_transacting_volume',
                'merchantsHavingHighTransactingVolumeMidsCount' => count($highTransactingVolumeMIDs),
            ]);

            $lowTransactingButRTBEligibleMIDs =
                array_flip($this->rtbCore->getMerchantsHavingLowTransactionsButRTBEligibleList());

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_merchants_with_low_transactions_but_rtb_eligible',
                'merchantsHavingLowTransactionsButRTBEligibleMidsCount' => count($lowTransactingButRTBEligibleMIDs),
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
                    Entity::HIGH_TRANSACTING_VOLUME_MERCHANT => array_key_exists($merchantId, $highTransactingVolumeMIDs),
                    Entity::LOW_TRANSACTING_BUT_RTB_ELIGIBLE_MERCHANT =>
                        array_key_exists($merchantId, $lowTransactingButRTBEligibleMIDs),
                    Entity::IS_RISK_MERCHANT => array_key_exists($merchantId, $merchantIdListWithRiskTags),
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
            $isMerchantEligibleForRTB = $this->isMerchantEligibleForRTB($eligibilityChecks) ;

            $status  = $isMerchantEligibleForRTB ? Entity::ELIGIBLE : Entity::INELIGIBLE;

            if ($this->dryRun === false)
            {
                $this->rtbCore->upsertStatus($merchantId, $status);
            }

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

    /**
     * @param array $eligibilityChecks
     * @return bool
     */
    private function isMerchantEligibleForRTB(array $eligibilityChecks): bool
    {
        if (
            $eligibilityChecks[Entity::IS_DMT_MERCHANT] === true ||
            $eligibilityChecks[Entity::IS_DISPUTE_MERCHANT] === true ||
            $eligibilityChecks[Entity::IS_RISK_MERCHANT] === true
        ) {
            return false;
        }

        if (
            $eligibilityChecks[Entity::STANDARD_CHECKOUT_ELIGIBLE] === true ||
            $eligibilityChecks[Entity::HIGH_TRANSACTING_VOLUME_MERCHANT] === true ||
            $eligibilityChecks[Entity::LOW_TRANSACTING_BUT_RTB_ELIGIBLE_MERCHANT] === true
        ) {
            return true;
        }

        return false;
    }
}
