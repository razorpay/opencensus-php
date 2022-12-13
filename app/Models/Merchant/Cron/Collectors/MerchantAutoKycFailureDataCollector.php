<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Core;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use RZP\Models\Merchant\Cron\Collectors\Core\DbDataCollector;

class MerchantAutoKycFailureDataCollector extends DbDataCollector
{

    protected function collectDataFromSource(): CollectorDto
    {
        $this->app['trace']->info(TraceCode::SELF_SERVE_CRON, [
            'type' => Constants::AUTO_KYC_FAILURE
        ]);

        // fetch all merchants who are UNDER_REVIEW and have submitted their L2 form.
        $merchantIdList = $this->repo->merchant_detail->findMerchantByActivationStatusAndActivationFormMileStone(
            [DetailStatus::UNDER_REVIEW], OrgEntity::ORG_ID_LIST, $this->lastCronTime
        );

        $this->app['trace']->info(TraceCode::SELF_SERVE_CRON_AUTO_KYC_FAILURE, [
            'type'              => Constants::AUTO_KYC_FAILURE,
            'total_mid_count'   => count($merchantIdList),
            'merchant_ids'      => $merchantIdList
        ]);

        $merchantIdChunks = array_chunk($merchantIdList, 20);

        $finalMerchantIdList = [];

        foreach ($merchantIdChunks as $merchantIdList) {

            // filter merchants whose auto_kyc has failed
            $merchantIdList = $this->filterAutoKycFailedMerchants($merchantIdList);

            $finalMerchantIdList = array_merge($finalMerchantIdList, $merchantIdList);

            $this->app['trace']->info(TraceCode::SELF_SERVE_CRON_AUTO_KYC_FAILURE, [
                'type'                      => Constants::AUTO_KYC_FAILURE,
                'final_auto_kyc_count'      => count($merchantIdList),
                'final_merchant_id_list'    => $merchantIdList
            ]);
        }

        if (empty($merchantIdList) === true) {
            $this->app['trace']->info(TraceCode::SELF_SERVE_CRON_FAILURE, [
                'type'      => Constants::AUTO_KYC_FAILURE,
                'reason'    => 'no merchants to run the cron'
            ]);
            return CollectorDto::create([]);
        }

        $data[CronConstants::MERCHANT_IDS] = $finalMerchantIdList;

        $data[CronConstants::CASE_TYPE] = Constants::AUTO_KYC_FAILURE;

        $data[CronConstants::LEVEL] = 1;

        return CollectorDto::create($data);
    }

    private function filterAutoKycFailedMerchants($merchantIdList) : array
    {
        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIdList);

        $includeIds  = [];

        foreach ($merchants as $merchant)
        {
            // at L2 activation milestone merchant details will always exist
            $merchantDetails = $merchant->merchantDetail;

            $autoKycDone = (new Core)->isAutoKycDone($merchantDetails);

            $activationStatus = (new Core)->getApplicableActivationStatus($merchantDetails);

            $this->app['trace']->info(TraceCode::SELF_SERVE_CRON_AUTO_KYC_FAILURE, [
                'merchant_activation_status'    => $activationStatus
            ]);

            if ($autoKycDone === false)
            {
                $includeIds[] = $merchant->getId();
            }
        }

        return $includeIds;
    }
}
