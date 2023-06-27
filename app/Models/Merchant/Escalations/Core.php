<?php


namespace RZP\Models\Merchant\Escalations;

use Carbon\Carbon;
use Razorpay\Trace\Logger;
use RZP\Models\Base;
use RZP\Models\Coupon;
use RZP\Constants\Mode;
use RZP\Services\Dcs\Configurations\Constants as DcsConstants;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Store;
use RZP\Models\Merchant\Detail;
use RZP\Services\ApachePinotClient;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Notifications\Onboarding\Events;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\Segment as SegmentAnalytics;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Escalations\Actions\Entity as ActionEntity;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;

class Core extends Base\Core
{
    protected $cache;

    protected $mode;

    const DATA_LAKE_WEB_ATTRIBUTION_QUERY               = "select * from hive.aggregate_pa.mid_attribution where mid in (%s)";

    const DATA_LAKE_WEB_ATTRIBUTION_FIRST_TOUCH_QUERY   = "select * from hive.aggregate_pa.payments_product where merchant_id in (%s) and first_txn = 1";

    const OFF = 'off';

    // Run both the query and hybrid query methods, and log any differences in the results.
    const SHADOW = 'shadow';

    const LIVE = 'live';

    public function __construct()
    {
        parent::__construct();

        $this->cache = $this->app['cache'];

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }
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

    public function getEscalationConfigForNoDocThresholdAndMilestone($merchantDetails, $threshold, $breachedAmount, $milestone)
    {
        if (isset(Constants::NO_DOC_PAYMENTS_ESCALATION_MATRIX[$milestone]) === false)
        {
            return null;
        }

        $configs = Constants::NO_DOC_PAYMENTS_ESCALATION_MATRIX[$milestone];

        foreach ($configs as $config)
        {
            if ($config[Constants::MILESTONE] === $milestone)
            {
                if ((new Handler)->canTriggerEscalation($merchantDetails, $config) === true)
                {
                    $config[Constants::ACTIONS][0][Constants::PARAMS][Entity::THRESHOLD] = $threshold;

                    $config[Constants::ACTIONS][0][Constants::PARAMS][Constants::CURRENT_GMV] = $breachedAmount;

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

    public function fetchAllEscalationsForMerchant($merchant)
    {
        $response = [];

        $escalations = $this->repo->merchant_onboarding_escalations->fetchEscalations($merchant->getId());

        if (empty($escalations) === false)
        {
            foreach ($escalations as $escalation)
            {
                $action = $this->repo->onboarding_escalation_actions->fetchActionForEscalation($escalation->getId());

                $escalationDetails = $escalation->toArray();

                if (empty($action) === false)
                {
                    $escalationDetails['action'] = [
                        ActionEntity::STATUS => $action->getAttribute(ActionEntity::STATUS)
                    ];
                }
            }
        }

        return $response;
    }

    public function fetchEscalationsForMerchant($merchant)
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

        if (empty($merchantIds) === true)
        {
            $this->trace->info(TraceCode::WEB_ATTRIBUTION_DETAILS_CRON_TRACE, [
                'type'            => 'pushWebAttributionFirstTouchDetailsToSegmentCron',
                'step'            => 'soft limit breach on auto kyc',
                'reason'          => 'no merchants found',
            ]);

            return;
        }

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
                      ->subMinutes(5)
                      ->getTimestamp();

        $to = Carbon::now()->getTimestamp();

        /*
         * Update last Cron time instantly, since processing of cron may take another 5-10 mins
         * and during that time another payments can happen
         */
        $this->updateLastCronTime(Constants::SEGMENT_MTU_CACHE_KEY);

        // Filter out all merchants that have transacted since last time cron ran
        // we have to first get count as pinot has limitation that limit has to be passed in the get query
        $query = "select count(distinct merchant_id) as transacted_merchants_count from payments_v1 where created_at between %s and %s and base_amount>0";

        $query = sprintf($query, $from, $to);

        $content = [
            'query'   => $query,
            'backend' => 'pinot'
        ];

        // fetch all merchants count merchants that have transacted since last time cron ran
        $queryResponse = $this->app['eventManager']->getDataFromPinot($content);

        if (empty($queryResponse) === true)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'type'   => 'mtu_coupon_apply',
                'reason' => 'no merchants found',
                'step'   => 'transacted_merchants_count'
            ]);

            return;
        }

        $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
            'merchants_count' => count($queryResponse),
            'type'            => 'mtu_coupon_apply',
            'step'            => 'transacted_merchants_count'
        ]);

        $resultCount = $queryResponse[0]["transacted_merchants_count"];

        $query = "select distinct merchant_id from payments_v1 where created_at between %s and %s and base_amount>0 limit %s";

        $query = sprintf($query, $from, $to, $resultCount + 1);

        $content = [
            'query'   => $query,
            'backend' => 'pinot'
        ];

        // fetch all merchants who've have done the transaction since last time cron ran
        $queryResponse = $this->app['eventManager']->getDataFromPinot($content);

        if (empty($queryResponse) === true)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'type'   => 'mtu_coupon_apply',
                'reason' => 'no merchants found',
                'step'   => 'transacted_merchants'
            ]);

            return;
        }

        $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
            'merchants_count' => count($queryResponse),
            'type'            => 'mtu_coupon_apply',
            'step'            => 'transacted_merchants'
        ]);

        $merchantIdList = array_column($queryResponse, Entity::MERCHANT_ID);

        //filter out m2m merchants as they will receive different coupon
        $m2mMerchants = $this->repo->m2m_referral->filterMerchants($merchantIdList);

        $this->trace->info(TraceCode::ESCALATION_CRON_TRACE, [
            'last_cron_time'  => $lastCronTime,
            'type'            => 'mtu_coupon_apply',
            'step'            => 'm2m merchants',
            'merchants_count' => count($m2mMerchants),
        ]);

        $merchantIdList = array_diff($merchantIdList, $m2mMerchants);

        $merchantIdChunks = array_chunk($merchantIdList, 100);

        foreach ($merchantIdChunks as $merchantIdChunk)
        {
            $query = "select min(created_at) as first_transaction_timestamp,merchant_id from payments_v1 where merchant_id in (%s) and base_amount>0 group by merchant_id limit %s";

            $query = sprintf($query, "'" . implode("','", $merchantIdChunk) . "'", count($merchantIdChunk) + 1);

            $content = [
                'query'   => $query,
                'backend' => 'pinot'
            ];

            // fetch all merchants first transaction timestamp
            $queryResponse = $this->app['eventManager']->getDataFromPinot($content);

            if (empty($queryResponse) === true)
            {
                $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                    'type'   => 'mtu_coupon_apply',
                    'reason' => 'no merchants found',
                    'step'   => 'first_transaction_timestamp'
                ]);

                return;
            }

            $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
                'merchants_count' => count($queryResponse),
                'type'            => 'mtu_coupon_apply',
                'step'            => 'first_transaction_timestamp'
            ]);

            $merchantsMTUTimestampMap = array_column($queryResponse, "first_transaction_timestamp", Entity::MERCHANT_ID);

            foreach ($merchantsMTUTimestampMap as $merchantId => $firstTxnTimestamp)
            {
                try
                {
                    if ($firstTxnTimestamp > $from and $firstTxnTimestamp < $to)
                    {
                        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                        $this->applyMtuCouponIfEligible($merchant);
                    }
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
    }

    public function applyMtuCouponIfEligible(Merchant\Entity $merchant)
    {
        if ($this->isEligibleForMtuCouponApplication($merchant) === true)
        {
            (new Coupon\Core())->apply($merchant, [
                Coupon\Entity::CODE => Coupon\Constants::MTU_COUPON
            ],true);

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
     *
     * @param false $timeBound
     */
    public function triggerPaymentEscalations($timeBound = false)
    {
        $experimentResult = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(),
            RazorxTreatment::TRIGGER_NEW_ONBOARDING_ESCALATION_FLOW,
            Mode::LIVE);

        $triggerNewOnboardingEscalationFlow = ( $experimentResult === 'on' ) ? true : false;

        if($triggerNewOnboardingEscalationFlow === true)
        {
            try
            {
                [$merchantIdList, $merchantsGMVList] = $this->filterMerchantsWithGmvUsingEscalationType(Constants::PAYMENTS_ESCALATION, $timeBound);

                if($merchantIdList === null)
                {
                    return;
                }
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex, Logger::ERROR, TraceCode::NEW_ESCALATION_FLOW_FAILURE);

                $triggerNewOnboardingEscalationFlow = false;
            }
        }

        //Todo: Need to remove below 'if' body once the filterMerchantsWithGmvUsingEscalationType function works satisfactorily
        if($triggerNewOnboardingEscalationFlow === false)
        {
            if ($timeBound === true)
            {
                $lastCronTime = $this->getLastCronTime() - 300;

                $this->trace->info(TraceCode::ESCALATION_CRON_TRACE, [
                    'last_cron_time' => $lastCronTime,
                    'type'           => 'payments_escalation',
                ]);
                $this->updateLastCronTime(Constants::ESCALATION_CACHE_KEY);

                $currentCronTime = $this->getLastCronTime();

                // we have to first get count as pinot has limitation that limit has to be passed in the get query
                $query = "select count(distinct merchant_id) as transacted_merchants_count from payments_v1 where created_at between %s and %s";

                $query = sprintf($query, $lastCronTime, $currentCronTime, Constants::LOWEST_PAYMENTS_THRESHOLD);

                $content = [
                    'query'   => $query,
                    'backend' => 'pinot'
                ];

                // fetch all merchants count who've atleast breached lowest payments threshold
                $queryResponse = $this->app['eventManager']->getDataFromPinot($content);

                if (empty($queryResponse) === true)
                {
                    $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                        'type'            => 'payments_escalation_timebound',
                        'reason'          => 'no merchants found',
                        'step'            => 'transacted_merchants_count'
                    ]);

                    return;
                }

                $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
                    'query_response_count' => count($queryResponse),
                    'type'            => 'payments_escalation_timebound',
                    'step'            => 'transacted_merchants_count'
                ]);

                $resultCount = $queryResponse[0]["transacted_merchants_count"];

                $query = "select distinct merchant_id from payments_v1 where created_at between %s and %s limit %s";

                $query = sprintf($query, $lastCronTime, $currentCronTime, $resultCount + 1);

                $content = [
                    'query'   => $query,
                    'backend' => 'pinot'
                ];

                // fetch all merchants who've atleast breached lowest payments threshold
                $queryResponse = $this->app['eventManager']->getDataFromPinot($content);

                if (empty($queryResponse) === true)
                {
                    $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                        'type'            => 'payments_escalation_timebound',
                        'reason'          => 'no merchants found',
                        'step'            => 'transacted_merchants'
                    ]);

                    return;
                }

                $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
                    'query_response_count' => count($queryResponse),
                    'type'            => 'payments_escalation_timebound',
                    'step'            => 'transacted_merchants'
                ]);

                $merchantIdList = array_column($queryResponse, Entity::MERCHANT_ID);

                $merchantIdList = $this->repo->merchant_detail->filterMerchantIdsByActivationStatus(
                    $merchantIdList, DetailStatus::MERCHANT_OPEN_STATUSES);

            }
            else
            {
                // fetch all the merchants who are not in end states
                $merchantIdList = $this->repo->merchant_detail->fetchMerchantIdsByActivationStatus(
                    DetailStatus::MERCHANT_OPEN_STATUSES);
            }

            if (empty($merchantIdList) === true)
            {
                $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                    'type'            => 'payments_escalation',
                    'reason'          => 'no merchants found',
                    'step'            => 'open_merchants'
                ]);

                return;
            }
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
                'merchants_count' => count($merchantIdList),
                'type'            => 'payments_escalation',
                'step'            => 'open_merchants'
            ]);

            $queryResponse = $this->fetchQueryResponseForMerchantsGMVList($merchantIdList);

            if (empty($queryResponse) === true)
            {
                $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                    'type'   => 'payments_escalation',
                    'reason' => 'no merchants found',
                    'step'   => 'transacted_merchants'
                ]);

                return;
            }

            $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
                'merchants_count' => count($queryResponse),
                'type'            => 'payments_escalation',
                'step'            => 'transacted_merchants'
            ]);

            $merchantIdList = array_column($queryResponse, Entity::MERCHANT_ID);

            $merchantsGMVList = array_column($queryResponse, "amount", Entity::MERCHANT_ID);
        }

        // these banking org merchants need custom escalation matrix so can't escalate for them in this flow
        // another cron handlePaymentEscalationsForBankingOrg is handling escalation for these merchants
        $bankingOrgMerchantsHavingFeature = $this->filterMerchantWithBankingOrgHavingFeature(Constants::ASSIGN_CUSTOM_HARD_LIMIT,$merchantIdList);

        $merchantIdList = array_diff($merchantIdList, $bankingOrgMerchantsHavingFeature);

        $merchantsGMVList = array_intersect_key($merchantsGMVList, array_flip($merchantIdList));

        // fetch the existing escalations triggered for merchants
        $escalations = $this->fetchEscalationMapForMerchants($merchantIdList);

        $skippedMerchants = [];

        foreach ($merchantsGMVList as $merchantId => $amount)
        {
            try
            {
                [$triggered, $reason] = (new Handler)->triggerPaymentEscalation(
                    $merchantId, $amount, $escalations[$merchantId] ?? []
                );

                $this->handleInstantActivationV2ApiLimitBreach($merchantId, $amount, $escalations[$merchantId] ?? []);

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
                    'type'        => Constants::PAYMENTS_ESCALATION,
                    'exception'   => $e->getMessage()
                ]);
            }
        }

        if (empty($skippedMerchants) === false)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'skippedMerchants' => $skippedMerchants,
                'type'             => Constants::PAYMENTS_ESCALATION,
            ]);
        }
    }

    /**
     * Filtering merchants is done using these queries:
     * - fetch all merchants which are not in end states. If timeBound is true, only merchants that are created in
     *   last 3 months are fetched (This is done to avoid heavy queries that fetches all merchants)
     * - fetch GMV of these merchants who've breached the lowest payment threshold (as of now its 1000 Rs)
     *
     * @param string $escalationType
     * @param false $timeBound
     * @return array|null[]
     */
    private function filterMerchantsWithGmvUsingEscalationType(string $escalationType = Constants::PAYMENTS_ESCALATION, bool $timeBound = false)
    {
        if ($timeBound === true)
        {
            $lastCronTime = $this->getLastCronTime(Constants::escalationsParamsMap[$escalationType][Constants::KEY])
                - Constants::escalationsParamsMap[$escalationType][Constants::INTERVAL];

            $this->trace->info(TraceCode::ESCALATION_CRON_TRACE, [
                'last_cron_time' => $lastCronTime,
                'type'           => $escalationType,
            ]);

            $this->updateLastCronTime(Constants::escalationsParamsMap[$escalationType][Constants::KEY]);

            $currentCronTime = $this->getLastCronTime(Constants::escalationsParamsMap[$escalationType][Constants::KEY]);

            // we have to first get count as pinot has limitation that limit has to be passed in the get query
            $query = "select count(distinct merchant_id) as transacted_merchants_count from payments_v1 where created_at between %s and %s";

            $query = sprintf($query, $lastCronTime, $currentCronTime, Constants::LOWEST_PAYMENTS_THRESHOLD);

            $content = [
                'query'   => $query,
                'backend' => 'pinot'
            ];

            // fetch all merchants count who've atleast breached lowest payments threshold
            $queryResponse = $this->app['eventManager']->getDataFromPinot($content);

            if (empty($queryResponse) === true)
            {
                $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                    'type'            => $escalationType . '_timebound',
                    'reason'          => 'no merchants found',
                    'step'            => 'transacted_merchants_count'
                ]);

                return [null, null];
            }

            $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
                'query_response_count' => count($queryResponse),
                'type'            => $escalationType . '_timebound',
                'step'            => 'transacted_merchants_count'
            ]);

            $resultCount = $queryResponse[0]["transacted_merchants_count"];

            $query = "select distinct merchant_id from payments_v1 where created_at between %s and %s limit %s";

            $query = sprintf($query, $lastCronTime, $currentCronTime, $resultCount + 1);

            $content = [
                'query'   => $query,
                'backend' => 'pinot'
            ];

            // fetch all merchants who've atleast breached lowest payments threshold
            $queryResponse = $this->app['eventManager']->getDataFromPinot($content);

            if (empty($queryResponse) === true)
            {
                $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                    'type'            => $escalationType . '_timebound',
                    'reason'          => 'no merchants found',
                    'step'            => 'transacted_merchants'
                ]);

                return [null, null];
            }

            $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
                'query_response_count' => count($queryResponse),
                'type'            => $escalationType . '_timebound',
                'step'            => 'transacted_merchants'
            ]);

            $merchantIdList = array_column($queryResponse, Entity::MERCHANT_ID);

            $merchantIdList = $this->repo->merchant_detail->filterMerchantIdsByActivationStatus(
                $merchantIdList, Constants::escalationsParamsMap[$escalationType][Constants::ALLOWED_OPEN_STATUSES]);

        }
        else
        {
            // fetch all the merchants who are not in end states
            $merchantIdList = $this->repo->merchant_detail->fetchMerchantIdsByActivationStatus(
                Constants::escalationsParamsMap[$escalationType][Constants::ALLOWED_OPEN_STATUSES]);
        }

        if (empty($merchantIdList) === true)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'type'            => $escalationType,
                'reason'          => 'no merchants found',
                'step'            => 'open_merchants'
            ]);

            return [null, null];
        }

        $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
            'merchants_count' => count($merchantIdList),
            'type'            => $escalationType,
            'step'            => 'open_merchants'
        ]);

        $queryResponse = $this->fetchQueryResponseForMerchantsGMVList($merchantIdList);

        if (empty($queryResponse) === true)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'type'   => $escalationType,
                'reason' => 'no merchants found',
                'step'   => 'transacted_merchants'
            ]);

            return [null, null];
        }

        $this->trace->info(TraceCode::ESCALATION_ATTEMPT, [
            'merchants_count' => count($queryResponse),
            'type'            => $escalationType,
            'step'            => 'transacted_merchants'
        ]);

        $merchantIdList = array_column($queryResponse, Entity::MERCHANT_ID);

        $merchantsGMVList = array_column($queryResponse, "amount", Entity::MERCHANT_ID);

        return [$merchantIdList, $merchantsGMVList];
    }

    /**
     * Fetch query response to evaluate merchantsGMVList depending on hybridDataQueryingMode
     *
     * @param $merchantIdList
     * @return array|null[]
     */
    private function fetchQueryResponseForMerchantsGMVList($merchantIdList)
    {
        try
        {
            $queryResponse = [];

            $hybridQueryResponse = [];

            $hybridDataQueryingMode = (new Detail\Core)->getSplitzResponse(UniqueIdEntity::generateUniqueId(),
                                       Constants::HYBRID_DATA_QUERYING_SPLITZ_EXPERIMENT_ID) ?: self::OFF;

            if ($hybridDataQueryingMode === self::OFF)
            {
                $queryResponse = $this->fetchQueryResponseForMerchantsGMVListFromPinot($merchantIdList);
            }

            if ($hybridDataQueryingMode === self::LIVE)
            {
                $queryResponse = $this->fetchHybridQueryResponseForMerchantsGMVListFromPinotAndDataLake($merchantIdList);
            }

            if ($hybridDataQueryingMode === self::SHADOW)
            {
                $queryResponse = $this->fetchQueryResponseForMerchantsGMVListFromPinot($merchantIdList);

                $hybridQueryResponse = $this->fetchHybridQueryResponseForMerchantsGMVListFromPinotAndDataLake($merchantIdList);

                $this->findDifferenceBetweenQueryResponses($queryResponse, $hybridQueryResponse);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::HYBRID_DATA_QUERYING_ATTEMPT_FAILED, [
                'type'        => Constants::HYBRID_DATA_QUERYING,
                'exception'   => $e->getMessage()
            ]);
        }

        return $queryResponse;
    }

    /**
     * Query pinot to retrieve data for entire period
     *
     * @param array $merchantIdList
     * @return array|null[]
     */
    private function fetchQueryResponseForMerchantsGMVListFromPinot($merchantIdList)
    {
        $query = "select sum(base_amount) amount, merchant_id
                  from payments_v1
                  where merchant_id in (%s)
                  group by merchant_id
                  having amount > %s
                  limit %s";

        $merchantIds = "'" . implode("','", $merchantIdList) . "'";

        $threshold = Constants::LOWEST_PAYMENTS_THRESHOLD;

        $limit = count($merchantIdList) + 1;

        $query = sprintf($query, $merchantIds, $threshold, $limit);

        $content = [
            'query'   => $query,
            'backend' => 'pinot'
        ];

        $queryResponse = $this->app['eventManager']->getDataFromPinot($content) ?? [];

        $this->trace->info(TraceCode::HYBRID_DATA_QUERYING_RESPONSE, [
            'type'                          => 'payments_escalation',
            'step'                          => 'hybrid_data_querying',
            'query_response'                => $queryResponse,
        ]);

        return $queryResponse;
    }

    /**
     * Query pinot to retrieve data till retention period
     * Query datalake to retrieve data before retention period
     * Return sum of data queried via pinot and datalake as hybrid query response
     *
     * @param array $merchantIdList
     * @return array|null[]
     */
    private function fetchHybridQueryResponseForMerchantsGMVListFromPinotAndDataLake($merchantIdList)
    {
        date_default_timezone_set('Asia/Kolkata');

        $retentionPeriod = strtotime('-' . Constants::RETENTION_PERIOD . ' days', strtotime('midnight'));

        $merchantIds = "'" . implode("','", $merchantIdList) . "'";

        $threshold = Constants::LOWEST_PAYMENTS_THRESHOLD;

        $limit = count($merchantIdList) + 1;

        // Query pinot
        $pinotQuery = "select sum(base_amount) amount, merchant_id
                       from payments_v1
                       where merchant_id in (%s)
                       and created_at >= %s
                       group by merchant_id
                       having amount > %s
                       limit %s";

        $pinotQuery = sprintf($pinotQuery, $merchantIds, $retentionPeriod, $threshold, $limit);

        $content = [
            'query'   => $pinotQuery,
            'backend' => 'pinot'
        ];

        $pinotQueryResponse = $this->app['eventManager']->getDataFromPinot($content) ?? [];

        $this->trace->info(TraceCode::HYBRID_DATA_QUERYING_RESPONSE, [
            'type'                          => 'payments_escalation',
            'step'                          => 'hybrid_data_querying',
            'pinot_query_response'          => $pinotQueryResponse,
        ]);

        // Query datalake
        $datalakeQuery = "select sum(base_amount) amount, merchant_id
                          from dbt_prod_harvester_agg.payments_v1_agg
                          where merchant_id in (%s)
                          and created_at < %s
                          group by merchant_id
                          having sum(base_amount) > %s
                          limit %s";

        $datalakeQuery = sprintf($datalakeQuery, $merchantIds, $retentionPeriod, $threshold, $limit);

        $datalakeQueryResponse = $this->app['datalake.presto']->getDataFromDataLake($datalakeQuery) ?? [];

        $this->trace->info(TraceCode::HYBRID_DATA_QUERYING_RESPONSE, [
            'type'                          => 'payments_escalation',
            'step'                          => 'hybrid_data_querying',
            'datalake_query_response'       => $datalakeQueryResponse,
        ]);

        if (empty($pinotQueryResponse) === true and empty($datalakeQueryResponse) === true)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'type'   => Constants::PAYMENTS_ESCALATION,
                'reason' => 'Null query response',
            ]);

            return [];
        }

        // merge pinot and datalake query response
        $pinotAndDataLakeQueryResponse = array_merge($pinotQueryResponse, $datalakeQueryResponse);

        foreach ($pinotAndDataLakeQueryResponse as ['amount' => $amount, 'merchant_id' => $merchantId])
        {
            $hybridQueryResponse[$merchantId]['amount'] = ($hybridQueryResponse[$merchantId]['amount'] ?? 0) + $amount;
            $hybridQueryResponse[$merchantId]['merchant_id'] = $merchantId;
        }

        $hybridQueryResponse = array_values($hybridQueryResponse);

        $this->trace->info(TraceCode::HYBRID_DATA_QUERYING_RESPONSE, [
            'type'                          => 'payments_escalation',
            'step'                          => 'hybrid_data_querying',
            'hybrid_query_response'         => $hybridQueryResponse,
        ]);

        return $hybridQueryResponse;
    }

    /**
     * Calculate difference between passed query responses
     *
     * @param array $queryResponse
     * @param array $hybridQueryResponse
     * @return array|null[]
     */
    private function findDifferenceBetweenQueryResponses($queryResponse, $hybridQueryResponse)
    {
        $hybridQueryResponseMap = array_column($hybridQueryResponse, 'amount', 'merchant_id');

        $queryResponseMap = array_column($queryResponse, 'amount', 'merchant_id');

        $differenceInQueryResponse = [];

        // merge all the merchant ids (keys) from both the responses and collect distinct merchant ids
        $distinctMerchantIds = array_unique(array_merge(array_keys($hybridQueryResponseMap), array_keys($queryResponseMap)));

        foreach ($distinctMerchantIds as $merchantId)
        {
            $hybridQueryResponseAmount = $hybridQueryResponseMap[$merchantId] ?? 0;

            $queryResponseAmount = $queryResponseMap[$merchantId] ?? 0;

            if ($hybridQueryResponseAmount !== $queryResponseAmount)
            {
                $differenceInQueryResponse[$merchantId] = abs($hybridQueryResponseAmount - $queryResponseAmount);
            }
        }

        $this->trace->info(TraceCode::HYBRID_DATA_QUERYING_RESPONSE, [
            'type'                         => 'payments_escalation',
            'step'                         => 'hybrid_data_querying',
            'difference_in_query_response' => $differenceInQueryResponse,
        ]);

        return $differenceInQueryResponse;
    }

    public function handleNoDocGmvLimitBreach($timeBound = false)
    {
        $startTime = microtime(true);

        [$merchantIdList, $merchantsGMVList] = $this->filterMerchantsWithGmvUsingEscalationType(Constants::NO_DOC_PAYMENTS_ESCALATION, $timeBound);

        if($merchantIdList === null)
        {
            return;
        }

        $escalationStartTime = microtime(true);

        $merchantIdList = $this->repo->feature->getMerchantIdsHavingFeature(FeatureConstants::NO_DOC_ONBOARDING, $merchantIdList);

        if($merchantIdList === null)
        {
            $this->trace->info(
                TraceCode::NO_DOC_ONBOARDING_ESCALATION_SKIPPED,
                [
                    'step'     => 'xpress_merchants',
                    'reason'   => 'Xpress escalation skipped since filtered merchants does not have no_doc_onboarding feature enabled',
                ]
            );

            return;
        }

        $this->trace->info(
            TraceCode::NO_DOC_ONBOARDING_ESCALATION_MERCHANTS,
            [
                'xpress_merchants' => $merchantIdList,
                'step'   => 'xpress_merchants',
            ]
        );

        $merchants = $this->repo->merchant->findManyOrFailPublic($merchantIdList);

        $thresholdToMerchantMapping = [];

        foreach ($merchants as $merchant)
        {
            $threshold = (new Merchant\AccountV2\Core())->getGmvLimitForNoDocMerchant($merchant);

            $thresholdToMerchantMapping[$threshold][] = $merchant->getId();
        }

        foreach ($thresholdToMerchantMapping as $threshold => $merchantIds)
        {
            [$merchantsGmvBreachList, $merchantsNinetyOnePercentileGmvList, $merchantsNinetyPercentileGmvList]
                = $this->filterNoDocEscalatedMerchants($merchantIds, $threshold, $merchantsGMVList);

            $this->trace->info(
                TraceCode::NO_DOC_ONBOARDING_ESCALATION_MERCHANTS,
                [
                    'xpress_merchants'              => $merchantIdList,
                    'GMV breached merchants'        => $merchantsGmvBreachList,
                    '90% GMV breached merchants'    => $merchantsNinetyPercentileGmvList,
                    '91% GMV breached merchants'    => $merchantsNinetyOnePercentileGmvList,
                    'step'                          => 'xpress_merchants_escalation',
                ]
            );

            $this->handleNoDocOnboardingEscalation($merchantsNinetyPercentileGmvList, Constants::NO_DOC_P90_GMV, $threshold);

            $this->handleNoDocOnboardingEscalation($merchantsNinetyOnePercentileGmvList, Constants::NO_DOC_P91_GMV, $threshold);

            $this->handleNoDocOnboardingEscalation($merchantsGmvBreachList, Constants::HARD_LIMIT_NO_DOC, $threshold);
        }

        $this->trace->info(TraceCode::NO_DOC_ONBOARDING_ESCALATION_LATENCY, [
            'escalation_startTime'          => $startTime,
            'overall_escalation_duration'   => (microtime(true) - $startTime) * 1000,
            'escalation_duration'           => (microtime(true) - $escalationStartTime) * 1000,
            'escalation_type'               => 'xpress_escalation'
        ]);
    }

    public function handlePaymentEscalationsForBankingOrg(){

        [$merchantIdList, $merchantsGMVList] = $this->filterMerchantsWithGmvUsingEscalationType(Constants::BANKING_ORG_PAYMENTS_ESCALATION, true);

        if($merchantIdList === null)
        {
            return;
        }

        $merchantIdList = $this->filterMerchantWithBankingOrgHavingFeature(Constants::ASSIGN_CUSTOM_HARD_LIMIT,$merchantIdList);

        $merchantsGMVList = array_intersect_key($merchantsGMVList, array_flip($merchantIdList));


        // fetch the existing escalations triggered for merchants
        $escalations = $this->fetchEscalationMapForMerchants($merchantIdList);

        $skippedMerchants = [];

        foreach ($merchantsGMVList as $merchantId => $amount)
        {
            try
            {
                [$triggered, $reason] = (new Handler)->triggerBankingOrgPaymentEscalation(
                    $merchantId, $amount, $escalations[$merchantId] ?? []
                );

                if ($triggered === false)
                {
                    $skippedMerchants[] = [
                        'merchant_id' => $merchantId,
                        'reason'      => $reason
                    ];
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->error(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                    'merchant_id' => $merchantId,
                    'type'        => Constants::BANKING_ORG_PAYMENTS_ESCALATION,
                    'exception'   => $e->getMessage()
                ]);
            }
        }

        if (empty($skippedMerchants) === false)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'skippedMerchants' => $skippedMerchants,
                'type'             => Constants::BANKING_ORG_PAYMENTS_ESCALATION,
            ]);
        }
    }

    private function filterMerchantWithBankingOrgHavingFeature($featurename, $merchantIdList){

        try {
            $dcsConfigService = app('dcs_config_service');

            $entityIdsWithFeature = $dcsConfigService->fetchEntityIdsWithValueByConfigNameAndFieldNameFromDcs(DcsConstants::CustomHardLimitConfigurations,$featurename,$this->mode);

            $orgIdList = [];
            foreach ($entityIdsWithFeature as $entityId => $value) {
                if (is_bool($value) and $value === true) {
                    $orgIdList[] = $entityId;
                }
            }

            $filteredMerchantIdList = $this->repo->merchant_detail->filterMerchantIdsByOrg($merchantIdList,$orgIdList);

            return $filteredMerchantIdList;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::ENTITIES_HAVING_FEATURE_DCS_FETCH_ERROR,[$featurename]);

            throw $ex;
        }
    }

    private function filterNoDocEscalatedMerchants(array $merchantIds, int $threshold, array $merchantsGMVList): array
    {
        $merchantsGmvBreachList = [];
        $merchantsNinetyOnePercentileGmvList = [];
        $merchantsNinetyPercentileGmvList = [];

        foreach ($merchantIds as $merchantId)
        {
            $merchantGmvMap['merchant_id'] = $merchantId;
            $merchantGmvMap['total'] = $merchantsGMVList[$merchantId];

            if($merchantsGMVList[$merchantId] >= $threshold)
            {
                array_push($merchantsGmvBreachList, $merchantGmvMap);
            }
            else if($merchantsGMVList[$merchantId] >= $threshold * 0.91)
            {
                array_push($merchantsNinetyOnePercentileGmvList, $merchantGmvMap);
            }
            else if($merchantsGMVList[$merchantId] >= $threshold * 0.90)
            {
                array_push($merchantsNinetyPercentileGmvList, $merchantGmvMap);
            }
        }

        return [$merchantsGmvBreachList, $merchantsNinetyOnePercentileGmvList, $merchantsNinetyPercentileGmvList];
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
                Constants::MERCHANT => $this->repo->merchant->findOrFailPublic($merchantId),
                Constants::PARAMS   => [
                    'amount'    =>  '₹10,000'
                ]
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

                //skip escalation for a merchant for milestones where webhook is already triggered before
                if(($milestone === Constants::NO_DOC_P90_GMV) or ($milestone === Constants::NO_DOC_P91_GMV))
                {
                    //checking if escalation entry exist for the merchant with the respective milestone & threshold, so that we do not re-trigger same escalation again
                    $escalations = $this->repo->merchant_onboarding_escalations->fetchLiveEscalationForThresholdAndMilestone($merchant->getId(), $milestone, $threshold);

                    if (empty($escalations) === false)
                    {
                        $this->trace->info(
                            TraceCode::NO_DOC_ONBOARDING_ESCALATION_SKIPPED,
                            [
                                'merchant'  => $merchant->getId(),
                                'threshold' => $threshold,
                                'milestone' => $milestone,
                                'reason'    => 'Escalation skipped since merchant has already been escalated before with given threshold and milestone',
                            ]
                        );
                        continue;
                    }
                }

                $merchantDetails = $this->repo->merchant_detail->getByMerchantId($merchant->getId());

                $escalationConfig = $this->getEscalationConfigForNoDocThresholdAndMilestone($merchantDetails, $threshold, $amount, $milestone);

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

    public function handleNoDocOnboardingEscalation(array $merchantsGmvList, string $milestone, int $threshold)
    {
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
                    'reason'    => 'no merchants found for mentioned threshold and milestone',
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

    public function handleInstantActivationV2ApiLimitBreach($merchantId, $amount, $existingEscalations)
    {
        try
        {
            [$threshold, $escalationConfig] = $this->instantActivationThreshold($merchantId, $amount, $existingEscalations);

            if ($threshold === 0) {
                return;
            }

            (new Handler)->triggerEscalation($merchantId, $amount, $threshold, $escalationConfig, Constants::PAYMENT_BREACH);
        }
        catch(\Throwable $e)
        {
            $this->trace->error(TraceCode::INSTANT_ACTIVATION_V2_APIS_ESCALATION_FAILURE, [
                'merchant_id' => $merchantId,
                'type'        => 'instant_activation_v2_api_webhook_alert',
                'exception'   => $e->getMessage()
            ]);
        }
    }

    public function instantActivationThreshold($merchantId, $breachedAmount, $existingEscalations)
    {
        $escalationMatrix  = array_reverse(Constants::INSTANT_ACTIVATION_V2_API_ESCALATION_MATRIX, true);

        foreach ($escalationMatrix as $threshold => $escalationConfig)
        {
            if ($breachedAmount < $threshold)
            {
                continue;
            }

            $existingMilestones = $this->getExistingMilestones($threshold, $existingEscalations);

            $escalationExists = in_array($escalationConfig[0][Constants::MILESTONE], $existingMilestones, true);
            $hardLimitAlreadyBreached = in_array(Constants::HARD_LIMIT_IA_V2, $existingMilestones, true);

            if ($escalationExists === true or $hardLimitAlreadyBreached === true)
            {
                continue;
            }

            foreach($escalationConfig as $config)
            {
                if ($this->canTriggerIAWebhookEscalation($merchantId, $config) === true)
                {
                    $config[Constants::ACTIONS][0][Constants::PARAMS][Entity::THRESHOLD] = $threshold;

                    $config[Constants::ACTIONS][0][Constants::PARAMS][Constants::CURRENT_GMV] = $breachedAmount;

                    return [$threshold, $config];
                }
            }
        }

        return [0,null];
    }

    public function canTriggerIAWebhookEscalation($merchantId, $config): bool
    {
        $merchantDetails = $this->repo->merchant_detail->getByMerchantId($merchantId);

        return (new Handler)->canTriggerEscalation($merchantDetails, $config);
    }

    public function getExistingMilestones($threshold, $existingEscalations): array
    {
        $milestones = [];

        foreach ($existingEscalations as $escalation)
        {
            if ($escalation[Entity::THRESHOLD] >= $threshold)
            {
                $milestones[] = $escalation[Entity::MILESTONE];
            }
        }
        return $milestones;
    }
}
