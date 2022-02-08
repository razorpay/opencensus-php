<?php
// TODO: Will be removed once FriendBuySendPurchaseEvent cron job is deployed to production.

namespace RZP\Models\Merchant\Onboarding\Cron;

use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\Entity as DEntity;
use RZP\Models\Merchant\Entity as MEntity;
use RZP\Models\Merchant\Detail\Constants as DConstants;
use RZP\Models\Merchant\Escalations\Actions\Handlers\CommunicationHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\DisablePaymentsHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\MerchantTagsHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\FundsOnHoldHandler;
use RZP\Models\Merchant\Escalations\Actions\Handlers\EscalationHandler;
use RZP\Notifications\Onboarding\Events;

class Constants
{
    const FRIENDBUY_SEND_PURCHASE_EVENTS_CRON_CACHE_KEY         = 'friendbuy_send_purchase_events_cron_cache_key';
    const FRIENDBUY_SEND_PURCHASE_EVENTS_CRON_FREQUENCY_IN_MINS = 60;
    const FRIENDBUY_SEND_PURCHASE_EVENTS_CRON_THRESHOLD_IN_MINS = 60;
}
