<?php

namespace RZP\Models\TrustedBadge;

use Illuminate\Support\Facades\Mail;
use RZP\Diag\EventCode;
use RZP\Jobs\TrustedBadge;
use RZP\Mail\TrustedBadge\OptinRequest;
use RZP\Mail\TrustedBadge\OptoutNotify;
use RZP\Mail\TrustedBadge\Welcome;
use RZP\Models\Base;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\TrustedBadge\TrustedBadgeHistory\Entity as TrustedBadgeHistoryEntity;
use RZP\Models\TrustedBadge\TrustedBadgeHistory\Core as TrustedBadgeHistoryCore;

class Core extends Base\Core
{
    public function eligibilityCron()
    {
        try
        {
            $this->trace->info(TraceCode::RTB_ELIGIBILITY_CRON_REQUEST);

            $this->app['diag']->trackTrustedBadgeEvent(EventCode::TRUSTED_BADGE_CRON_INITIATED, []);

            $blacklistedMIDs = $this->repo->trusted_badge->fetchRTBBlacklistedMerchantIds();

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_blacklisted_mid',
                'blacklistedMerchantCount' => count($blacklistedMIDs),
            ]);

            /**
             * Merchant id query, check the following
             * 1. activation date < 120 days
             * 2. is razorpay org
             * 3. category2 not in (government, lending, govt education)
             * 4. registered - details table - business_type not in ('2','11')
             * 5. kyc done - details table - activation_status = 'activated
             * 6. merchant not in RTB blacklist
             */
            $merchantIdListWithInitialChecksPassed = $this->repo->merchant->getMerchantListEligibleForRTB($blacklistedMIDs);

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_merchants_with_initial_checks_passed',
                'merchantCountWithInitialChecks' => count($merchantIdListWithInitialChecksPassed),
            ]);

            $standardCheckoutEligibleMIDs = $this->getStandardCheckoutEligibleMerchantsList();

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_eligible_standard_checkout_merchants',
                'standardCheckoutEligibleMerchantsCount' => count($standardCheckoutEligibleMIDs),
            ]);

            $dmtMIDs = $this->getDMTMerchantsList();

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_dmt_merchants',
                'dmtMerchantsCount' => count($dmtMIDs),
            ]);

            $disputedMIDs = $this->repo->dispute->getLostOrClosedDisputeMerchantIdsInLast4Months();

            $this->trace->info(TraceCode::RTB_CRON_CHECKPOINT_REACHED, [
                'checkpoint'    => 'fetched_disputed_merchants',
                'disputedMerchantsCount' => count($disputedMIDs),
            ]);

            foreach ($merchantIdListWithInitialChecksPassed as $merchantId)
            {
                $eligibilityChecks = [
                    Entity::STANDARD_CHECKOUT_ELIGIBLE => in_array($merchantId, $standardCheckoutEligibleMIDs, true),
                    Entity::IS_DMT_MERCHANT            => in_array($merchantId, $dmtMIDs, true),
                    Entity::IS_DISPUTE_MERCHANT        => in_array($merchantId, $disputedMIDs, true),
                ];

                TrustedBadge::dispatch($this->mode, $merchantId, $eligibilityChecks);
            }

            return ['success' => true];
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::RTB_CRON_FAILURE);

            return ['success' => false];
        }
    }

    /**
     * Create or update a trusted badge entity & record it's history.
     *
     * @param string $merchantId
     * @param string $status
     */
    public function upsertStatus(string $merchantId, string $status): void
    {
        $this->trace->info(TraceCode::TRUSTED_BADGE_STATUS_UPSERT_REQUEST, [
            'merchantId' => $merchantId,
            'status'     => $status
        ]);

        $this->repo->transaction(function() use ($merchantId, $status)
        {
            // check if entity exists
            $trustedBadge = $this->repo->trusted_badge->fetchByMerchantId($merchantId);

            if(isset($trustedBadge))
            {
                // update entity if status not same
                $currentStatus = $trustedBadge->getStatus();

                if($status === $currentStatus)
                {
                    return;
                }

                $columnsToUpdate = [Entity::STATUS => $status];

                $this->repo->trusted_badge->updateByMerchantId($merchantId, $columnsToUpdate);
            }
            else
            {
                // create trusted badge entity
                $trustedBadge = new Entity;

                $trustedBadgeAttributes = [
                    Entity::MERCHANT_ID => $merchantId,
                    Entity::STATUS      => $status,
                ];

                $trustedBadge->build($trustedBadgeAttributes);

                $this->repo->saveOrFail($trustedBadge);
            }

            $this->recordHistory($merchantId, $status, $trustedBadge->merchant_status);
            $this->triggerMailers($trustedBadge->merchant, $status, $trustedBadge->merchant_status);
            $this->deleteTrustedBadgeStatusInRedis($merchantId);
        });
    }

    public function upsertMerchantStatus(string $merchantId, string $merchantStatus): void
    {
        $this->trace->info(TraceCode::TRUSTED_BADGE_MERCHANT_STATUS_UPSERT_REQUEST, [
            'merchantId'         => $merchantId,
            'merchantStatus'     => $merchantStatus
        ]);

        $this->repo->transaction(function() use ($merchantId, $merchantStatus)
        {
            // check if entity exists
            $trustedBadge = $this->repo->trusted_badge->fetchByMerchantId($merchantId);

            if(isset($trustedBadge))
            {
                // update entity if status not same
                $currentMerchantStatus = $trustedBadge->getMerchantStatus();

                if($merchantStatus === $currentMerchantStatus)
                {
                    return;
                }

                $columnsToUpdate = [Entity::MERCHANT_STATUS => $merchantStatus];

                $this->repo->trusted_badge->updateByMerchantId($merchantId, $columnsToUpdate);
            }
            else
            {
                // create trusted badge entity
                $trustedBadge = new Entity;

                $trustedBadgeAttributes = [
                    Entity::MERCHANT_ID     => $merchantId,
                    Entity::STATUS          => Entity::INELIGIBLE,
                    Entity::MERCHANT_STATUS => $merchantStatus
                ];

                $trustedBadge->build($trustedBadgeAttributes);

                $this->repo->saveOrFail($trustedBadge);
            }

            $this->recordHistory($merchantId, $trustedBadge->status, $merchantStatus);
            $this->triggerMailers($trustedBadge->merchant, $trustedBadge->status, $merchantStatus);
            $this->deleteTrustedBadgeStatusInRedis($merchantId);
        });
    }

    public function isTrustedBadgeLiveForMerchant($merchantId): bool
    {
        try
        {
            $status = $this->getTrustedBadgeStatusFromRedis($merchantId);

            if(isset($status))
            {
                return $status === true;
            }

            $isMerchantLiveOnRTB = $this->repo->trusted_badge->isMerchantLiveOnRTB($merchantId);

            $this->addTrustedBadgeKeyInRedis($merchantId, $isMerchantLiveOnRTB);

            return $isMerchantLiveOnRTB;
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TRUSTED_BADGE_LIVE_CHECK_ERROR,[
                'merchantId' => $merchantId,
            ]);

            return false;
        }
    }

    protected function getTrustedBadgeRedisKey($merchantId): string
    {
        return 'is_rtb_live_for_mid_'.$merchantId.'_'.$this->mode.'_mode';
    }

    protected function getTrustedBadgeStatusFromRedis($merchantId)
    {
        $redisKey = $this->getTrustedBadgeRedisKey($merchantId);

        return $this->app['cache']->get($redisKey);
    }

    protected function addTrustedBadgeKeyInRedis($merchantId, $isLive): void
    {
        $redisKey = $this->getTrustedBadgeRedisKey($merchantId);

        /** @var $ttl - ttl of 1 day */
        $ttl = 24 * 60 * 60;

        $this->app['cache']->put($redisKey, $isLive, $ttl);
    }

    protected function deleteTrustedBadgeStatusInRedis($merchantId): void
    {
        $redisKey = $this->getTrustedBadgeRedisKey($merchantId);

        $this->app['cache']->delete($redisKey);
    }

    public function isDelistedCheck($merchantId): bool
    {
        $isDelisted = false;

        // find out first eligible from history
        $firstEligibleHistory = $this->repo->trusted_badge_history->fetchFirstEligible($merchantId);

        if (isset($firstEligibleHistory))
        {
            // check for ineligible status after first eligible
            $isDelistedHistory = $this->repo->trusted_badge_history->fetchIsDelisted($merchantId, $firstEligibleHistory[Entity::CREATED_AT]);

            if (isset($isDelistedHistory))
            {
                $isDelisted = true;
            }

        }
        return $isDelisted;
    }

    protected function recordHistory(string $merchantId, string $status, string $merchantStatus): void
    {
        $attributes = [
            TrustedBadgeHistoryEntity::MERCHANT_ID => $merchantId,
            TrustedBadgeHistoryEntity::STATUS      => $status,
        ];

        if ($merchantStatus) {
            $attributes[TrustedBadgeHistoryEntity::MERCHANT_STATUS] = $merchantStatus;
        }

        (new TrustedBadgeHistoryCore())->insertHistory($attributes);
    }

    protected function triggerMailers(Merchant $merchant, string $status, string $merchantStatus): void
    {
        $rtbHistoryCore = new TrustedBadgeHistoryCore();

        if ($status === Entity::ELIGIBLE && $rtbHistoryCore->isFirstTimeEligible($merchant->getId())) {
            $this->triggerWelcomeMailer($merchant);

            return;
        }

        if (
            $status === Entity::ELIGIBLE &&
            $merchantStatus === Entity::OPTOUT &&
            $rtbHistoryCore->isFirstTimeEligibleAfterOptout($merchant->getId())
        ) {
            $this->triggerOptinRequestMailer($merchant);

            return;
        }

        if ($merchantStatus === Entity::OPTOUT && $rtbHistoryCore->isFirstTimeOptout($merchant->getId())) {
            $this->triggerOptoutMailer($merchant);

            return;
        }
    }

    /**
     * Initiate sending of welcome email to merchant & fire analytics event for the same
     *
     * @param Merchant $merchant
     */
    protected function triggerWelcomeMailer(Merchant $merchant): void
    {
        Mail::queue(new Welcome($merchant->getId(), $merchant->getEmail()));

        $this->app['diag']->trackTrustedBadgeEvent(
            EventCode::TRUSTED_BADGE_WELCOME_MAIL_INITIATED,
            ['merchantId' => $merchant->getId()]
        );

        $this->trace->info(TraceCode::TRUSTED_BADGE_MAIL_TRIGGER, [
            'mailer'     => 'welcome',
            'merchantId' => $merchant->getId(),
        ]);
    }

    /**
     * Initiate sending of optin request email to merchant
     *
     * @param Merchant $merchant
     */
    protected function triggerOptinRequestMailer(Merchant $merchant): void
    {
        Mail::queue(new OptinRequest($merchant->getId(), $merchant->getEmail()));

        $this->app['diag']->trackTrustedBadgeEvent(
            EventCode::TRUSTED_BADGE_OPTIN_REQUEST_MAIL_INITIATED,
            ['merchantId' => $merchant->getId()]
        );

        $this->trace->info(TraceCode::TRUSTED_BADGE_MAIL_TRIGGER, [
            'mailer'     => 'optin_request',
            'merchantId' => $merchant->getId(),
        ]);
    }

    /**
     * Initiate sending of optout email to merchant
     *
     * @param Merchant $merchant
     */
    protected function triggerOptoutMailer(Merchant $merchant): void
    {
        Mail::queue(new OptoutNotify($merchant->getId(), $merchant->getEmail()));

        $this->app['diag']->trackTrustedBadgeEvent(
            EventCode::TRUSTED_BADGE_OPTOUT_NOTIFY_MAIL_INITIATED,
            ['merchantId' => $merchant->getId()]
        );

        $this->trace->info(TraceCode::TRUSTED_BADGE_MAIL_TRIGGER, [
            'mailer'     => 'optout',
            'merchantId' => $merchant->getId(),
        ]);
    }

    protected function getStandardCheckoutEligibleMerchantsList($retryCount = 0): array
    {
        try
        {
            $rawQuery = "select * from hive.aggregate_pa.rtb_eligibility_trxn_check_v1";

            $queryResult = $this->app['datalake.presto']->getDataFromDataLake($rawQuery);

            return array_column($queryResult, Entity::MERCHANT_ID);
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::RTB_DATALAKE_QUERY_FAILURE, [
                'query'      => 'standard_checkout_eligibility_query',
                'retryCount' => $retryCount,
            ]);

            if($retryCount < 2)
            {
                return $this->getStandardCheckoutEligibleMerchantsList($retryCount+1);
            }
            throw $ex;
        }
    }

    protected function getDMTMerchantsList($retryCount = 0): array
    {
        try
        {
            $rawQuery = "select distinct vertical, id as merchant_id from hive.aggregate_ba.verticals where vertical = 'dmt'";

            $queryResult = $this->app['datalake.presto']->getDataFromDataLake($rawQuery);

            return array_column($queryResult, Entity::MERCHANT_ID);
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::RTB_DATALAKE_QUERY_FAILURE, [
                'query'      => 'dmt_merchants_query',
                'retryCount' => $retryCount,
            ]);

            if($retryCount < 2)
            {
                return $this->getDMTMerchantsList($retryCount+1);
            }
            throw $ex;
        }
    }
}
