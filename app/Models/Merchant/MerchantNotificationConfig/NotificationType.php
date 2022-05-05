<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

class NotificationType
{
    const BENE_BANK_DOWNTIME    = 'bene_bank_downtime';
    const FUND_LOADING_DOWNTIME = 'fund_loading_downtime';
    const PARTNER_BANK_HEALTH   = 'partner_bank_health';

    public static function getNotificationTypes()
    {
        return [
            self::BENE_BANK_DOWNTIME,
            self::FUND_LOADING_DOWNTIME,
            self::PARTNER_BANK_HEALTH,
        ];
    }
}
