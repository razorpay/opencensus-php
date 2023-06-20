<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Balance\Type as BalanceType;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use RZP\Models\Merchant\Cron\Collectors\Core\DbDataCollector;


class PreActivationMerchantReleaseFundsDataCollector extends DbDataCollector
{
    /*
     We are releasing settlements for the merchant who is rejected, and it has been 120 days since rejection
     Right now funds will only be released for those merchant who have bank details verified, poi verified and poa verified
     We are considering maximum rejection date as 1.6 years back and minimum rejection date as 120 days back
    */

    public function collectDataFromSource(): CollectorDto
    {
        /*
        Initially setting the run timeout for the cron as 120 minutes , moving to prod will see how much time it is
        taking and will change it accordingly further
        */

        $finalMerchantIdList = [];

        $merchantState = Detail\Entity::REJECTED;

        $this->app["trace"]->info(TraceCode::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS);

        $startDate = Carbon::today(Timezone::IST)->subDays(120)->getTimestamp();

        $endDate = Carbon::createFromTimestamp($startDate)->subDays(120)->getTimestamp();

        // We are processing data in batches for merchants upto 16 months old, we are processing in batches of 4 months
        // hence ran the loop 4 time and everytime 4 months will get incremented in the max and min rejection date

        for ($i = 0; $i < 4; $i++)
        {
            $result = $this->getMerchantIdsInBatches($merchantState, $startDate, $endDate);

            $startDate = $endDate;

            $endDate = Carbon::createFromTimestamp($endDate)->subDays(120)->getTimestamp();

            $finalMerchantIdList = array_merge($finalMerchantIdList, $result);
        }

        /* @todo send segment event for rejected merchants with their detail */

        $this->app["trace"]->info(TraceCode::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS_INPUT, [
            'final_merchant_ids' => $finalMerchantIdList
        ]);

        /* @todo pull lea flags, risk flags, and chargebackflags into the workflow details */

        if (empty($finalMerchantIdList) === true)
        {
            $this->app['trace']->info(TraceCode::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS_FAILED, [
                'type'   => Constants::ONBOARDING_REJECTED_SETTLEMENT_CLEARANCE,
                'reason' => 'no merchants to run the cron'
            ]);

            return CollectorDto::create([]);
        }

        $data[CronConstants::MERCHANT_IDS] = $finalMerchantIdList;

        return CollectorDto::create($data);
    }

    protected function getMerchantIdsInBatches($merchantState, $startDate, $endDate)
    {
        // Below are the conditions which the merchant should satisfy for the settlements to be released

        // Merchant Ids with rejected activation status

        $rejectedMerchantIds = $this->repo->state->getEntityIdsWithNameInRange($merchantState, $endDate, $startDate);

        $this->app['trace']->info(TraceCode::REJECTED_MERCHANT_IDS, [
            'rejectedMerchantIds' => $rejectedMerchantIds
        ]);

        // Merchant ids with razorpay org and poi/bank details verified
        $rzpOrgMerchantIdsWithPoiBankDetailsVerified = $this->repo->merchant_detail->fetchRzpOrgMerchantIdsWithPoiAndBankVerified($rejectedMerchantIds);

        $this->app['trace']->info(TraceCode::POI_BANK_ACCOUNT_VERIFIED_MERCHANTS, [
            'PoiBankDetailsVerifiedMerchants' => $rzpOrgMerchantIdsWithPoiBankDetailsVerified
        ]);

        // Merchants can have either poa status verified in merchant detail or they can have it through aadhaar xml scan , voter id etc
        // which is stored in the stakeholder repo

        $poaStatusVerifiedMerchants = $this->repo->merchant_detail->filterMerchantIdsWithPoaStatusVerified($rzpOrgMerchantIdsWithPoiBankDetailsVerified);

        $this->app['trace']->info(TraceCode::POA_STATUS_VERIFIED_MERCHANTS,[
            'poaStatusVerifiedMerchants' => $poaStatusVerifiedMerchants
        ]);

        // filter merchants with positive primary balance
        $merchantIdsWithPositivePrimaryBalance = $this->filterPositivePrimaryBalanceMerchants($poaStatusVerifiedMerchants);

        /* Below is the common function that we use along with the risk team. The conditions to filter out the merchants
         are mentioned in the function itself . As this is a normal function call and the function is present inside the
         data collector hence passing the values below of last cron time, cron start time and empty array in args  */

        $finalMerchantIdList = (new FOHRemovalDataCollector(0, 0, []))
            ->getMerchantsNotApplicableForFOH($merchantIdsWithPositivePrimaryBalance);

        $this->app['trace']->info(TraceCode::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS_FINAL_LIST, [
            'final_merchant_ids' => $finalMerchantIdList
        ]);

        return $finalMerchantIdList;
    }

    private function filterPositivePrimaryBalanceMerchants($merchantIdList)
    {
        $result = [];

        $balances = $this->repo->balance->getBalancesForMerchantIds($merchantIdList, BalanceType::PRIMARY);

        foreach ($balances as $merchantId => $balance)
        {
            if (empty($balance) === false)
            {
                if ($balance > 0)
                {
                    $result[] = stringify($merchantId);
                }
            }
        }

        $this->app['trace']->info(TraceCode::POSITIVE_PRIMARY_BALANCE_MERCHANTS,[
            'primary_balance_positive' => $result
        ]);

        return $result;
    }
}


