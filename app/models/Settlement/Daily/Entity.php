<?php

namespace Models\Settlement\Daily;

use Models\Base;
use Carbon\Carbon;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const DATE              = 'date';
    const CHANNEL           = 'channel';
    const AMOUNT            = 'amount';
    const URLS              = 'urls';
    const INITIATED_AT      = 'initiated_at';
    const RECONCILED_AT     = 'reconciled_at';
    const RETURNED_AT       = 'returned_at';

    protected $table = \Constants\Table::DAILY_SETTLEMENT;

    protected $entity = 'daily_settlement';

    protected $genereateIdOnCreate = true;

    protected static $delimiter = '';

    protected $public = array(
        self::ID,
        self::DATE,
        self::CHANNEL,
        self::AMOUNT,
        self::URLS,
        self::INITIATED_AT,
        self::RECONCILED_AT,
        self::RETURNED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    protected $publicSetters = array(self::ENTITY);

    protected $dates = array(
        self::DATE,
        self::INITIATED_AT,
        self::RECONCILED_AT,
        self::RETURNED_AT);

    public function setTodayTimestamp()
    {
        $this->attributes[self::DATE] = self::getTodayTimestamp();
    }

    public static function getTodayTimestamp()
    {
        return Carbon::today('Asia/Kolkata')->timestamp;
    }

    public static function getTimestampForDate($day, $month, $year)
    {
        return Carbon::createFromDate($year, $month, $day, 'Asia/Kolkata');
    }

    public function getUrlsAttribute()
    {
        return json_decode($this->attributes['urls'], true);
    }

    public function setUrlsAttribute($urls)
    {
        $urls = json_encode($urls);

        $this->attributes['urls'] = $urls;
    }

    public function getUrls()
    {
        return $this->getAttribute('urls');
    }

    public function setUrls($urls)
    {
        return $this->setAttribute('urls', $urls);
    }

    public function addUrl($key, $url)
    {
        $urls = $this->getUrls();

        $urls[$key] = $url;

        $this->setAttribute('urls', $urls);

        return $urls;
    }
}