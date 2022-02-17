<?php

namespace RZP\Models\Merchant\Cron\Actions;

use Carbon\Carbon;
use RZP\Diag\EventCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\M2MReferral\Service;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Feature;
use RZP\Models\Merchant\Store;

class EnableM2MReferralAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collector = $data["enable_m2m_referral"];
        $druidData = $collector->getData();

        foreach ($druidData as $data)
        {
            $this->app['trace']->info(TraceCode::M2M_REFERRALS_ENABLE_CRON_TRACE, [
                'data' => $data
            ]);

            $merchantId = $data['merchant_details_merchant_id'];
            $gmv        = $data[Merchant\Service::SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV];

            if (empty($gmv) === false and
                $gmv > env(MerchantConstants::M2M_REFERRAL_ENABLE_AFTER_MIN_TRANSACTION_VOLUME) / 100)
            {
                $txns = $this->repo->transaction->isMerchantPaymentCountAboveThreshold($merchantId,
                                                                                       env(MerchantConstants::M2M_REFERRAL_MIN_TRANSACTION_COUNT));

                $this->app['trace']->info(TraceCode::M2M_REFERRALS_ENABLE_CRON_TRACE, [
                    'merchantId' => $merchantId,
                    'txns'       => $txns
                ]);

                if (empty($txns) === false &&
                    count($txns) >= env(MerchantConstants::M2M_REFERRAL_MIN_TRANSACTION_COUNT))
                {
                    $this->app['trace']->info(TraceCode::M2M_REFERRALS_ENABLE_CRON_TRACE, [
                        'enableM2M' => $merchantId
                    ]);

                    $this->enableM2MReferral($merchantId);
                }
            }

        }

        $this->app['segment-analytics']->buildRequestAndSend(true);

        return new ActionDto(Constants::SUCCESS);
    }

    private function enableM2MReferral($merchantId)
    {
        try
        {
            (new Feature\Core)->create([

                                           Feature\Entity::ENTITY_TYPE => E::MERCHANT,
                                           Feature\Entity::ENTITY_ID   => $merchantId,
                                           Feature\Entity::NAME        => Feature\Constants::M2M_REFERRAL,
                                       ], $shouldSync = true);

            $data = [
                Store\Constants::NAMESPACE                    => Store\ConfigKey::ONBOARDING_NAMESPACE,
                Store\ConfigKey::REFERRED_COUNT               => 0,
                Store\ConfigKey::REFERRAL_SUCCESS_POPUP_COUNT => 0,
                Store\ConfigKey::REFEREE_SUCCESS_POPUP_COUNT  => 0
            ];

            (new Store\Core())->updateMerchantStore($merchantId, $data, Store\Constants::INTERNAL);

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $input = (new Service)->fetchReferralDetails($merchant);

            $input['experiment_timestamp'] = Carbon::now()->getTimestamp();

            $this->app['segment-analytics']->pushTrackEvent(
                $merchant, $input, SegmentEvent::M2M_ENABLED);

            $this->app['diag']->trackOnboardingEvent(EventCode::M2M_ENABLED, $merchant, null, $input);

            if ($input['can_refer'] === true)
            {
                $this->app['segment-analytics']->pushTrackEvent(
                    $merchant, $input, SegmentEvent::M2M_ENABLED_EXPERIMENT);

                $this->app['diag']->trackOnboardingEvent(EventCode::M2M_ENABLED_EXPERIMENT, $merchant, null, $input);

            }

        }
        catch (\Exception $e)
        {
            $this->app['trace']->info(TraceCode::M2M_REFERRALS_ENABLE_CRON_FAILED, [
                'reason'      => 'something went wrong while enabling m2m referral feature',
                'trace'       => $e->getMessage(),
                'merchant_id' => $merchantId
            ]);
        }
    }
}
