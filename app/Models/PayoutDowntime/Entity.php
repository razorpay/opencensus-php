<?php

namespace RZP\Models\PayoutDowntime;

use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const STATUS                  = 'status';

    const CHANNEL                 = 'channel';

    const MODE                    = 'mode';

    const START_TIME              = 'start_time';

    const END_TIME                = 'end_time';

    //the message will be shown on merchant dashboard
    const DOWNTIME_MESSAGE        = 'downtime_message';
    const UPTIME_MESSAGE          = 'uptime_message';

    //If the downtime status is Enabled and support team opts for sending out an email then Yes will be filled else No.
    const ENABLED_EMAIL_OPTION    = 'enabled_email_option';

    //If the downtime status is Disabled and support team opts for sending out an email then yes will be filled else No.
    const DISABLED_EMAIL_OPTION   = 'disabled_email_option';

    //If the downtime status is Enabled and support team opts for sending out an email.
    // First will be in Processing state before ProcessDowntimeNotification job get dispatched.
    // Later to Sent/Failed depending upon job
    const ENABLED_EMAIL_STATUS    = 'enabled_email_status';

    //If the downtime status is Disabled and support team opts for sending out an email.
    // First will be in Processing state before ProcessDowntimeNotification job get dispatched.
    // Later to Sent/Failed depending upon job
    const DISABLED_EMAIL_STATUS   = 'disabled_email_status';

    const CREATED_BY              = 'created_by';

    const PAYOUT_DOWNTIME         = 'payout_downtime';

    const ORG_ID                  = 'org_id';

    const ADMIN_ID                = 'admin_id';

    protected static $sign         = 'pdown';

    protected  $primaryKey         = self::ID;

    protected  $generateIdOnCreate = true;

    protected  $entity             = Table::PAYOUT_DOWNTIMES;

    protected  $fillable           = [
        self::STATUS,
        self::CHANNEL,
        self::MODE,
        self::START_TIME,
        self::END_TIME,
        self::DOWNTIME_MESSAGE,
        self::UPTIME_MESSAGE,
        self::ENABLED_EMAIL_OPTION,
        self::DISABLED_EMAIL_OPTION,
        self::ENABLED_EMAIL_STATUS,
        self::DISABLED_EMAIL_STATUS,
        self::CREATED_BY,
    ];

    protected $visible             = [
        self::ID,
        self::STATUS,
        self::CHANNEL,
        self::MODE,
        self::START_TIME,
        self::END_TIME,
        self::DOWNTIME_MESSAGE,
        self::UPTIME_MESSAGE,
        self::ENABLED_EMAIL_OPTION,
        self::DISABLED_EMAIL_OPTION,
        self::ENABLED_EMAIL_STATUS,
        self::DISABLED_EMAIL_STATUS,
        self::CREATED_BY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public             = [
        self::ID,
        self::STATUS,
        self::CHANNEL,
        self::MODE,
        self::START_TIME,
        self::END_TIME,
        self::DOWNTIME_MESSAGE,
        self::UPTIME_MESSAGE,
        self::ENABLED_EMAIL_OPTION,
        self::DISABLED_EMAIL_OPTION,
        self::ENABLED_EMAIL_STATUS,
        self::DISABLED_EMAIL_STATUS,
        self::CREATED_BY,
    ];

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function setDowntimeMessage(string $downtimeMessage)
    {
         $this->setAttribute(self::DOWNTIME_MESSAGE, $downtimeMessage);
    }

    public function getDowntimeMessage()
    {
        return $this->getAttribute(self::DOWNTIME_MESSAGE);
    }

    public function setUptimeMessage(string $uptimeMessage)
    {
         $this->setAttribute(self::UPTIME_MESSAGE, $uptimeMessage);
    }

    public function getUpTimeMessage()
    {
        return $this->getAttribute(self::UPTIME_MESSAGE);
    }

    public function setEnabledEmailOption(string $enabledEmailOption)
    {
        $this->setAttribute(self::ENABLED_EMAIL_OPTION, $enabledEmailOption);
    }

    public function getEnabledEmailOption()
    {
        return $this->getAttribute(self::ENABLED_EMAIL_OPTION);
    }

    public function setDisabledEmailOption(string $disabledEmailOption)
    {
        $this->setAttribute(self::DISABLED_EMAIL_OPTION, $disabledEmailOption);
    }

    public function getDisabledEmailOption()
    {
        return $this->getAttribute(self::DISABLED_EMAIL_OPTION);
    }

    public function setEnabledEmailStatus(string $enabledEmailStatus)
    {
        $this->setAttribute(self::ENABLED_EMAIL_STATUS, $enabledEmailStatus);
    }

    public function setDisabledEmailStatus(string $disabledEmailStatus)
    {
        $this->setAttribute(self::DISABLED_EMAIL_STATUS, $disabledEmailStatus);
    }

    public function getStartTime()
    {
        return $this->getAttribute(self::START_TIME);
    }

    public function getEndTime()
    {
        return $this->getAttribute(self::END_TIME);
    }
}
