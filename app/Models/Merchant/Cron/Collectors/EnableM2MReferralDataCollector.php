<?php

namespace RZP\Models\Merchant\Cron\Collectors;


use RZP\Constants\Mode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Service as MerchantService;
use RZP\Trace\TraceCode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
/**
 * collects merchants who have transacted in the past day +
 * merchants in activated state +
 * merchants with payment is above a threshold +
 * merchants activated for min time +
 * merchants with feature already not created
 * merchants belonging to razorpay org
 */
class EnableM2MReferralDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        // Filter out all merchants that have transacted since last time cron ran
        $merchants = $this->repo->transaction->fetchTransactedMerchants(
            'payment', $this->lastCronTime);

        $this->app['trace']->info(TraceCode::M2M_REFERRALS_ENABLE_CRON_TRACE, [
            'last_cron_time'  => $this->lastCronTime,
            'type'            => 'm2m_referral',
            'filter'          => 'transactedMerchants',
            'merchants_count' => count($merchants),
        ]);

        // filter merchants of string RAZORPAY ORG and all merchants who are activated
        $merchants = $this->repo->merchant_detail->filterMerchantIdsByActivationStatus(
            $merchants, [DetailStatus::ACTIVATED]);

        $this->app['trace']->info(TraceCode::M2M_REFERRALS_ENABLE_CRON_TRACE, [
            'type'            => 'm2m_referral',
            'filter'          => 'activatedMerchants',
            'merchants_count' => count($merchants),
        ]);

        // filter all merchants who've have been activated for min no of days and belonging to razorpay org
        $merchants = $this->repo->merchant->filterMerchantIdsWithMinActivatedTime(
            $merchants, env(Constants::M2M_REFERRAL_ENABLE_AFTER_MIN_ACTIVATED_TIME));

        $this->app['trace']->info(TraceCode::M2M_REFERRALS_ENABLE_CRON_TRACE, [
            'type'            => 'm2m_referral',
            'filter'          => 'activatedMinTime',
            'merchants_count' => count($merchants),
        ]);

        //filter already m2m referral enabled merchants
        $m2mReferralEnabledMerchants = $this->repo->feature->getMerchantIdsHavingFeature(Feature\Constants::M2M_REFERRAL, $merchants);

        $merchants = array_diff($merchants, $m2mReferralEnabledMerchants);

        $this->app['trace']->info(TraceCode::M2M_REFERRALS_ENABLE_CRON_TRACE, [
            'merchants_count' => count($merchants),
            'filter'          => 'featureNotAlreadyCreated',
            'type'            => 'm2m_referral'
        ]);

        if (empty($merchants) === true)
        {
            $this->app['trace']->info(TraceCode::M2M_REFERRALS_CRON_SKIP, [
                'merchants_count' => 0,
                'type'            => 'm2m_referral',
                'reason'          => 'no merchants found'
            ]);

            return CollectorDto::create([]);
        }

        $experimentResult       = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(),
            RazorxTreatment::DRUID_MIGRATION,
            Mode::LIVE);

        $isDruidMigrationEnabled = ( $experimentResult === 'on' ) ? true : false;

        if($isDruidMigrationEnabled === true)
        {
            $merchantData = (new MerchantService)->getDataFromPinotForMerchantIds($merchants);
        }
        else
        {
            $merchantData = (new MerchantService)->getDataFromDruidForMerchantIds($merchants);
        }

        return CollectorDto::create($merchantData);

    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime;
    }

    protected function getEndInterval(): int
    {
        return $this->cronStartTime;
    }

}
