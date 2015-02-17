<?php

namespace Models\Settlement\Daily;

use Models\Base;
use Carbon\Carbon;

class Entity extends Base\PublicEntity
{
    protected $table = \Constants\Table::DAILY_SETTLEMENT;

    protected $entity = 'daily_settlement';

    protected $fillable = array(
        'day',
        'amount',
        'urls',
        'initiated_at'
    );

    protected $genereateIdOnCreate = true;

    protected static $delimiter = '';

    protected $publicSetters = array(self::ENTITY);

    public function setTodayTimestamp()
    {
        $this->attributes['day'] = self::getTodayTimestamp();
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