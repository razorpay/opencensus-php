<?php


namespace RZP\Models\Merchant\Escalations;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Coupon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Store;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Notifications\Onboarding\Events;
use RZP\Services\Segment as SegmentAnalytics;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Models\Coupon\Constants as CouponCodeConstants;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Merchant\MerchantActionNotification;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Account as MerchantAccount;
use RZP\Models\Merchant\Escalations\Actions\Entity as ActionEntity;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;
use RZP\Models\Merchant\M2MReferral\Service as M2MService;

class Core extends Base\Core
{
    protected $cache;

    const DATA_LAKE_WEB_ATTRIBUTION_QUERY               = "select * from hive.aggregate_pa.mid_attribution where mid in (%s)";

    const DATA_LAKE_WEB_ATTRIBUTION_FIRST_TOUCH_QUERY   = "select * from hive.aggregate_pa.payments_product where merchant_id in (%s) and first_txn = 1";

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

    public function pushWebAttributionDetailsToSegmentCron()
    {
        $lastCronTime = $this->getLastCronTime(Constants::WEB_ATTRIBUTION_CRON_CACHE_KEY);

        /*
         * Update last Cron time instantly, since processing of cron may take another 5-10 mins
         * and during that time another payments can happen
         */
        $this->updateLastCronTime(Constants::WEB_ATTRIBUTION_CRON_CACHE_KEY);

        list($from, $to) = $this->getTimeWindowForCron([], Constants::WEB_ATTRIBUTION_CRON_CACHE_KEY,1);

        // Fetch all merchants that have been created since last time cron ran
        $merchantIds = $this->repo->merchant->fetchMerchantsCreatedBetween($from, $to);

        $this->trace->info(TraceCode::WEB_ATTRIBUTION_DETAILS_CRON_TRACE, [
            'last_cron_time'  => $lastCronTime,
            'merchants_count' => count($merchantIds),
        ]);

        $strMerchantIds = implode(', ', array_map(function ($val) { return sprintf('\'%s\'', $val);}, $merchantIds));

        $dataLakeQuery = sprintf(self::DATA_LAKE_WEB_ATTRIBUTION_QUERY, $strMerchantIds);

        $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        foreach ($lakeData as $data)
        {
            $merchantId = $data['mid'];

            unset($data['mid']);

            $segmentProperties = [];

            foreach ($data as $key => $value)
            {
                $segmentProperties["web_" . $key] = $value;
            }

            $segmentProperties[SegmentAnalytics\Constants::EVENT_MILESTONE] = SegmentEvent::IDENTIFY_WEB_ATTRIBUTION;

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties);
        }

        $this->app['segment-analytics']->buildRequestAndSend(true);
    }

    public function pushWebAttributionFirstTouchDetailsToSegmentCron()
    {
        $lastCronTime = $this->getLastCronTime(Constants::WEB_ATTRIBUTION_FIRST_TOUCH_CRON_CACHE_KEY);
        /*
         * Update last Cron time instantly, since processing of cron may take another 5-10 mins
         * and during that time another payments can happen
         */
        $this->updateLastCronTime(Constants::WEB_ATTRIBUTION_FIRST_TOUCH_CRON_CACHE_KEY);

        list($from, $to) = $this->getTimeWindowForCron([], Constants::WEB_ATTRIBUTION_FIRST_TOUCH_CRON_CACHE_KEY,1);

        // Fetch all merchants that have been created since last time cron ran
        $merchantIds = $this->repo->merchant->fetchMerchantsCreatedBetween($from, $to);

        $this->trace->info(TraceCode::WEB_ATTRIBUTION_FIRST_TOUCH_DETAILS_CRON_TRACE, [
            'last_cron_time'  => $lastCronTime,
            'merchants_count' => count($merchantIds),
        ]);

        $merchantIdChunks = array_chunk($merchantIds, 1000);

        foreach ($merchantIdChunks as $merchantIdChunk)
        {
            $strMerchantIds = implode(', ', array_map(function ($val) { return sprintf('\'%s\'', $val);}, $merchantIdChunk));

            $dataLakeQuery = sprintf(self::DATA_LAKE_WEB_ATTRIBUTION_FIRST_TOUCH_QUERY, $strMerchantIds);

            $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

            foreach ($lakeData as $data)
            {
                $merchantId = $data['merchant_id'];

                $segmentProperties = [];

                $segmentProperties['merchant_id'] = $merchantId;

                $segmentProperties['first_touch_product']  = $data['product'];

                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties);
            }
        }
        $this->app['segment-analytics']->buildRequestAndSend(true);
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
            'payment', $lastCronTime, null, false);

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

                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $merchantBalance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(),
                    Merchant\Balance\Type::PRIMARY);

                if (empty($merchantBalance) === false)
                {
                    $segmentProperties[Merchant\Service::SEGMENT_FREE_CREDITS_AVAILABLE] = $merchantBalance->getAmountCredits();
                }

                $this->trace->info(TraceCode::TRANSACTION_DETAILS_CRON_TRACE, [
                    'type'          => 'transaction_cron',
                    'merchant_id'   => $merchantId,
                    'segment_properties'    => $segmentProperties
                ]);

                $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties);
            }
        }

        $this->app['segment-analytics']->buildRequestAndSend(true);
    }

    public function handleMtuCouponApply()
    {
        $lastCronTime = $this->getLastCronTime(Constants::SEGMENT_MTU_CACHE_KEY);

        $from = Carbon::createFromTimestamp($lastCronTime)
                      ->subHour()
                      ->getTimestamp();

        $to = Carbon::now()->subHour()->getTimestamp();

        /*
         * Update last Cron time instantly, since processing of cron may take another 5-10 mins
         * and during that time another payments can happen
         */
        $this->updateLastCronTime(Constants::SEGMENT_MTU_CACHE_KEY);

        // Filter out all merchants that have transacted since last time cron ran
        $transactedMerchants = $this->repo->transaction->fetchTransactedMerchants(
            'payment', $from, $to, false);

        $this->trace->info(TraceCode::ESCALATION_CRON_TRACE, [
            'last_cron_time'  => $lastCronTime,
            'type'            => 'mtu_coupon_apply_init',
            'merchants_count' => count($transactedMerchants),
        ]);

        $merchantIdChunks = array_chunk($transactedMerchants, 100);
        $merchantIdList   = [];

        foreach ($merchantIdChunks as $merchantIdChunk)
        {
            $filteredMerchants = $this->repo->transaction->filterMerchantsWithFirstTransactionBetweenTimestamps(
                $merchantIdChunk, $from, $to);

            $this->trace->info(TraceCode::ESCALATION_CRON_TRACE, [
                'last_cron_time'  => $lastCronTime,
                'type'            => 'mtu_coupon_apply',
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
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $this->applyMtuCouponIfEligible($merchant);
            }
            catch (\Exception $e)
            {
                $this->trace->error(TraceCode::MTU_COUPON_APPLY_FAILURE, [
                    'merchant_id' => $merchantId,
                    'exception'   => $e->getMessage()
                ]);
            }
        }
    }

    public function applyMtuCouponIfEligible(Merchant\Entity $merchant)
    {
        if ($this->isEligibleForMtuCouponApplication($merchant) === true)
        {
            (new Coupon\Core())->apply($merchant, [
                Coupon\Entity::CODE => Coupon\Constants::MTU_COUPON
            ]);

            (new Merchant\Store\Core)->updateMerchantStore($merchant->getMerchantId(), [
                Store\Constants::NAMESPACE                       => Store\ConfigKey::ONBOARDING_NAMESPACE,
                Store\ConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP => true
            ]);
        }
    }

    private function isEligibleForMtuCouponApplication(Merchant\Entity $merchant): bool
    {
        if ($merchant->getOrgId() !== Org::RAZORPAY_ORG_ID)
        {
            return false;
        }

        if ((new Merchant\Core)->isRegularMerchant($merchant) === false)
        {
            return false;
        }

        $data = (new Store\Core())->fetchValuesFromStore($merchant->getMerchantId(),
            Store\ConfigKey::ONBOARDING_NAMESPACE,
            [Store\ConfigKey::MTU_COUPON_POPUP_COUNT],
            Store\Constants::INTERNAL
        );

        $popupCount = (int) $data[Store\ConfigKey::MTU_COUPON_POPUP_COUNT] ?? 0;

        if ($popupCount === 0 or $popupCount > 5)
        {
            return false;
        }

        $isCouponCodeAlreadyApplied = (new Coupon\Core)->isAnyCouponApplied(
            $merchant);

        if ($isCouponCodeAlreadyApplied === true)
        {
            return false;
        }

        $isMtuCouponExperimentEnabled = (new Merchant\Core)->isRazorxExperimentEnable(
            $merchant->getId(),
            Merchant\RazorxTreatment::MTU_COUPON_CODE);

        if ($isMtuCouponExperimentEnabled === false)
        {
            return false;
        }

        return true;
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
    public function sendNotificationUtility(array $merchantIdList, string $event)
    {
        $successCount = 0;

        $this->trace->info(TraceCode::SEND_NOTIFICATION, [
            'merchants_count' => count($merchantIdList),
            'event'           => $event,
            'type'            => 'sendNotification',
        ]);

        if (empty($merchantIdList) === true)
        {
            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_SKIPPED, [
                'merchants_count' => count($merchantIdList),
                'type'            => 'sendNotification',
                'reason'          => 'no merchants found',
                'event'           => $event
            ]);

            return;
        }

        foreach ($merchantIdList as $merchantId)
        {
            $args = [
                Constants::MERCHANT => $this->repo->merchant->findOrFailPublic($merchantId)
            ];

            $success = (new OnboardingNotificationHandler($args))
                ->sendEventNotificationForMerchant($merchantId, $event);

            $successCount += (($success === true) ? 1 : 0);
        }

        return $successCount;
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

        $this->trace->info(TraceCode::SEND_NOTIFICATION, [
            'merchants_count' => count($merchantIdList),
            'type'            => 'sendNotification',
            'to'              => $to,
            'from'            => $from,
            'event'           => Events::ONBOARDING_VERIFY_EMAIL
        ]);

        if (empty($merchantIdList) === true)
        {
            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_SKIPPED, [
                'merchants_count' => count($merchantIdList),
                'type'            => 'sendNotification',
                'reason'          => 'no merchants found',
                'event'           => Events::ONBOARDING_VERIFY_EMAIL
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

    public function sendNotificationsToInstantlyActivatedButNotTransactedMerchants($input)
    {
        $lastCronTime = $this->getLastCronTime(Constants::INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED_IN_1_HOUR);

        $this->updateLastCronTime(Constants::INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED_IN_1_HOUR);

        $to = Carbon::now()->getTimestamp();

        $merchantIdList = $this->repo->merchant->fetchAllInstantlyActivatedMerchants($lastCronTime, $to);

        $transactedMerchants = $this->repo->transaction->filterMerchantsWithFirstTransactionAboveTimestamp(
            $merchantIdList, $lastCronTime);

        $merchantList =  array_diff($merchantIdList, $transactedMerchants);

        $this->sendNotificationUtility(
            $merchantList,
            Events::INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED
        );
    }

    public function sendSignupStartedNotification($input)
    {
        $lastCronTime = $this->getLastCronTime(Constants::SIGNUP_STARTED_NOTIFY_TIMESTAMP_CACHE_KEY);

        $this->updateLastCronTime(Constants::SIGNUP_STARTED_NOTIFY_TIMESTAMP_CACHE_KEY);

        $to = Carbon::now()->getTimestamp();

        $merchantIdList = $this->repo->merchant->fetchMerchantsCreatedBetween($lastCronTime, $to);

        $this->sendNotificationUtility(
            $merchantIdList,
            Events::SIGNUP_STARTED_NOTIFY
        );
    }

    private function triggerNoDocLimitEscalation(Base\PublicCollection $merchants, array $merchantsGmvList, string $milestone, $threshold)
    {
        $merchantsGmvMap = collect($merchantsGmvList)->mapToDictionary(function($item, $key) {
            return [$item[DetailEntity::MERCHANT_ID] => $item[MConstants::TOTAL]];
        });

        foreach ($merchants as $merchant)
        {
            try
            {
                $merchantId = $merchant->getId();

                $amount = $merchantsGmvMap[$merchantId][0];

                $merchantDetails = $this->repo->merchant_detail->getByMerchantId($merchant->getId());

                $escalationConfig = $this->getEscalationConfigForThresholdAndMilestone($merchantDetails, $threshold, $milestone);

                if (empty($escalationConfig) === true)
                {
                    $this->trace->info(
                        TraceCode::NO_DOC_ONBOARDING_ESCALATION_CONFIG_NOT_FOUND,
                        [
                            'merchant_id'   => $merchant->getId(),
                            'milestone'     => $milestone,
                            'threshold'     => $threshold
                        ]
                    );

                    return;
                }

                (new Handler)->triggerEscalation($merchantId, $amount, $threshold, $escalationConfig, Constants::PAYMENT_BREACH);
            }
            catch (\Throwable $e)
            {
                $this->trace->info(
                    TraceCode::NO_DOC_ONBOARDING_ESCALATION_FAILURE,
                    [
                        'reason'        => 'something went wrong while handling no-doc onboarding escalation',
                        'trace'         => $e->getMessage(),
                        'merchant_id'   => $merchant->getId(),
                        'milestone'     => $milestone,
                        'threshold'     => $threshold
                    ]
                );
            }
        }
    }

    public function handleNoDocLimitBreach()
    {
        $threshold = Constants::HARD_LIMIT_KYC_PENDING_THRESHOLD;

        $milestone = Constants::HARD_LIMIT_NO_DOC;

        $merchantIds = $this->repo->merchant_detail->fetchMerchantIdsByActivationStatus([DetailStatus::ACTIVATED_KYC_PENDING]);

        $merchantsGmvList = $this->repo->transaction->fetchTotalAmountByTransactionTypeAboveThreshold($merchantIds, MConstants::PAYMENT, $threshold);

        $merchantIdList = array_map(function($element) {
            return $element[Entity::MERCHANT_ID];
        }, $merchantsGmvList);

        if (empty($merchantIdList) === true)
        {
            $this->trace->info(
                TraceCode::NO_DOC_ONBOARDING_ESCALATION_SKIPPED,
                [
                    'threshold' => $threshold,
                    'milestone' => $milestone,
                    'reason'    => 'no merchants found',
                ]
            );

            return;
        }
        else
        {
            $this->trace->info(
                TraceCode::NO_DOC_ONBOARDING_ESCALATION_MERCHANTS,
                [
                    'merchant_ids'  => $merchantIdList,
                    'threshold'     => $threshold,
                    'milestone'     => $milestone,
                ]
            );
        }

        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIdList);

        $this->triggerNoDocLimitEscalation($merchants, $merchantsGmvList, $milestone, $threshold);
    }
}
