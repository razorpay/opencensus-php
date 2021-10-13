<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;

class NpsDashboardClient extends Base\Service
{
    /**
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function getCohorts($surveyTTl) //survey_ttl is in hours
    {
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $startTimeStamp = Carbon::now(Timezone::IST)->subHours(24)->getTimestamp();

        $cohorts = $this->repo->payout->getPayoutDashboardCohortList($startTimeStamp, $currentTimeStamp);

        $this->trace->info(TraceCode::COHORT_PAYOUT_COUNT, ['Count' => count($cohorts)]);

        $cohorts = $cohorts->toArray();

        return $cohorts;
    }

    public function fetchMerchantUsers(array $merchantIdList)
    {
        return $this->repo->merchant_user->fetchAllBankingUserIdsForMerchantIds($merchantIdList);
    }
}
