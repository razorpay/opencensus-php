<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\SendNotificationAction;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;
use RZP\Models\Merchant\Cron\Collectors\FirstPaymentOfferDataCollector;

class FirstPaymentOfferCronJob extends BaseCronJob
{
    protected $dataCollectors = [FirstPaymentOfferDataCollector::class];

    protected $actions = [SendNotificationAction::class];

    protected $lastCronTimestampCacheKey = "first_payment_offer_cron_timestamp";

    protected $eventName =  OnboardingEvents::FIRST_PAYMENT_OFFER;
}
