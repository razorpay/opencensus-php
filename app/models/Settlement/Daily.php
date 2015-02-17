<?php

namespace Models\Settlement;

use Models\Base;
use Carbon\Carbon;

class Daily extends Base\PublicEntity
{
    protected $table = \Constants\Table::DAILY_SETTLEMENT;

    public $primaryKey = 'day';

    protected $fillable = array(
        'day',
        'amount',
        'urls',
        'initiated_at'
    );

    public function setTodayTimestamp()
    {s(self::getTodayTimestamp());
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
}