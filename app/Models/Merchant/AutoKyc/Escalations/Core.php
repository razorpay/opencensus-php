<?php

namespace RZP\Models\Merchant\AutoKyc\Escalations;

use Mail;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Transaction\Entity as TEntity;
use RZP\Http\Controllers\CmmaProxyController;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Mail\Merchant\HardLimitLevelThreeEmail;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Escalations as NewEscalation;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\AutoKyc\Escalations\Types\CmmaEscalation;
use RZP\Models\Merchant\Escalations\Constants as EscalationConstant;

class Core extends Base\Core
{
    private function filterMerchantIdsNotEscalatedToType($merchantIdList, string $type)
    {
        $escalations = $this->repo->merchant_auto_kyc_escalations->fetchEscalationsForMerchants($merchantIdList, $type);
        $excludeIds  = [];

        foreach ($escalations as $escalation)
        {
            $excludeIds[] = $escalation->getMerchantId();
        }

        return array_diff($merchantIdList, $excludeIds);
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
     * Main method that triggers SOFT_LIMIT breach level 1 escalations for all merchants
     * who fall under this category
     */
    public function handleSoftLimitBreach()
    {
        $this->trace->info(TraceCode::SELF_SERVE_CRON, [
            'type' => Constants::SOFT_LIMIT
        ]);

        // fetch all the merchants who are in activated_mcc_pending state
        $merchantIdList = $this->repo->merchant_detail->fetchMerchantIdsByActivationStatus(
            [DetailStatus::ACTIVATED_MCC_PENDING], OrgEntity::ORG_ID_LIST
        );

        $this->trace->info(TraceCode::SELF_SERVE_CRON, [
            'type'         => Constants::SOFT_LIMIT,
            'allmidscount' => count($merchantIdList)
        ]);

        $merchantIdChunks = array_chunk($merchantIdList, 20);

        foreach ($merchantIdChunks as $merchantIdList) {
            // filter merchants who have not escalated to soft limit already
            $merchantIdList = $this->filterMerchantIdsNotEscalatedToType($merchantIdList, Constants::SOFT_LIMIT);

            // filter merchants who have crossed settlements above threshold
            $merchantsGmvList = $this->repo->transaction->fetchTotalAmountByTransactionTypeAboveThreshold(
                $merchantIdList, MConstants::PAYMENT, env(Constants::SOFT_LIMIT_MCC_PENDING_THRESHOLD));

            $merchantIdList = array_map(function($element) {
                return $element[Entity::MERCHANT_ID];
            }, $merchantsGmvList);

            if (empty($merchantIdList) === true)
            {
                $this->trace->info(TraceCode::SELF_SERVE_CRON_FAILURE, [
                    'type'   => Constants::SOFT_LIMIT,
                    'reason' => 'no merchants to run the cron'
                ]);
                continue;
            }

            $merchants = $this->repo->merchant->findManyByPublicIds($merchantIdList);

            // finally, raise escalations
            (new Handler)->handleEscalations($merchants, $merchantsGmvList, Constants::SOFT_LIMIT, 1);

            // CMMA triggers should not affect the usual flow
            try {

                // trigger CMMA escalations for merchants who breached soft limit
                (new CmmaEscalation)->triggerCMMAEscalation($merchants, Constants::SOFT_LIMIT, 1);

            }
            catch (\Throwable $err) { // Exception in this flow should not affect the primary escalation flow

                $this->trace->error(TraceCode::CMMA_ESCALATION_ATTEMPT_FAILURE, [
                    'error'   => $err
                ]);

            }
        }
    }

    /**
     * Main method that triggers HARD_LIMIT breach level 1 escalations for all merchants
     * who fall under this category
     */
    public function handleHardLimitBreach()
    {
        $this->trace->info(TraceCode::SELF_SERVE_CRON, [
            'type' => Constants::HARD_LIMIT
        ]);

        // fetch all merchants who have not escalated to hard limit
        // this will fetch merchants who have crossed soft limit but not hard limit
        $merchantIdList = $this->repo->merchant_auto_kyc_escalations->fetchMerchantIdsNotEscalatedToType(
            Constants::HARD_LIMIT);

        // merchants who crossed soft limit, may get rejected, activated upon manual review
        // so filter only those who are in NC, under review, mcc pending
        $merchantIdList = $this->repo->merchant_detail->filterMerchantIdsByActivationStatus(
            $merchantIdList, [
            DetailStatus::NEEDS_CLARIFICATION,
            DetailStatus::UNDER_REVIEW,
            DetailStatus::ACTIVATED_MCC_PENDING
        ]);

        // filter merchants who have crossed payments above threshold
        $merchantsGmvList = $this->repo->transaction->fetchTotalAmountByTransactionTypeAboveThreshold(
            $merchantIdList, MConstants::PAYMENT, env(Constants::HARD_LIMIT_MCC_PENDING_THRESHOLD));

        $merchantIdList = array_map(function($element) {
            return $element[Entity::MERCHANT_ID];
        }, $merchantsGmvList);
        if (empty($merchantIdList) === true)
        {
            $this->trace->info(TraceCode::SELF_SERVE_CRON_FAILURE, [
                'type'   => Constants::HARD_LIMIT,
                'reason' => 'no merchants to run the cron',
                'count'  => count($merchantIdList),
            ]);

            return;
        }
        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIdList);
        // finally raise escalations
        (new Handler)->handleEscalations($merchants, $merchantsGmvList, Constants::HARD_LIMIT, 1);
        // trigger CMMA escalations
        (new CmmaEscalation)->triggerCMMAEscalation($merchants, Constants::HARD_LIMIT, 1);

    }

    /**
     * Main method that takes care of fetching and handling escalations (above level 1) according to:
     * - merchants activation status
     * - previous escalation raised for the merchant
     * - which escalation to raise for the merchant
     */
    public function handleEscalationsCron()
    {
        $this->trace->info(TraceCode::SELF_SERVE_CRON, [
            'type' => 'escalation_cron'
        ]);

        $this->handleEscalationsForType(Constants::SOFT_LIMIT);
        $this->handleEscalationsForType(Constants::HARD_LIMIT);
    }

    /**
     * Method to trigger Escalations for a given Type
     *
     * @param string $type
     */
    private function handleEscalationsForType(string $type)
    {
        $threshold = ($type == Constants::SOFT_LIMIT) ? env(Constants::SOFT_LIMIT_MCC_PENDING_THRESHOLD) : env(Constants::HARD_LIMIT_MCC_PENDING_THRESHOLD);

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

            return;
        }

        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIdList);

        $escalationLevelMap = $this->fetchEscalationLevelMapForMerchants(
            $merchants, $type, $latestEscalationsForMerchant);

        // finally raise escalations for each type
        foreach ($escalationLevelMap as $level => $merchants)
        {
            $this->trace->info(TraceCode::SELF_SERVE_ESCALATION_ATTEMPT, [
                'type'      => 'escalation ' . $type,
                'level'     => $level,
                'merchants' => array_map(function($merchant) {
                    return $merchant->getId();
                }, $merchants)
            ]);

            $merchantIdList = array_map(function($merchant) {
                return $merchant->getId();
            }, $merchants);

            $merchantsGmvList = $this->repo->transaction->fetchTotalAmountByTransactionTypeAboveThreshold(
                $merchantIdList, MConstants::PAYMENT, $threshold);

            (new Handler)->handleEscalations($merchants, $merchantsGmvList, $type, $level);

        }
    }

    /**
     * This method is just to save V1 triggered escalations to V2 escalation entity
     * In future we'll move everything from V1 to V2.
     *
     * @param $merchantsList array of total settled amonut to a merchant
     * @param $milestone
     */
    private function saveEscalationToV2($merchantsList, $milestone)
    {
        foreach ($merchantsList as $data)
        {
            $merchantId = $data[Entity::MERCHANT_ID];
            $amount     = $data['total'];
            $threshold  = env(Constants::HARD_LIMIT_MCC_PENDING_THRESHOLD);

            (new NewEscalation\Handler)->triggerEscalation(
                $merchantId, $amount, $threshold,
                (new NewEscalation\Core)->getEscalationConfigForThresholdAndMilestone($threshold, $milestone),
                NewEscalation\Constants::PAYMENT_BREACH
            );

        }
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

    private function sendMailToInformHardLimitReached(MerchantEntity $merchant)
    {
        $notificationBlocked = (new PartnerCore())->isSubMerchantNotificationBlocked($merchant->getId());

        if ($notificationBlocked === true)
        {
            return;
        }

        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $data = [
            DEConstants::MERCHANT => [
                MerchantEntity::NAME          => $merchant->getName(),
                MerchantEntity::BILLING_LABEL => $merchant->getBillingLabel(),
                MerchantEntity::EMAIL         => $merchant->getEmail(),
                DEConstants::ORG              => [
                    DEConstants::HOSTNAME => $org->getPrimaryHostName(),
                ]
            ],
        ];

        $email = new HardLimitLevelThreeEmail($data, $org->toArray());

        Mail::queue($email);
    }
}
