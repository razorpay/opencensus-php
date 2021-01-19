<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Timezone;

class NpsClient extends Base\Service
{
    /**
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function getCohorts()
    {
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $startTimeStamp = Carbon::now(Timezone::IST)->subHours(24)->getTimestamp();

        $cohorts = $this->repo->payout->getPayoutCohortList($startTimeStamp, $currentTimeStamp);

        $cohorts = $cohorts->toArray();

        return $cohorts;
    }

    public function fetchMerchantUsers(array $merchantIdList)
    {
        return $this->repo->merchant_user->fetchAllBankingUserIdsForMerchantIds($merchantIdList);
    }
}
