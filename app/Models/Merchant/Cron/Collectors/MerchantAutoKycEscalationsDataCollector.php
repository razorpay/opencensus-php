<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;
use RZP\Models\Merchant\AutoKyc\Escalations\Utils;
use RZP\Models\Merchant\Cron\Collectors\Core\DbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Trace\TraceCode;

class MerchantAutoKycEscalationsDataCollector extends DbDataCollector
{
    protected function collectDataFromSource(): CollectorDto
    {
        $this->app['trace']->info(TraceCode::SELF_SERVE_CRON, [
            'type' => 'escalation_cron'
        ]);

        $data=[];

        $soft_limit_escalations = $this->handleEscalationsForType(Constants::SOFT_LIMIT);
        $data['escalations_with_type'][Constants::SOFT_LIMIT] = $soft_limit_escalations;

        $hard_limit_escalations = $this->handleEscalationsForType(Constants::HARD_LIMIT);
        $data['escalations_with_type'][Constants::HARD_LIMIT] = $hard_limit_escalations;

        return CollectorDto::create($data);
    }

    /**
     * Method to trigger Escalations for a given Type
     *
     * @param string $type
     */
    private function handleEscalationsForType(string $type)
    {
        // fetch all escalations for type
        $escalations = $this->repo->merchant_auto_kyc_escalations->fetchEscalationsForType($type);

        // create a map of latest escalation for each merchant
        $latestEscalationsForMerchant = $this->getLatestEscalations($escalations);

        // extract merchant ids from the map
        $merchantIdList = array_keys($latestEscalationsForMerchant);

        // merchants who crossed soft limit, may get rejected, activated upon manual review
        // so filter only those who are in NC, under review, mcc pending
        $merchantIdList = $this->repo->merchant_detail->filterMerchantIdsByActivationStatus(
            $merchantIdList, [
            DetailStatus::NEEDS_CLARIFICATION,
            DetailStatus::UNDER_REVIEW,
            DetailStatus::ACTIVATED_MCC_PENDING
        ]);

        if (empty($merchantIdList) === true)
        {
            $this->trace->info(TraceCode::SELF_SERVE_CRON_FAILURE, [
                'type'   => 'escalation ' . $type,
                'reason' => 'no merchants to run the cron'
            ]);

            return [];
        }

        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIdList);

        $escalationLevelMap = $this->fetchEscalationLevelMapForMerchants(
            $merchants, $type, $latestEscalationsForMerchant);

        return $escalationLevelMap;
    }

    /**
     * Method that returns a map of latest escalation for a merchant.
     * Each merchant may have gone through many escalations for a given type (level 1, 2,3)
     * This method basically loops over escalations and returns the map of latest escalations
     * e.g:
     * [
     *   mid1 => Escalation1,
     *   mid2 => Escalation2
     * ]
     *
     * @param $escalations
     *
     * @return array
     */
    private function getLatestEscalations($escalations)
    {
        $latestEscalationsMap = [];

        foreach ($escalations as $escalation)
        {
            $mid = $escalation->getMerchantId();
            if (isset($latestEscalationsMap[$mid]) === false)
            {
                // since escalations are in desc order of created_at...
                $latestEscalationsMap[$mid] = $escalation;
            }
        }

        return $latestEscalationsMap;
    }

    /**
     * Method to return a map that tells which merchants fall under what escalation level for a given type
     * e.g:
     * [
     *      1 => [Merchant1, Merchant2..],
     *      2 => [Merchant3, ....]
     * ]
     *
     * @param $merchants
     * @param $type
     * @param $latestEscalationsForMerchant
     *
     * @return array -> map
     */
    private function fetchEscalationLevelMapForMerchants($merchants, $type, $latestEscalationsForMerchant)
    {
        $cronTime           = Carbon::now(Timezone::IST)->getTimestamp();
        $escalationLevelMap = [];

        foreach ($merchants as $merchant)
        {
            $escalation = $latestEscalationsForMerchant[$merchant->getId()];

            // fetch whats the next level for the type and see if that's possible based on time diff from now (cronTime)
            $nextLevel          = Utils::getNextEscalationLevel($type, $escalation->getLevel());
            $escalationPossible = Utils::isEscalationPossible($escalation, $cronTime, $type, $nextLevel);

            if ($escalationPossible)
            {
                // utility method that helps create the map: $escalationLevelMap
                Utils::appendValueToKey($merchant, $nextLevel, $escalationLevelMap);
            }
        }

        return $escalationLevelMap;
    }
}
