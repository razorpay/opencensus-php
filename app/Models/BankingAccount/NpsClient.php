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
    public function getCohorts($surveyTTL)
    {
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $startTimeStamp = Carbon::now(Timezone::IST)->subHours(24)->getTimestamp();

        $cohorts = $this->repo->banking_account->getCAOnboardCohortList($startTimeStamp, $currentTimeStamp);

        $this->trace->info(TraceCode::COHORT_ONBOARD_COUNT, ['Count' => count($cohorts)]);

        $cohorts = $cohorts->toArray();

        return $cohorts;
    }

    public function fetchMerchantUsers(array $merchantIdList)
    {
        return $this->repo->merchant_user->fetchAllBankingUserIdsForMerchantIds($merchantIdList);
    }
}
