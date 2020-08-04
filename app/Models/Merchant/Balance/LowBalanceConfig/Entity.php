<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    // Traits
    use HasBalance;

    // properties
    protected $entity = EntityConstants::LOW_BALANCE_CONFIG;

    protected $table  = Table::LOW_BALANCE_CONFIG;

    protected $generateIdOnCreate = true;

    protected static $sign = 'lbc';

    // Schema Constants
    const ID                  = 'id';
    const MERCHANT_ID         = 'merchant_id';
    const BALANCE_ID          = 'balance_id';
    const THRESHOLD_AMOUNT    = 'threshold_amount';
    const NOTIFICATION_EMAILS = 'notification_emails';
    const STATUS              = 'status';
    const NOTIFY_AFTER        = 'notify_after'; // it is in hours
    const NOTIFY_AT           = 'notify_at';
    const CREATED_AT          = 'created_at';
    const UPDATED_AT          = 'updated_at';

    // Schema Constants End

    // ================================== Other Constants =========================
    const ACCOUNT_NUMBER = 'account_number';


    // ================================== END Other Constants =========================

    // defaults
    protected $defaults = [
        self::NOTIFY_AFTER => 8,
    ];

    // generators
    protected static $generators = [
        self::ID
    ];

    // fillable attributes
    protected $fillable = [
        self::THRESHOLD_AMOUNT,
        self::NOTIFICATION_EMAILS,
        self::NOTIFY_AFTER,
    ];

    // visible attributes
    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::BALANCE_ID,
        self::THRESHOLD_AMOUNT,
        self::NOTIFICATION_EMAILS,
        self::STATUS,
        self::NOTIFY_AFTER,
        self::NOTIFY_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // public attributes
    protected $public = [
        self::ID,
        self::THRESHOLD_AMOUNT,
        self::NOTIFICATION_EMAILS,
        self::STATUS,
        self::NOTIFY_AFTER,
        self::ACCOUNT_NUMBER,
    ];

    // public setters
    protected $publicSetters = [
        self::ACCOUNT_NUMBER,
    ];

    // ============================= GETTERS ===============================

    public function getThresholdAmount()
    {
        return $this->getAttribute(self::THRESHOLD_AMOUNT);
    }

    public function getNotificationEmails()
    {
        return $this->getAttribute(self::NOTIFICATION_EMAILS);
    }

    public function getNotifyAfter()
    {
        // it is in hours
        return $this->getAttribute(self::NOTIFY_AFTER);
    }

    public function getNotifyAt()
    {
        return $this->getAttribute(self::NOTIFY_AT);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    // ============================= END GETTERS ===========================

    // ============================= SETTERS ===========================
    // TODO:add setters
    // ============================= END SETTERS ===========================

    // ============================= RELATIONS =============================

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    // ============================= END RELATIONS ===========================

    // ============================= PUBLIC SETTERS ==========================

    public function setPublicAccountNumberAttribute(array & $attributes)
    {
        $attributes[self::ACCOUNT_NUMBER] = $this->getAccountNumberAttribute();
    }

    // ============================= END PUBLIC SETTERS =======================
}
