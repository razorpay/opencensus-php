<?php


namespace RZP\Models\DeviceDetail\Attribution;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createAttribution(array $input)
    {
        $attributionDetail = new Entity;

        $attributionDetail->generateId();

        $this->trace->info(TraceCode::CREATE_APPSFLYER_ATTRIBUTION_DETAILS, [
            'merchant_id'  => $input[Entity::MERCHANT_ID],
            'user_id'      => $input[Entity::USER_ID],
            'input'        => $input
        ]);

        $attributionDetail->build($input);

        $this->repo->app_attribution_detail->saveOrFail($attributionDetail);

        return $attributionDetail;
    }

    public function storeAttributionFromAppsflyer($merchantId, $userId, $data)
    {
        $entityData = [
            Entity::MERCHANT_ID     => $merchantId,
            Entity::USER_ID         => $userId,
            Entity::APPSFLYER_ID    => $data[Entity::APPSFLYER_ID],

            Entity::INSTALL_TIME               => $data[Entity::INSTALL_TIME] ?? '',
            Entity::EVENT_TYPE                 => $data[Entity::EVENT_TYPE] ?? '',
            Entity::EVENT_TIME                 => $data[Entity::EVENT_TIME] ?? '',
            Entity::CONTRIBUTOR_1_ATTRIBUTES   => [
                Constants::CONTRIBUTOR_1_TOUCH_TYPE     => $data[Constants::CONTRIBUTOR_1_TOUCH_TYPE] ?? '',
                Constants::CONTRIBUTOR_1_TOUCH_TIME     => $data[Constants::CONTRIBUTOR_1_TOUCH_TIME] ?? '',
                Constants::CONTRIBUTOR_1_PARTNER        => $data[Constants::CONTRIBUTOR_1_PARTNER] ?? '',
                Constants::CONTRIBUTOR_1_MATCH_TYPE     => $data[Constants::CONTRIBUTOR_1_MATCH_TYPE] ?? '',
                Constants::CONTRIBUTOR_1_MEDIA_SOURCE   => $data[Constants::CONTRIBUTOR_1_MEDIA_SOURCE] ?? '',
                Constants::CONTRIBUTOR_1_CAMPAIGN       => $data[Constants::CONTRIBUTOR_1_CAMPAIGN] ?? '',
            ],
            Entity::CONTRIBUTOR_2_ATTRIBUTES   => [
                Constants::CONTRIBUTOR_2_TOUCH_TYPE     => $data[Constants::CONTRIBUTOR_2_TOUCH_TYPE] ?? '',
                Constants::CONTRIBUTOR_2_TOUCH_TIME     => $data[Constants::CONTRIBUTOR_2_TOUCH_TIME] ?? '',
                Constants::CONTRIBUTOR_2_PARTNER        => $data[Constants::CONTRIBUTOR_2_PARTNER] ?? '',
                Constants::CONTRIBUTOR_2_MATCH_TYPE     => $data[Constants::CONTRIBUTOR_2_MATCH_TYPE] ?? '',
                Constants::CONTRIBUTOR_2_MEDIA_SOURCE   => $data[Constants::CONTRIBUTOR_2_MEDIA_SOURCE] ?? '',
                Constants::CONTRIBUTOR_2_CAMPAIGN       => $data[Constants::CONTRIBUTOR_2_CAMPAIGN] ?? '',
            ],
            Entity::CONTRIBUTOR_3_ATTRIBUTES   => [
                Constants::CONTRIBUTOR_3_TOUCH_TYPE     => $data[Constants::CONTRIBUTOR_3_TOUCH_TYPE] ?? '',
                Constants::CONTRIBUTOR_3_TOUCH_TIME     => $data[Constants::CONTRIBUTOR_3_TOUCH_TIME] ?? '',
                Constants::CONTRIBUTOR_3_PARTNER        => $data[Constants::CONTRIBUTOR_3_PARTNER] ?? '',
                Constants::CONTRIBUTOR_3_MATCH_TYPE     => $data[Constants::CONTRIBUTOR_3_MATCH_TYPE] ?? '',
                Constants::CONTRIBUTOR_3_MEDIA_SOURCE   => $data[Constants::CONTRIBUTOR_3_MEDIA_SOURCE] ?? '',
                Constants::CONTRIBUTOR_3_CAMPAIGN       => $data[Constants::CONTRIBUTOR_3_CAMPAIGN] ?? '',
            ],
            Entity::CAMPAIGN_ATTRIBUTES        => [
                Constants::SDK_VERSION                => $data[Constants::SDK_VERSION] ?? '',
                Constants::MEDIATION_NETWORK          => $data[Constants::MEDIATION_NETWORK] ?? '',
                Constants::MONETIZATION_NETWORK       => $data[Constants::MONETIZATION_NETWORK] ?? '',
                Constants::CONVERSION_TYPE            => $data[Constants::CONVERSION_TYPE] ?? '',
                Constants::CAMPAIGN_TYPE              => $data[Constants::CAMPAIGN_TYPE] ?? '',
                Constants::EVENT_SOURCE               => $data[Constants::EVENT_SOURCE] ?? '',
                Constants::MEDIA_SOURCE               => $data[Constants::MEDIA_SOURCE] ?? '',
                Constants::CHANNEL                    => $data[Constants::CHANNEL] ?? '',
                Constants::CAMPAIGN                   => $data[Constants::CAMPAIGN] ?? '',
                Constants::CAMPAIGN_ID                => $data[Constants::CAMPAIGN_ID] ?? '',
                Constants::ADSET_ID                   => $data[Constants::ADSET_ID] ?? '',
                Constants::AD_ID                      => $data[Constants::AD_ID] ?? '',
                Constants::ATTRIBUTED_TOUCH_TYPE      => $data[Constants::ATTRIBUTED_TOUCH_TYPE] ?? '',
                Constants::ATTRIBUTED_TOUCH_TIME      => $data[Constants::ATTRIBUTED_TOUCH_TIME] ?? '',
            ],
            Entity::DEVICE_TYPE                => $data[Entity::DEVICE_TYPE] ?? '',
            Entity::DEVICE_CATEGORY            => $data[Entity::DEVICE_CATEGORY] ?? '',
            Entity::PLATFORM                   => $data[Entity::PLATFORM] ?? '',
            Entity::OS_VERSION                 => $data[Entity::OS_VERSION] ?? '',
            Entity::APP_VERSION                => $data[Entity::APP_VERSION] ?? '',
        ];

        $this->createAttribution($entityData);
    }
}
