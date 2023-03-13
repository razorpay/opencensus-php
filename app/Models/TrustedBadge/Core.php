<?php

namespace RZP\Models\TrustedBadge;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use RZP\Diag\EventCode;
use RZP\Jobs\TrustedBadge;
use RZP\Mail\TrustedBadge\OptinRequest;
use RZP\Mail\TrustedBadge\OptoutNotify;
use RZP\Mail\TrustedBadge\Welcome;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\TrustedBadge\TrustedBadgeHistory\Entity as TrustedBadgeHistoryEntity;
use RZP\Models\TrustedBadge\TrustedBadgeHistory\Core as TrustedBadgeHistoryCore;

class Core extends Base\Core
{
    /** @var float the maximum allowed dispute loss rate for a merchant to be eligible for RTB */
    public const DISPUTE_LOSS_RATE_THRESHOLD = 0.1;

    public function eligibilityCron(array $input): array
    {
        try
        {
            $dryRun = $input['dry_run'] ?? false;

            $this->trace->info(TraceCode::RTB_ELIGIBILITY_CRON_REQUEST, [
                'dryRun' => $dryRun,
            ]);

            $this->app['diag']->trackTrustedBadgeEvent(EventCode::TRUSTED_BADGE_CRON_INITIATED, []);

            TrustedBadge::dispatch($this->mode, $dryRun);

            return ['success' => true];
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::RTB_CRON_FAILURE);

            return ['success' => false];
        }
    }

    /**
     * This method returns all merchantIds whose dispute loss rate
     * exceeded DISPUTE_LOSS_RATE_THRESHOLD
     *
     * @param float $threshold DISPUTE_LOSS_RATE_THRESHOLD
     * @return array list of merchantIds who are no longer eligible for RTB
     */
    public function getMerchantsWithDisputeLossRateGreaterThanThreshold(float $threshold = self::DISPUTE_LOSS_RATE_THRESHOLD): array
    {
        $disputedMerchantIds = [];

        $fourMonthsAgo = Carbon::now()->subDays(120)->getTimestamp();

        $disputeCountForMerchants = $this->repo->dispute->getCountOfLostOrClosedDisputesForMerchants($fourMonthsAgo);

        $merchantIdsWithAtLeastOneDispute = array_keys($disputeCountForMerchants);

        // Idea here is to not pass all MIDs at once but instead send them in chunks
        $chunkedMerchantIds = array_chunk($merchantIdsWithAtLeastOneDispute , 2000);

        $paymentCountForDisputedMerchants = [];

        foreach ($chunkedMerchantIds as $merchantIds)
        {
            $paymentCountChunks = $this->repo->payment->getPaymentsCountForMerchantsFromDataLakePresto(
                $merchantIds,
                $fourMonthsAgo
            );

            $paymentCountForDisputedMerchants[] = $paymentCountChunks;
        }

        $paymentCountForDisputedMerchants = array_merge([], ...$paymentCountForDisputedMerchants);

        foreach ($disputeCountForMerchants as $merchantId => $disputeCount)
        {
            if (array_key_exists($merchantId , $paymentCountForDisputedMerchants) === false)
            {
                $disputedMerchantIds[] = $merchantId;
                continue;
            }

            $paymentCount = $paymentCountForDisputedMerchants[$merchantId];

            $disputeLossRate = $this->calculateDisputeLossRate($disputeCount, $paymentCount);

            if ($disputeLossRate > $threshold)
            {
                $disputedMerchantIds[] = $merchantId;
            }
        }

        return $disputedMerchantIds;
    }

    /**
     * Dispute Loss rate is one of parameters used to verify eligibility of merchant for RTB.
     * We consider only those disputes and payments within 4 months
     *
     * @param int $disputeCount Number of disputes lost/closed by the merchant
     * @param int $paymentCount Number of successful payments processed by the merchant
     * @return float
     */
    protected function calculateDisputeLossRate(int $disputeCount, int $paymentCount): float
    {
        return (($disputeCount * 100) / $paymentCount);
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
                if ($status === Entity::INELIGIBLE) {
                    /**
                     * Do not create default records for ineligible merchants
                     * i.e first record will be created only on the following events
                     *      1. Blacklisted by Admin
                     *      2. Waitlist by Merchant
                     *      3. Eligible by Cron
                     */
                    return;
                }

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

    public function getRTBExperimentDetails($merchantId, $contact = '')
    {
        try
        {
            if($this->isRTBExperimentMerchant($merchantId) === false)
            {
                return [
                    'experiment' => false,
                ];
            }

            if(empty($contact) === true)
            {
                return [
                    'experiment' => true,
                ];
            }

            $isNewUser = $this->repo->payment->isNewCustomerToMerchant($merchantId, $contact);

            if($isNewUser === false)
            {
                return [
                    'experiment' => true,
                    'variant'    => 'not_applicable',
                ];
            }

            return [
                'experiment' => true,
                'variant'    => $this->getRTBSplitzVariant($merchantId),
            ];
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::RTB_EXPERIMENT_DETAILS_ERROR, [
                'merchantId' => $merchantId,
            ]);

            return [
                'experiment' => false,
            ];
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



    public function getDMTMerchantsList($retryCount = 0): array
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

    /**
     * @param $merchantId
     * @return string
     */
    protected function getRTBSplitzVariant($merchantId): string
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.rtb_splitz_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $merchantId,
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            return $response['response']['variant']['name'] ?? 'not_applicable';
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::RTB_SPLITZ_ERROR);

            return 'not_applicable';
        }
    }

    /**
     * @param $merchantId
     * @return bool
     */
    protected function isRTBExperimentMerchant($merchantId): bool
    {
        $redis = Redis::Connection();

        return $redis->sismember(Entity::REDIS_EXPERIMENT_KEY, $merchantId);
    }

    /**
     * This method returns list of merchants with less than 100 txns on std checkout
     * but still eligible for RTB if they have passed additional checks. Those are
     * 1. Must have more than 25 txns on standard checkout in last 4 months
     * 2. Z value of refund rate of merchant must be within set limits. -0.5 <= Z <= 1.5
     *
     * @return array
     * @throws \Exception
     */
    public function getMerchantsHavingLowTransactionsButRTBEligibleList(): array
    {
        $lowTransactionsMerchantsData = $this->repo->trusted_badge->getLowTransactingMerchantsData();

        return $this->getMerchantsWithZValueWithinLimits($lowTransactionsMerchantsData);
    }

    /**
     * This method fetches merchants who all have Z value within limits.
     *
     * @param array $lowTransactionsMerchantsData
     * @return array
     */
    protected function getMerchantsWithZValueWithinLimits(array $lowTransactionsMerchantsData): array
    {
        $merchantsWithZValueWithinLimits = [];

        $merchantIds = array_column($lowTransactionsMerchantsData, Entity::MERCHANT_ID);

        $merchantsCategories = $this->repo->merchant->getMerchantsCategories($merchantIds);

        foreach ($lowTransactionsMerchantsData as $merchantData)
        {
            $merchantId = $merchantData['merchant_id'];

            $category = $merchantsCategories[$merchantId] ?? '';

            if(array_key_exists($category, Constants::CATEGORY_WISE_STATISTICAL_DATA) === false)
            {
                continue;
            }

            $zValue = $this->calculateZValue($category, $merchantData['refund_rate']);

            if (-0.5 <= $zValue && $zValue <= 1.5)
            {
                $merchantsWithZValueWithinLimits[] = $merchantId;
            }
        }

        return $merchantsWithZValueWithinLimits;
    }

    /**
     * This method takes in category and refund rate and calculates Z value
     * Z value = ((Refund rate of merchant - Mean refund rate of the category that the merchant belongs to)/
     *           (standard deviation of refund rate of the category that the merchant belongs to))
     *
     * @param string $category
     * @param float $refundRate
     * @return float Z value of merchant
     */
    private function calculateZValue(string $category, float $refundRate): float
    {
        $categoryData = Constants::CATEGORY_WISE_STATISTICAL_DATA[$category];

        return (($refundRate - $categoryData[Constants::MEAN]) / $categoryData[Constants::STANDARD_DEVIATION]);
    }
}
