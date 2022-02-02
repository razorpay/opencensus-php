<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\SendNotificationAction;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;
use RZP\Models\Merchant\Cron\Collectors\FirstPaymentOfferDataCollector;

class FirstPaymentOfferCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_notification_data" => FirstPaymentOfferDataCollector::class
    ];

    protected $actions = [SendNotificationAction::class];

    protected $lastCronTimestampCacheKey = "first_payment_offer_cron_timestamp";

    protected $defaultArgs = [
        'event_name' => OnboardingEvents::FIRST_PAYMENT_OFFER
    ];
}
