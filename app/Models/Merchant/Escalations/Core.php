<?php


namespace RZP\Models\Merchant\Escalations;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Notifications\Onboarding\Events;
use RZP\Models\Coupon\Constants as CouponCodeConstants;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Account as MerchantAccount;
use RZP\Models\Merchant\Escalations\Actions\Entity as ActionEntity;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;

class Core extends Base\Core
{
    protected $cache;

    public function __construct()
    {
        parent::__construct();

        $this->cache = $this->app['cache'];
    }

    public function getEscalationConfigForThresholdAndMilestone($merchantDetails, $threshold, $milestone)
    {
        if (isset(Constants::PAYMENTS_ESCALATION_MATRIX[$threshold]) === false)
        {
            return null;
        }

        $configs = Constants::PAYMENTS_ESCALATION_MATRIX[$threshold];

        foreach ($configs as $config)
        {
            if ($config[Constants::MILESTONE] === $milestone)
            {
                if ((new Handler)->canTriggerEscalation($merchantDetails, $config) === true)
                {
                    return $config;
                }
            }
        }

        return null;
    }

    private function getLimitOnEscalationMilestone($merchant)
    {
        $limitData = [];

        $milestone = $merchant->merchantDetail->getAttribute('activation_form_milestone');

        $limitData['settlement'] = 1500000;

        if ($milestone === 'L1')
        {
            $limitData['payment'] = 1500000;
        }
        else
        {
            if ($milestone === 'L2')
            {
                $limitData['payment'] = 1000000000;
            }
        }

        return $limitData;
    }

    public function fetchLatestEscalationForMerchant($merchant)
    {
        $response = [];

        $escalation = $this->repo->merchant_onboarding_escalations->fetchLatestEscalation($merchant->getId());

        if (empty($escalation) === false)
        {
            $action = $this->repo->onboarding_escalation_actions->fetchActionForEscalation($escalation->getId());

            $response = $escalation->toArray();

            if (empty($action) === false)
            {
                $response['action'] = [
                    ActionEntity::STATUS => $action->getAttribute(ActionEntity::STATUS)
                ];
            }
        }

        $response['limit'] = $this->getLimitOnEscalationMilestone($merchant);

        return $response;
    }

    private function fetchEscalationMapForMerchants(array $merchantIdList)
    {
        $escalations = $this->repo->merchant_onboarding_escalations->fetchEscalationsForMerchants($merchantIdList);

        $escalationMap = [];

        foreach ($escalations as $escalation)
        {
            $mid = $escalation[Entity::MERCHANT_ID];

            if (isset($escalationMap[$mid]) === false)
            {
                $escalationMap[$mid] = [];
            }

            $escalationMap[$mid][] = $escalation;
        }

        return $escalationMap;
    }

    private function getLastCronTime(string $cacheKey = Constants::ESCALATION_CACHE_KEY)
    {
        $lastCronTime = $this->cache->get($cacheKey);

        if (empty($lastCronTime))
        {
            if ($cacheKey === Constants::SEGMENT_MTU_CACHE_KEY)
            {
                /*
                 * since ESCALATION_CACHE_KEY is the default one and we are currently using it
                 * we can override SEGMENT_MTU_CACHE_KEY with its value;
                 */
                return $this->getLastCronTime();
            }

            return Carbon::now()->subDays(Constants::TIME_BOUND_THRESHOLD)->getTimestamp();
        }

        return $lastCronTime;
    }

    private function updateLastCronTime(string $cacheKey)
    {
        $this->cache->put($cacheKey, Carbon::now()->getTimestamp());
    }

    public function pushTransactionDetailsToSegmentCron()
    {
        $lastCronTime = $this->getLastCronTime(Constants::TRANSACTION_CRON_CACHE_KEY);

        /*
         * Update last Cron time instantly, since processing of cron may take another 5-10 mins
         * and during that time another payments can happen
         */
        $this->updateLastCronTime(Constants::TRANSACTION_CRON_CACHE_KEY);

        // Filter out all merchants that have transacted since last time cron ran
        $transactedMerchants = $this->repo->transaction->fetchTransactedMerchants(
            'payment', $lastCronTime, false);

        $this->trace->info(TraceCode::TRANSACTION_DETAILS_CRON_TRACE, [
            'last_cron_time'  => $lastCronTime,
            'type'            => 'transaction_cron',
            'merchants_count' => count($transactedMerchants),
        ]);

        $merchantIdChunks = array_chunk($transactedMerchants, 1000);

        foreach ($merchantIdChunks as $merchantIdChunk)
        {
            $druidData = (new Merchant\Service)->getDataFromDruidForMerchantIds($merchantIdChunk);

            foreach ($druidData as $data)
            {
                $segmentProperties = [
                    Merchant\Service::SEGMENT_DATA_USER_DAYS_TILL_LAST_TRANSACTION => $data[Merchant\Service::SEGMENT_DATA_USER_DAYS_TILL_LAST_TRANSACTION] ?: 'NULL',
                    Merchant\Service::SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV          => $data[Merchant\Service::SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV] ?: 'NULL',
                    Merchant\Service::SEGMENT_DATA_AVERAGE_MONTHLY_GMV             => $data[Merchant\Service::SEGMENT_DATA_AVERAGE_MONTHLY_GMV] ?: 'NULL',
                    Merchant\Service::SEGMENT_DATA_PRIMARY_PRODUCT_USED            => $data[Merchant\Service::SEGMENT_DATA_PRIMARY_PRODUCT_USED] ?: 'NULL',
                    Merchant\Service::SEGMENT_DATA_PPC                             => $data[Merchant\Service::SEGMENT_DATA_PPC] ?: 'NULL',
                    Merchant\Service::SEGMENT_DATA_AVERAGE_MONTHLY_TRANSACTIONS    => $data[Merchant\Service::SEGMENT_DATA_AVERAGE_MONTHLY_TRANSACTIONS] ?: 'NULL',
                    Merchant\Service::SEGMENT_DATA_PG_ONLY                         => isset($data[Merchant\Service::SEGMENT_DATA_PG_ONLY]) ? $data[Merchant\Service::SEGMENT_DATA_PG_ONLY] : 'NULL',
                    Merchant\Service::SEGMENT_DATA_PL_ONLY                         => isset($data[Merchant\Service::SEGMENT_DATA_PL_ONLY]) ? $data[Merchant\Service::SEGMENT_DATA_PL_ONLY] : 'NULL',
                    Merchant\Service::SEGMENT_DATA_PP_ONLY                         => isset($data[Merchant\Service::SEGMENT_DATA_PP_ONLY]) ? $data[Merchant\Service::SEGMENT_DATA_PP_ONLY] : 'NULL'
                ];

                $merchantId = $data['merchant_details_merchant_id'];

                $this->trace->info(TraceCode::TRANSACTION_DETAILS_CRON_TRACE, [
                    'type'          => 'transaction_cron',
                    'merchant_id'   => $merchantId,
                    'segment_properties'    => $segmentProperties
                ]);

                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $this->app['segment-analytics']->pushIdentify($merchant, $segmentProperties);
            }
        }

        $this->app['segment-analytics']->buildRequestAndSend();
    }

    public function handleMtuSegmentEvent()
    {
        $lastCronTime = $this->getLastCronTime(Constants::SEGMENT_MTU_CACHE_KEY);

        /*
         * Update last Cron time instantly, since processing of cron may take another 5-10 mins
         * and during that time another payments can happen
         */
        $this->updateLastCronTime(Constants::SEGMENT_MTU_CACHE_KEY);

        // Filter out all merchants that have transacted since last time cron ran
        $transactedMerchants = $this->repo->transaction->fetchTransactedMerchants(
            'payment', $lastCronTime, false);

        $this->trace->info(TraceCode::ESCALATION_CRON_TRACE, [
            'last_cron_time'  => $lastCronTime,
            'type'            => 'segment_mtu_init',
            'merchants_count' => count($transactedMerchants),
        ]);

        $merchantIdChunks = array_chunk($transactedMerchants, 100);
        $merchantIdList   = [];

        foreach ($merchantIdChunks as $merchantIdChunk)
        {
            $filteredMerchants = $this->repo->transaction->filterMerchantsWithFirstTransactionAboveTimestamp(
                $merchantIdChunk, $lastCronTime);

            $this->trace->info(TraceCode::ESCALATION_CRON_TRACE, [
                'last_cron_time'  => $lastCronTime,
                'type'            => 'segment_mtu',
                'merchants_count' => count($filteredMerchants),
                'merchants'       => $filteredMerchants
            ]);

            if (empty($filteredMerchants) === false)
            {
                $merchantIdList = array_merge($merchantIdList, $filteredMerchants);
            }
        }

        foreach ($merchantIdList as $merchantId)
        {
            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $previousActivationStatus = $this->repo->state->getPreviousActivationStatus($merchant->getId());

            $properties = [
                'mtu'                         => true,
                'first_transaction_timestamp' => Carbon::now()->getTimestamp(),
                'activation_status'           => $merchant->merchantDetail->getActivationStatus(),
                'previous_activation_status'  => $previousActivationStatus['name']
            ];

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $properties, SegmentEvent::MTU_TRANSACTED);
        }

        $this->app['segment-analytics']->buildRequestAndSend();
    }

    /**
     * Main method that triggers payment breach escalation for merchants
     * Filtering merchants is done using these queries:
     * - fetch all merchants which are not in end states. If timeBound is true, only merchants that are created in
     *   last 3 months are fetched (This is done to avoid heavy queries that fetches all merchants)
     * - fetch GMV of these merchants who've breached the lowest payment threshold (as of now its 1000 Rs)
     *
     * @param false $timeBound
     */
    public function triggerPaymentEscalations($timeBound = false)
    {
        if ($timeBound === true)
        {
            $lastCronTime = $this->getLastCronTime();

            $this->trace->info(TraceCode::ESCALATION_CRON_TRACE, [
                'last_cron_time' => $lastCronTime,
                'type'           => 'payments_escalation',
            ]);
            $this->updateLastCronTime(Constants::ESCALATION_CACHE_KEY);
            $merchantIdList = $this->repo->transaction->fetchTransactedMerchants('payment', $lastCronTime);

            $merchantIdList = $this->repo->merchant_detail->filterMerchantIdsByActivationStatus(
                $merchantIdList, DetailStatus::OPEN_STATUSES);
        }
        else
        {
            // fetch all the merchants who are not in end states
            $merchantIdList = $this->repo->merchant_detail->fetchMerchantIdsByActivationStatus(
                DetailStatus::OPEN_STATUSES);
        }

        // fetch all merchants who've atleast breached lowest payments threshold
        $merchantsGMVList = $this->repo->transaction->fetchTotalAmountByTransactionTypeAboveThreshold(
            $merchantIdList, 'payment', Constants::LOWEST_PAYMENTS_THRESHOLD
        );

        $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
            'merchants_count' => count($merchantsGMVList),
            'type'            => 'payments_escalation'
        ]);

        if (empty($merchantsGMVList) === true)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'merchants_count' => count($merchantsGMVList),
                'type'            => 'payments_escalation',
                'reason'          => 'no merchants found'
            ]);

            return;
        }

        $merchantIdList = array_map(function($element) {
            return $element[Entity::MERCHANT_ID];
        }, $merchantsGMVList);

        // fetch the existing escalations triggered for merchants
        $escalations = $this->fetchEscalationMapForMerchants($merchantIdList);

        $skippedMerchants = [];

        foreach ($merchantsGMVList as $merchantData)
        {
            $merchantId = $merchantData[Entity::MERCHANT_ID];

            try
            {
                [$triggered, $reason] = (new Handler)->triggerPaymentEscalation(
                    $merchantId, $merchantData['total'], $escalations[$merchantId] ?? []
                );

                if ($triggered === false)
                {
                    $skippedMerchants[] = [
                        'merchant_id' => $merchantId,
                        'reason'      => $reason
                    ];
                }
            }
            catch (\Exception $e)
            {
                $this->trace->error(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                    'merchant_id' => $merchantId,
                    'type'        => 'payments_escalation',
                    'exception'   => $e->getMessage()
                ]);
            }
        }

        if (empty($skippedMerchants) === false)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'skippedMerchants' => $skippedMerchants,
                'type'             => 'payments_escalation',
            ]);
        }
    }

    /**
     * send notification to merchants
     *
     * @param $input
     */
    public function sendNotifications($input)
    {

        //L1_ACTIVATION_NOT_STARTED_IN_1_DAY
        list($from, $to) = $this->getTimeWindowForCron($input, Constants::L1_ACTIVATION_NOT_STARTED_IN_1_DAY_CACHE_KEY,1);

        //Logged in but didnt start activation (L1) within 1 day
        //since cron job runs every hour query to get merchants with created date in 1 hr duration prev day and activation mile stone is null

        $merchantIdList = $this->repo->merchant_detail->filterActivationNotStartedMerchantIds(
            $from, $to);

        $this->trace->info(TraceCode::SEND_NOTIFICATION, [
            'merchants_count' => count($merchantIdList),
            'type'            => 'sendNotification',
            'to'              => $to,
            'from'            => $from
        ]);

        if (empty($merchantIdList) === true)
        {
            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_SKIPPED, [
                'merchants_count' => count($merchantIdList),
                'type'            => 'sendNotification',
                'reason'          => 'no merchants found'
            ]);

            return;
        }
        foreach ($merchantIdList as $merchantId)
        {
            $args = [
                Constants::MERCHANT => $this->repo->merchant->findOrFailPublic($merchantId)
            ];

            (new OnboardingNotificationHandler($args))
                ->sendEventNotificationForMerchant($merchantId, Events::ONBOARDING_ACTIVATION_L1_PENDING);

        }
    }

    public function getTimeWindowForCron(array $input, string $cacheKey, int $days)
    {
        if (empty($input[Constants::START_TIME]) === true)
        {
            $lastCronTime = Carbon::createFromTimestamp(
                $this->getLastCronTime($cacheKey), Timezone::IST);

            $this->updateLastCronTime($cacheKey);

            $to = Carbon::now()->subDays($days)->getTimestamp();

            $from = $lastCronTime->subDays($days)->getTimestamp();
        }
        else
        {
            $to = $input[Constants::END_TIME];

            $from = $input[Constants::START_TIME];
        }

        return [$from, $to];
    }

    public function sendOnboardingVerifyEmailNotification($input)
    {
        //EMAIL_NOT_VERIFIED_IN_1_DAY
        list($from, $to) = $this->getTimeWindowForCron($input, Constants::EMAIL_NOT_VERIFIED_IN_1_DAY_CACHE_KEY,1);

        $userIdList = $this->repo->user->filterEmailNotVerifiedUserIds($from, $to);

        $merchantIdList = array_unique($this->repo->merchant_user->fetchMerchantIdsForUserIdsAndRole($userIdList));

        $this->trace->info(TraceCode::SEND_ONBOARDING_VERFIY_EMAIL_NOTIFICATION, [
            'merchants_count' => count($merchantIdList),
            'type'            => 'sendNotification',
            'to'              => $to,
            'from'            => $from
        ]);

        if (empty($merchantIdList) === true)
        {
            $this->trace->info(TraceCode::SEND_ONBOARDING_VERFIY_EMAIL_NOTIFICATION_SKIPPED, [
                'merchants_count' => count($merchantIdList),
                'type'            => 'sendNotification',
                'reason'          => 'no merchants found'
            ]);

            return;
        }
        foreach ($merchantIdList as $merchantId)
        {
            $args = [
                Constants::MERCHANT => $this->repo->merchant->findOrFailPublic($merchantId)
            ];

            (new OnboardingNotificationHandler($args))
                ->sendEventNotificationForMerchant($merchantId, Events::ONBOARDING_VERIFY_EMAIL);

        }
    }

    public function sendNotificationsToCouponCodeEligibleMerchant($input)
    {
        //Coupon Code Eligible Merchant who have not become mtu in 2 days

        list($from, $to) = $this->getTimeWindowForCron($input, Constants::NOT_MTU_IN_TWO_DAY_CACHE_KEY,2);

        $merchantIdList = $this->repo->merchant->fetchAllLiveAndActivatedMerchants($from,$to);

        $offerMTUCouponCode = $this->repo->coupon->fetchByCodeWithRelations(CouponCodeConstants::MTU_COUPON, MerchantAccount::SHARED_ACCOUNT);

        $merchantList = $this->repo
            ->merchant_promotion
            ->fetchMerchantsWithPromotion(
                $offerMTUCouponCode->getId(),
                $from,
                $to
            );

        $merchantList =  array_diff($merchantIdList, $merchantList);

        $filteredMerchants = $this->repo->transaction->filterMerchantsWithFirstTransactionAboveTimestamp(
            $merchantList, $from);

        $merchantIdList =  array_diff($merchantList, $filteredMerchants);

        $this->trace->info(TraceCode::COUPON_CODE_ELIGIBLE_MERCHANT_NOT_MTU_NOTIFICATION, [
            'merchants_count' => count($merchantIdList),
            'type'            => 'sendNotification',
            'to'              => $to,
            'from'            => $from
        ]);

        if (empty($merchantIdList) === true)
        {
            $this->trace->info(TraceCode::COUPON_CODE_ELIGIBLE_MERCHANT_NOT_MTU_NOTIFICATION_SKIPPED, [
                'merchants_count' => count($merchantIdList),
                'type'            => 'sendNotification',
                'reason'          => 'no merchants found'
            ]);
            return;
        }

        foreach ($merchantIdList as $merchantId)
        {
            $args = [
                Constants::MERCHANT => $this->repo->merchant->findOrFailPublic($merchantId)
            ];

            (new OnboardingNotificationHandler($args))
                ->sendEventNotificationForMerchant($merchantId, Events::COUPON_CODE_ELIGIBLE_MERCHANT_NOT_MTU);
        }
    }
}
