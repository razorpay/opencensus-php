<?php

namespace RZP\Models\BankingAccount;

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
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $startTimeStamp = Carbon::now(Timezone::IST)->subHours(24)->getTimestamp();

        $cohortActivatedCA = $this->repo->banking_account->getCAOnboardCohortList($startTimeStamp, $currentTimeStamp)->toArray();

        $cohortArchivedCA = $this->repo->banking_account->getCAArchivedCohortList($startTimeStamp, $currentTimeStamp)->toArray();

        $cohorts = array_merge($cohortActivatedCA, $cohortArchivedCA);

        $this->trace->info(TraceCode::COHORT_ONBOARD_COUNT, ['Count' => count($cohorts)]);

        return $cohorts;
    }

    public function fetchMerchantUsers(array $merchantIdList)
    {
        return $this->repo->merchant_user->fetchAllBankingUserIdsForMerchantIds($merchantIdList);
    }
}
