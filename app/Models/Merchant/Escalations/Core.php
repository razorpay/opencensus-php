<?php


namespace RZP\Models\Merchant\Escalations;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant\Escalations\Actions\Entity as ActionEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Status as DetailStatus;

class Core extends Base\Core
{
    protected $cache;

    public function __construct()
    {
        parent::__construct();

        $this->cache = $this->app['cache'];
    }

    public function getEscalationConfigForThresholdAndMilestone($threshold, $milestone)
    {
        if(isset(Constants::PAYMENTS_ESCALATION_MATRIX[$threshold]) === false)
        {
            return null;
        }

        $configs = Constants::PAYMENTS_ESCALATION_MATRIX[$threshold];

        foreach ($configs as $config)
        {
            if($config[Constants::MILESTONE] === $milestone)
            {
                return $config;
            }
        }

        return null;
    }

    private function getLimitOnEscalationMilestone($merchant)
    {
        $limitData = [];

        $milestone = $merchant->merchantDetail->getAttribute('activation_form_milestone');

        $limitData['settlement'] = 1500000;

        if ($milestone === 'L1') {
            $limitData['payment'] = 1500000;
        } else if ($milestone === 'L2') {
            $limitData['payment'] = 1000000000;
        }

        return $limitData;
    }

    public function fetchLatestEscalationForMerchant($merchant)
    {
        $response = [];

        $escalation = $this->repo->merchant_onboarding_escalations->fetchLatestEscalation($merchant->getId());

        if(empty($escalation) === false)
        {
            $action = $this->repo->onboarding_escalation_actions->fetchActionForEscalation($escalation->getId());

            $response = $escalation->toArray();

            if(empty($action) === false)
            {
                $response['action'] = [
                    ActionEntity::STATUS    => $action->getAttribute(ActionEntity::STATUS)
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

            if(isset($escalationMap[$mid]) === false)
            {
                $escalationMap[$mid] = [];
            }

            $escalationMap[$mid][] = $escalation;
        }

        return $escalationMap;
    }

    private function getLastCronTime()
    {
        $lastCronTime = $this->cache->get(Constants::ESCALATION_CACHE_KEY);

        if(empty($lastCronTime))
        {
            return Carbon::now()->subDays(Constants::TIME_BOUND_THRESHOLD)->getTimestamp();
        }

        return $lastCronTime;
    }

    private function updateLastCronTime()
    {
        $this->cache->put(Constants::ESCALATION_CACHE_KEY, Carbon::now()->getTimestamp());
    }

    /**
     * Main method that triggers payment breach escalation for merchants
     * Filtering merchants is done using these queries:
     * - fetch all merchants which are not in end states. If timeBound is true, only merchants that are created in
     *   last 3 months are fetched (This is done to avoid heavy queries that fetches all merchants)
     * - fetch GMV of these merchants who've breached the lowest payment threshold (as of now its 1000 Rs)
     * @param false $timeBound
     */
    public function triggerPaymentEscalations($timeBound = false)
    {
        if($timeBound === true)
        {
            $lastCronTime = $this->getLastCronTime();

            $this->trace->info(TraceCode::ESCALATION_CRON_TRACE, [
                'last_cron_time'    => $lastCronTime,
                'type'              => 'payments_escalation',
            ]);

            $merchantIdList = $this->repo->transaction->fetchTransactedMerchants('payment', $lastCronTime);

            $merchantIdList = $this->repo->merchant_detail->filterMerchantIdsByActivationStatus(
                $merchantIdList, DetailStatus::OPEN_STATUSES
            );
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
            'merchants_count'   => count($merchantsGMVList),
            'type'              => 'payments_escalation'
        ]);

        if(empty($merchantsGMVList) === true)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'merchants_count'   => count($merchantsGMVList),
                'type'              => 'payments_escalation',
                'reason'            => 'no merchants found'
            ]);
            return;
        }

        $merchantIdList = array_map(function($element)
        {
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

                if($triggered === false)
                {
                    $skippedMerchants[] = [
                        'merchant_id'   => $merchantId,
                        'reason'        => $reason
                    ];
                }
            }
            catch (\Exception $e)
            {
                $this->trace->error(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                    'merchant_id'   => $merchantId,
                    'type'          => 'payments_escalation',
                    'exception'     => $e->getMessage()
                ]);
            }
        }

        if(empty($skippedMerchants) === false)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                'skippedMerchants'   => $skippedMerchants,
                'type'          => 'payments_escalation',
            ]);
        }

        // update last cron time
        $this->updateLastCronTime();
    }
}
