<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Website\Constants;
use RZP\Models\Merchant\Escalations\Entity as EscalationEntity;
use RZP\Models\Merchant\Website\Entity as WebsiteEntity;
use RZP\Models\Merchant\Escalations\Constants as EscalationConstants;
use RZP\Models\Merchant\Website\Service as WebsiteService;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant;

class WebsiteComplianceGracePeriodReminderDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['rzp.mode'] = Mode::LIVE;

        // fetch all merchants who are activated and in grace period
        $gracePeriodMerchantIdList = $this->repo->merchant_website->fetchMerchantIdsInGracePeriod();

        // fetch all merchants who are in instantly activated and activated_mcc_pending
        $paymentsEnabledMerchantIdList = $this->repo->merchant_detail->fetchMerchantIdsByActivationStatus(
            [DetailStatus::ACTIVATED_MCC_PENDING, DetailStatus::INSTANTLY_ACTIVATED], OrgEntity::ORG_ID_LIST
        );

        $merchantIdList = array_merge($gracePeriodMerchantIdList, $paymentsEnabledMerchantIdList);

        if (empty($merchantIdList) === true)
        {
            $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                'merchants_count' => 0,
                'args'            => $this->args,
                'reason'          => 'no merchants found'
            ]);

            return CollectorDto::create([]);
        }

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'merchants_count' => count($merchantIdList),
            'filter'          => 'Merchants',
            'args'            => $this->args
        ]);

        // fetch all merchants who have not disabled communications
        $merchantIdList = $this->repo->merchant_website->filterMerchantIdsWithCommunicationsEnabled($merchantIdList);

        //fetch merchants whose payment volume is above 3 lakhs
        $query = "select sum(base_amount) amount,merchant_id from payments_v1 where merchant_id in (%s) and created_at< %s  group by merchant_id having amount>=%s limit %s";

        $query = sprintf($query, "'" . implode("','", $merchantIdList) . "'",
                         $endTime,
                         30000000,
                         count($merchantIdList) + 1);

        $queryResponse = $this->app['apache.pinot']->getDataFromPinot($query);

        if (empty($queryResponse) === true)
        {
            $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                'reason' => 'no merchants found',
                'args'   => $this->args,
                'filter' => 'transacted_merchants'
            ]);

            return CollectorDto::create([]);
        }

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'merchants_count' => count($queryResponse),
            'type'            => 'transacted_merchants',
            'args'            => $this->args
        ]);

        $merchantIdList = array_column($queryResponse, WebsiteEntity::MERCHANT_ID);

        //fetch merchants whose payment volume is above 3 lakh in the current job run and not in previous job run
        $query = "select sum(base_amount) amount,merchant_id from payments_v1 where merchant_id in (%s) and created_at< %s group by merchant_id having amount<%s limit %s";

        $query = sprintf($query, "'" . implode("','", $merchantIdList) . "'",
                         $startTime,
                         30000000,
                         count($merchantIdList) + 1);

        $queryResponse = $this->app['apache.pinot']->getDataFromPinot($query);

        if (empty($queryResponse) === true)
        {
            $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                'reason' => 'no merchants found',
                'args'   => $this->args,
                'filter' => 'transacted_merchants'
            ]);

            return CollectorDto::create([]);
        }

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'merchants_count' => count($queryResponse),
            'type'            => 'transacted_merchants',
            'args'            => $this->args
        ]);

        $merchantIdList = array_column($queryResponse, WebsiteEntity::MERCHANT_ID);

        $finalList = [];

        foreach ($merchantIdList as $merchantId)
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            if ((new WebsiteService)->isWebsiteSectionsApplicable($merchant) === false)
            {
                continue;
            }

            $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                'merchant' => $merchant->getId(),
                'type'     => 'website_Adherence_applicable',
                'args'     => $this->args
            ]);

            $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchant->getId());

            if (empty(optional($websiteDetail)->getStatus()) === true)
            {
                continue;
            }

            foreach (explode(',', Constants::VALID_MERCHANT_SECTIONS) as $sectionName)
            {

                $sectionStatus = $websiteDetail->getSectionStatus($sectionName);

                $publishedWebsite = $websiteDetail->getPublishedUrl($sectionName);

                $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                    'merchant'         => $merchant->getId(),
                    'type'             => 'website_Adherence_published',
                    'args'             => $this->args,
                    'sectionStatus'    => $sectionStatus,
                    'sectionName'      => $sectionName,
                    'publishedWebsite' => $publishedWebsite
                ]);

                if (($sectionStatus === 3 and empty($publishedWebsite) === false) or
                    $sectionStatus === 2)
                {
                    array_push($finalList, $merchantId);
                }
            }
        }

        if (empty($finalList) === true or
            count($finalList) === 0)
        {
            $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                'reason' => 'no merchants found',
                'args'   => $this->args,
                'filter' => 'published_merchants'
            ]);

            return CollectorDto::create([]);
        }

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'merchants_count' => count($finalList),
            'type'            => 'published_merchants',
            'args'            => $this->args
        ]);

        $data["merchantIds"] = $finalList;

        return CollectorDto::create($data);
    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime - 60 * 60;
    }

    protected function getEndInterval(): int
    {
        return $this->cronStartTime;
    }
}
