<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Notifications\Onboarding\Events;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;
use RZP\Models\Merchant\Escalations\Core as EscalationCore;
use RZP\Models\Merchant\Escalations\Constants as EscalationConstants;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;

class WebsiteCompliancePaymentsEnabledAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        $collectorData = $data["merchant_notification_data"]; // since data collector is an array

        $data = $collectorData->getData();

        $merchantIds = $data["merchantIds"]??[];

        if (count($merchantIds) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;


        foreach ($merchantIds as $merchantId)
        {
            $args = [
                EscalationConstants::MERCHANT => $this->repo->merchant->findOrFailPublic($merchantId)
            ];

            $success = (new OnboardingNotificationHandler($args))
                ->sendEventNotificationForMerchant($merchantId, Events::WEBSITE_ADHERENCE_SOFT_NUDGE);

            $data = [
                StoreConstants::NAMESPACE                           => StoreConfigKey::ONBOARDING_NAMESPACE,
                StoreConfigKey::WEBSITE_INCOMPLETE_SOFT_NUDGE_COUNT => 5,
            ];

            (new StoreCore())->updateMerchantStore($merchantId, $data, StoreConstants::INTERNAL);

            $successCount += ($success === true);
        }

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($merchantIds)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }
}
