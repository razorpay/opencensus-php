<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Base\Traits\HardDeletes;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    // Trait
    use HardDeletes;

    // properties
    protected        $entity             = EntityConstants::MERCHANT_NOTIFICATION_CONFIG;

    protected        $table              = Table::MERCHANT_NOTIFICATION_CONFIG;

    protected        $generateIdOnCreate = true;

    protected static $sign               = 'mnc';

    // Schema Constants
    const ID                          = 'id';
    const MERCHANT_ID                 = 'merchant_id';
    const CONFIG_STATUS               = 'config_status';
    const NOTIFICATION_TYPE           = 'notification_type';
    const UPPER_THRESHOLD             = 'upper_threshold';
    const LOWER_THRESHOLD             = 'lower_threshold';
    const MODE                        = 'mode';
    const NOTIFY_AFTER                = 'notify_after'; // it is in seconds
    const NOTIFY_AT                   = 'notify_at';
    const NOTIFICATION_EMAILS         = 'notification_emails';
    const NOTIFICATION_MOBILE_NUMBERS = 'notification_mobile_numbers';
    const LAST_ENABLED_AT             = 'last_enabled_at';
    const LAST_DISABLED_AT            = 'last_disabled_at';
    const CREATED_AT                  = 'created_at';
    const UPDATED_AT                  = 'updated_at';
    // End of Schema Constants

    // Default values
    protected $defaults = [
        self::NOTIFY_AT         => 0,
        self::NOTIFY_AFTER      => 900, // 15 minutes*/
        self::CONFIG_STATUS     => Status::ENABLED,
        self::MODE              => 'ALL',
        self::NOTIFICATION_TYPE => NotificationType::BENE_BANK_DOWNTIME,
    ];

    // Generators
    protected static $generators = [
        self::ID,
    ];

    // Fillable attributes
    protected $fillable = [
        self::UPPER_THRESHOLD,
        self::LOWER_THRESHOLD,
        self::NOTIFICATION_TYPE,
        self::NOTIFICATION_EMAILS,
        self::NOTIFICATION_MOBILE_NUMBERS,
        self::NOTIFY_AFTER,
        self::MODE,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CONFIG_STATUS,
        self::NOTIFICATION_TYPE,
        self::UPPER_THRESHOLD,
        self::LOWER_THRESHOLD,
        self::MODE,
        self::NOTIFY_AFTER,
        self::NOTIFY_AT,
        self::NOTIFICATION_EMAILS,
        self::NOTIFICATION_MOBILE_NUMBERS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::UPPER_THRESHOLD,
        self::LOWER_THRESHOLD,
        self::NOTIFICATION_TYPE,
        self::NOTIFICATION_EMAILS,
        self::NOTIFICATION_MOBILE_NUMBERS,
        self::CONFIG_STATUS,
        self::MODE,
        self::NOTIFY_AFTER,
        self::CREATED_AT,
    ];

    // Public Setters
    protected $publicSetters = [
        self::ID,
        self::NOTIFICATION_MOBILE_NUMBERS,
        self::NOTIFICATION_EMAILS,
    ];

    // Getters
    public function getUpperThreshold()
    {
        return $this->getAttribute(self::UPPER_THRESHOLD);
    }

    public function getLowerThreshold()
    {
        return $this->getAttribute(self::LOWER_THRESHOLD);
    }

    public function getNotificationEmails()
    {
        return $this->getAttribute(self::NOTIFICATION_EMAILS);
    }

    public function getNotificationMobileNumbers()
    {
        return $this->getAttribute(self::NOTIFICATION_MOBILE_NUMBERS);
    }

    public function getNotifyAfter()
    {
        return $this->getAttribute(self::NOTIFY_AFTER);
    }

    public function getNotifyAt()
    {
        return $this->getAttribute(self::NOTIFY_AT);
    }

    public function getConfigStatus()
    {
        return $this->getAttribute(self::CONFIG_STATUS);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function getLastEnabledAt()
    {
        return $this->getAttribute(self::LAST_ENABLED_AT);
    }

    public function getLastDisabledAt()
    {
        return $this->getAttribute(self::LAST_DISABLED_AT);
    }

    public function getNotificationType()
    {
        return $this->getAttribute(self::NOTIFICATION_TYPE);
    }
    // End of Getters

    // Setters
    public function setConfigStatus(string $status)
    {
        $this->setAttribute(self::CONFIG_STATUS, $status);
        if($status === Status::ENABLED)
        {
            $this->setLastEnabledAt(now()->timestamp);
        }
        if($status === Status::DISABLED)
        {
            $this->setLastDisabledAt(now()->timestamp);
        }
    }

    public function setNotifyAt(int $notifyAt)
    {
        $this->setAttribute(self::NOTIFY_AT, $notifyAt);
    }

    public function setNotifyAfter(int $notifyAfter)
    {
        $this->setAttribute(self::NOTIFY_AFTER, $notifyAfter);
    }

    protected function setLastEnabledAt(int $time)
    {
        return $this->setAttribute(self::LAST_ENABLED_AT, $time);
    }

    protected function setLastDisabledAt(int $time)
    {
        return $this->setAttribute(self::LAST_DISABLED_AT, $time);
    }
    // End of Setters

    // Relations
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }
    // End of Relations

    // Mutators and Public Setters
    public function setPublicNotificationEmailsAttribute(array &$attributes)
    {
        $attributes[self::NOTIFICATION_EMAILS] = explode(',', $this->getNotificationEmails());
    }

    public function setPublicNotificationMobileNumbersAttribute(array &$attributes)
    {
        $attributes[self::NOTIFICATION_MOBILE_NUMBERS] = explode(',', $this->getNotificationMobileNumbers());
    }
    // End of Mutators and Public Setters
}
