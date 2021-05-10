<?php

namespace RZP\Models\Merchant\Balance;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;

class NpsClient extends Base\Service
{
    /**
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function getCohorts($surveyTTL) //survey_ttl is in hours
    {
        $surveyTTLInDays = $surveyTTL/24;

        $endTimeStamp = Carbon::now(Timezone::IST)->subDays($surveyTTLInDays)->getTimestamp();

        $startTimeStamp = Carbon::now(Timezone::IST)->subDays($surveyTTLInDays + 1)->getTimestamp();

        $endTimeStampCApayouts = Carbon::now(Timezone::IST)->getTimestamp();

        $cohortsList1 = $this->repo->balance->getCANpsCohortList($startTimeStamp, $endTimeStamp)->toArray();

        //granularity of this getCAPayoutCohortList is in days
        $cohortsList2 = $this->repo->payout->getCAPayoutCohortList($startTimeStamp, $endTimeStampCApayouts, $surveyTTLInDays)->toArray();

        $cohorts = array_unique(array_merge($cohortsList1, $cohortsList2), SORT_REGULAR);

        $this->trace->info(TraceCode::COHORT_ACTIVE_CA_COUNT, ['Count' => count($cohorts)]);

        return $cohorts;
    }

    public function fetchMerchantUsers(array $merchantIdList)
    {
        return $this->repo->merchant_user->fetchAllBankingUserIdsForMerchantIds($merchantIdList);
    }
}
