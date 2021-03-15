<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Base\Traits\HardDeletes;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    // Traits
    use HasBalance;
    use HardDeletes;

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
    const NOTIFY_AFTER        = 'notify_after'; // it is in seconds
    const NOTIFY_AT           = 'notify_at';
    const CREATED_AT          = 'created_at';
    const UPDATED_AT          = 'updated_at';
    const TYPE                = 'type';
    const AUTOLOAD_AMOUNT     = 'autoload_amount';

    // Schema Constants End

    // ================================== Other Constants =========================
    const ACCOUNT_NUMBER = 'account_number';

    // Types of Low Balance Config Actions
    const NOTIFICATION      = 'notification';
    const AUTOLOAD_BALANCE  = 'autoload_balance';

    const AUTOLOAD_BALANCE_ADJUSTMENT_DESCRIPTION = 'Autoload balance via low balance config';

    const ALLOWED_CONFIG_TYPES = [
        self::AUTOLOAD_BALANCE,
        self::NOTIFICATION,
    ];

    const LOW_BALANCE_TYPE_SERIALIZER = [
        self::NOTIFICATION      => 1,
        self::AUTOLOAD_BALANCE  => 2,
    ];

    const LOW_BALANCE_TYPE_DESERIALIZER = [
        1 => self::NOTIFICATION,
        2 => self::AUTOLOAD_BALANCE,
    ];

    // ================================== END Other Constants =========================

    // defaults
    protected $defaults = [
        self::NOTIFY_AT             => 0,
        self::NOTIFY_AFTER          => 28800, // 8 hrs
        self::STATUS                => Status::ENABLED,
        self::TYPE                  => Entity::NOTIFICATION,
        self::AUTOLOAD_AMOUNT       => 0,
        self::NOTIFICATION_EMAILS   => ''
    ];

    // generators
    protected static $generators = [
        self::ID,
    ];

    // fillable attributes
    protected $fillable = [
        self::THRESHOLD_AMOUNT,
        self::NOTIFICATION_EMAILS,
        self::NOTIFY_AFTER,
        self::AUTOLOAD_AMOUNT,
        self::TYPE,
    ];

    // visible attributes
    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::BALANCE_ID,
        self::TYPE,
        self::THRESHOLD_AMOUNT,
        self::AUTOLOAD_AMOUNT,
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
        self::CREATED_AT,
        self::TYPE,
        self::AUTOLOAD_AMOUNT,
    ];

    // public setters
    protected $publicSetters = [
        self::ID,
        self::ACCOUNT_NUMBER,
        self::NOTIFICATION_EMAILS,
        self::TYPE,
        self::AUTOLOAD_AMOUNT,
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

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getAutoloadAmount()
    {
        return $this->getAttribute(self::AUTOLOAD_AMOUNT);
    }

    // ============================= END GETTERS ===========================

    // ============================= SETTERS ===========================
    // TODO:add setters

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setNotifyAt(int $notifyAt)
    {
        $this->setAttribute(self::NOTIFY_AT, $notifyAt);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    // ============================= END SETTERS ===========================

    // ============================= MUTATORS ==============================

    public function setTypeAttribute($type)
    {
        $type = self::LOW_BALANCE_TYPE_SERIALIZER[$type];

        $this->attributes[self::TYPE] = $type;
    }

    public function getTypeAttribute()
    {
        $type = $this->attributes[self::TYPE];

        return self::LOW_BALANCE_TYPE_DESERIALIZER[$type];
    }

    // ============================= END MUTATORS ==========================

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

    public function setPublicNotificationEmailsAttribute(array & $attributes)
    {
        $attributes[self::NOTIFICATION_EMAILS] = explode(',', $this->getNotificationEmails());
    }

    public function setPublicTypeAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isAdminAuth() === false)
        {
            unset($attributes[self::TYPE]);

            return;
        }
    }

    public function setPublicAutoloadAmountAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isAdminAuth() === false)
        {
            unset($attributes[self::AUTOLOAD_AMOUNT]);

            return;
        }
    }
    // ============================= END PUBLIC SETTERS =======================
}
