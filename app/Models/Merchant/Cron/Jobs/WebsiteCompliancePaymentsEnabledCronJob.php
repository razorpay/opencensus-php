<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\WebsiteCompliancePaymentsEnabledAction;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;
use RZP\Models\Merchant\Cron\Collectors\WebsiteCompliancePaymentsEnabledDataCollector;

class WebsiteCompliancePaymentsEnabledCronJob extends BaseCronJob
{
    protected $dataCollectors            = [
        "merchant_notification_data" => WebsiteCompliancePaymentsEnabledDataCollector::class
    ];

    protected $actions                   = [WebsiteCompliancePaymentsEnabledAction::class];

    protected $lastCronTimestampCacheKey = "website_compliance_payments_enabled_cron_timestamp";
}
