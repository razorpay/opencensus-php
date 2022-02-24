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
    const NOTIFICATION_EMAILS         = 'notification_emails';
    const NOTIFICATION_MOBILE_NUMBERS = 'notification_mobile_numbers';
    const CREATED_AT                  = 'created_at';
    const UPDATED_AT                  = 'updated_at';
    // End of Schema Constants

    // Default values
    protected $defaults = [
        self::CONFIG_STATUS     => Status::ENABLED,
        self::NOTIFICATION_TYPE => NotificationType::BENE_BANK_DOWNTIME,
    ];

    // Generators
    protected static $generators = [
        self::ID,
    ];

    // Fillable attributes
    protected $fillable = [
        self::NOTIFICATION_TYPE,
        self::NOTIFICATION_EMAILS,
        self::NOTIFICATION_MOBILE_NUMBERS,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CONFIG_STATUS,
        self::NOTIFICATION_TYPE,
        self::NOTIFICATION_EMAILS,
        self::NOTIFICATION_MOBILE_NUMBERS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::NOTIFICATION_TYPE,
        self::NOTIFICATION_EMAILS,
        self::NOTIFICATION_MOBILE_NUMBERS,
        self::CONFIG_STATUS,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    // Public Setters
    protected $publicSetters = [
        self::ID,
        self::NOTIFICATION_MOBILE_NUMBERS,
        self::NOTIFICATION_EMAILS,
    ];

    // Getters

    public function getNotificationEmails()
    {
        return $this->getAttribute(self::NOTIFICATION_EMAILS);
    }

    public function getNotificationMobileNumbers()
    {
        return $this->getAttribute(self::NOTIFICATION_MOBILE_NUMBERS);
    }

    public function getConfigStatus()
    {
        return $this->getAttribute(self::CONFIG_STATUS);
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
        $emails = $this->getNotificationEmails();

        if (empty($emails) === true)
        {
            $attributes[self::NOTIFICATION_EMAILS] = [];
        }
        else
        {
            $attributes[self::NOTIFICATION_EMAILS] = explode(',', $emails);
        }
    }

    public function setPublicNotificationMobileNumbersAttribute(array &$attributes)
    {
        $numbers = $this->getNotificationMobileNumbers();

        if (empty($numbers) === true)
        {
            $attributes[self::NOTIFICATION_MOBILE_NUMBERS] = [];
        }
        else
        {
            $attributes[self::NOTIFICATION_MOBILE_NUMBERS] = explode(',', $numbers);
        }
    }
    // End of Mutators and Public Setters
}
