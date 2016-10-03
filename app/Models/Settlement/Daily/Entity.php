<?php

namespace RZP\Models\Settlement\Daily;

use RZP\Models\Base;
use Carbon\Carbon;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const DATE              = 'date';
    const CHANNEL           = 'channel';
    const AMOUNT            = 'amount';
    const FEES              = 'fees';
    const API_FEE           = 'api_fee';
    const GATEWAY_FEE       = 'gateway_fee';
    const SETTLEMENT_COUNT  = 'settlement_count';
    const TRANSACTION_COUNT = 'transaction_count';
    const SERVICE_TAX       = 'service_tax';
    const URLS              = 'urls';
    const INITIATED_AT      = 'initiated_at';
    const RECONCILED_AT     = 'reconciled_at';
    const RETURNED_AT       = 'returned_at';

    protected $table = \RZP\Constants\Table::DAILY_SETTLEMENT;

    protected $entity = 'daily_settlement';

    protected $generateIdOnCreate = true;

    protected static $delimiter = '';

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::DATE,
        self::CHANNEL,
        self::AMOUNT,
        self::FEES,
        self::API_FEE,
        self::GATEWAY_FEE,
        self::SETTLEMENT_COUNT,
        self::TRANSACTION_COUNT,
        self::SERVICE_TAX,
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

    protected $casts = array(
        self::DATE => 'int'
    );

    public static function newForToday()
    {
        $entity = new static;

        $entity->setTodayTimestamp();

        return $entity;
    }

    public function setTodayTimestamp()
    {
        $this->attributes[self::DATE] = self::getTodayTimestamp();
    }

    public function setFees($fees)
    {
        $this->setAttribute(self::FEES, $fees);
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

    public function getSettlementCount()
    {
        return $this->getAttribute(self::SETTLEMENT_COUNT);
    }

    public function setServiceTax($servicetax)
    {
        assertTrue($servicetax >= 0);

        $this->setAttribute(self::SERVICE_TAX, $servicetax);
    }

    public function getServiceTax()
    {
        return $this->getAttribute(self::SERVICE_TAX);
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function getFeesAttribute()
    {
        return (int) $this->attributes[self::FEES];
    }

    public function getServiceTaxAttribute()
    {
        return (int) $this->attributes[self::SERVICE_TAX];
    }

    public function getApiFeeAttribute()
    {
        return (int) $this->attributes[self::API_FEE];
    }

    public function getGatewayFeeAttribute()
    {
        return (int) $this->attributes[self::GATEWAY_FEE];
    }

    public function getSettlementCountAttribute()
    {
        return (int) $this->attributes[self::SETTLEMENT_COUNT];
    }

    public function getTransactionCountAttribute()
    {
        return (int) $this->attributes[self::TRANSACTION_COUNT];
    }
}
