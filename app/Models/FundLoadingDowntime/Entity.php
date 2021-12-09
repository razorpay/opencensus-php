<?php

namespace RZP\Models\FundLoadingDowntime;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use Base\Traits\HardDeletes;

    const TYPE                   = 'type';
    const MODE                   = 'mode';
    const ORG_ID                 = 'org_id';
    const SOURCE                 = 'source';
    const CHANNEL                = 'channel';
    const END_TIME               = 'end_time';
    const START_TIME             = 'start_time';
    const CREATED_BY             = 'created_by';
    const DOWNTIME_MESSAGE       = 'downtime_message';
    const FUND_LOADING_DOWNTIMES = 'fund_loading_downtimes';

    protected static $sign = 'fdown';

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;

    protected $entity = \RZP\Constants\Entity::FUND_LOADING_DOWNTIMES;

    protected $fillable = [
        self::TYPE,
        self::SOURCE,
        self::CHANNEL,
        self::MODE,
        self::START_TIME,
        self::END_TIME,
        self::DOWNTIME_MESSAGE,
        self::CREATED_BY,
    ];

    protected $visible = [
        self::ID,
        self::TYPE,
        self::SOURCE,
        self::CHANNEL,
        self::MODE,
        self::START_TIME,
        self::END_TIME,
        self::DOWNTIME_MESSAGE,
        self::CREATED_BY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::TYPE,
        self::SOURCE,
        self::CHANNEL,
        self::MODE,
        self::START_TIME,
        self::END_TIME,
        self::DOWNTIME_MESSAGE,
        self::CREATED_BY,
    ];

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function setType(string $type)
    {
        return $this->setAttribute(self::TYPE, $type);
    }

    public function getSource()
    {
        return $this->getAttribute(self::SOURCE);
    }

    public function setSource(string $source)
    {
        return $this->setAttribute(self::SOURCE, $source);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function setChannel(string $channel)
    {
        return $this->setAttribute(self::CHANNEL, $channel);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function setMode(string $mode)
    {
        return $this->setAttribute(self::MODE, $mode);
    }

    public function setDowntimeMessage(string $downtimeMessage)
    {
        $this->setAttribute(self::DOWNTIME_MESSAGE, $downtimeMessage);
    }

    public function getDowntimeMessage()
    {
        return $this->getAttribute(self::DOWNTIME_MESSAGE);
    }

    public function getStartTime()
    {
        return $this->getAttribute(self::START_TIME);
    }

    public function setStartTime(string $start_time)
    {
        return $this->setAttribute(self::START_TIME, $start_time);
    }

    public function getEndTime()
    {
        return $this->getAttribute(self::END_TIME);
    }

    public function setEndTime(string $end_time)
    {
        return $this->setAttribute(self::END_TIME, $end_time);
    }
}
