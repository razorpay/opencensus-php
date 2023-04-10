<?php

namespace App\Merchant\Notifications;

use App\MerchantDetails;

class Constants
{
    const NOTIFICATIONS = [];

    const SPLITZ_NOTIFICATION = [];

    //insert data in data field, that is dynamically loaded based on the sub-campaign
    const ANNOUNCEMENT_ID_TO_SUB_CAMPAIGN_DETAIL_MAPPING = [];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }

    public static function getSplitzBasedNotifications(): array
    {
        return self::SPLITZ_NOTIFICATION;
    }

    public static function getAnnouncementToSubCampaignDetailsMapping(): array
    {
        return self::ANNOUNCEMENT_ID_TO_SUB_CAMPAIGN_DETAIL_MAPPING;
    }
}
