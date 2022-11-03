<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Website\Service as WebsiteService;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant;

class WebsiteCompliancePaymentsEnabledDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['rzp.mode'] = Mode::LIVE;

        //fetch merchants who has payments enabled
        $merchantIdList = $this->repo->state->fetchPaymentsEnabledMerchants($startTime, $endTime);

        if (empty($merchantIdList) === true)
        {
            $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                'merchants_count' => 0,
                'args'            => $this->args,
                'reason'          => 'no liveInIntervalMerchants found'
            ]);

            return CollectorDto::create([]);
        }

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'merchants_count' => count($merchantIdList),
            'filter'          => 'liveInIntervalMerchants',
            'args'            => $this->args
        ]);

        //filter merchants whose first time payments enabled
        $merchantIdList = $this->repo->state->filterPaymentsEnabledMerchants($merchantIdList, $startTime, $endTime);

        if (empty($merchantIdList) === true)
        {
            $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                'merchants_count' => 0,
                'args'            => $this->args,
                'reason'          => 'no liveInIntervalMerchants found'
            ]);

            return CollectorDto::create([]);
        }

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'merchants_count' => count($merchantIdList),
            'filter'          => 'liveInIntervalMerchants',
            'args'            => $this->args
        ]);

        // filter all merchants who are not activated
        $merchantIdList = $this->repo->merchant_detail->filterMerchantIdsByActivationStatus(
            $merchantIdList,
            [
                DetailStatus::INSTANTLY_ACTIVATED,
                DetailStatus::UNDER_REVIEW,
                DetailStatus::ACTIVATED_MCC_PENDING
            ]);

        if (empty($merchantIdList) === true)
        {
            $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                'merchants_count' => 0,
                'args'            => $this->args,
                'reason'          => 'no open merchants found'
            ]);

            return CollectorDto::create([]);
        }

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'merchants_count' => count($merchantIdList),
            'filter'          => 'OpenMerchants',
            'args'            => $this->args
        ]);

        // filter live merchants
        $merchantIdList = $this->repo->merchant->filterLiveMerchants($merchantIdList);

        if (empty($merchantIdList) === true)
        {
            $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                'merchants_count' => 0,
                'args'            => $this->args,
                'reason'          => 'no live merchants found'
            ]);

            return CollectorDto::create([]);
        }

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'merchants_count' => count($merchantIdList),
            'filter'          => 'LiveMerchants',
            'args'            => $this->args
        ]);

        $finalMerchantIdList = [];

        foreach ($merchantIdList as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findorFail($merchantId);

                if ((new WebsiteService())->isWebsiteSectionsApplicable($merchant) === true)
                {

                    $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                        'merchant' => $merchant->getId(),
                        'type'     => 'website_Adherence_applicable',
                        'args'     => $this->args
                    ]);

                    $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchant->getId());

                    if (empty(optional($websiteDetail)->getStatus()) === true)
                    {
                        array_push($finalMerchantIdList, $merchantId);
                    }
                }
            }
            catch (\Throwable $e)
            {
                $this->app['trace']->traceException(
                    $e,
                    null,
                    TraceCode::CRON_DATA_COLLECTOR_TRACE,
                    [
                        "merchant_id" => $merchantId,
                    ]);
            }
        }

        if (empty($finalMerchantIdList) === true)
        {
            $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                'merchants_count' => 0,
                'args'            => $this->args,
                'reason'          => 'no website incomplete merchants found'
            ]);

            return CollectorDto::create([]);
        }

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'merchants_count' => ($finalMerchantIdList),
            'filter'          => 'WebsiteIncompleteMerchants',
            'args'            => $this->args
        ]);

        $data["merchantIds"] = $finalMerchantIdList;

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
