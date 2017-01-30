<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;

class Entity extends Merchant\Entity
{
    protected static $sign = 'acc';

    protected static $delimiter = '_';

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::EMAIL,
        self::ACTIVATED,
        self::ACTIVATED_AT,
        self::LIVE,
        self::HOLD_FUNDS,
        // self::PRICING_PLAN_ID,
        // self::WEBSITE,
        // self::CATEGORY,
        // self::CATEGORY2,
        // self::INTERNATIONAL,
        // self::FEE_BEARER,
        // self::FEE_MODEL,
        // self::BILLING_LABEL,
        // self::RECEIPT_EMAIL_ENABLED,
        // self::TRANSACTION_REPORT_EMAIL,
        // self::SETTLEMENT_SCHEDULE,
        // self::SETTLEMENT_SCHEDULE_ID,
        // self::METHODS,
        // self::CONVERT_CURRENCY,
        // self::MAX_PAYMENT_AMOUNT,
        // self::AUTO_REFUND_DELAY,
        // self::BRAND_COLOR,
        // self::RISK_RATING,
        self::CREATED_AT,
        // self::UPDATED_AT,
        // self::LOGO_URL,
        // self::ORG_ID,
        // 'groups',
        // 'admins',
     ];

    public function scopeMerchantId($query, $merchantId)
    {
        $merchantIdColumn = $this->getAttributeWithTableName(Entity::PARENT_ID);

        $query->where($merchantIdColumn, '=', $merchantId);
    }
}
