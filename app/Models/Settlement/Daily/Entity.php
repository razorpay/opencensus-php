<?php

/**
 * This entity contains aggregated settlement data.
 * It is updated during settlement creation.
 * If the update fails and the settlement creation
 * goes through successfully, this will not get updated.
 * We will need to update it manually via a route.
 */

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

    protected $entity = 'daily_settlement';

    protected $generateIdOnCreate = true;

    protected static $delimiter = '';

    protected $fillable = array(
        self::CHANNEL,
        self::AMOUNT,
        self::FEES,
        self::SETTLEMENT_COUNT,
        self::TRANSACTION_COUNT,
        self::SERVICE_TAX,
        self::INITIATED_AT,
        self::API_FEE,
        self::GATEWAY_FEE,
        self::URLS,
    );

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

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
    ];

    protected $dates = [
        self::DATE,
        self::INITIATED_AT,
        self::RECONCILED_AT,
        self::RETURNED_AT
    ];

    protected $casts = [
        self::AMOUNT            => 'int',
        self::FEES              => 'int',
        self::SERVICE_TAX       => 'int',
        self::API_FEE           => 'int',
        self::GATEWAY_FEE       => 'int',
        self::SETTLEMENT_COUNT  => 'int',
        self::TRANSACTION_COUNT => 'int',
    ];

    public function getSettlementCount()
    {
        return $this->getAttribute(self::SETTLEMENT_COUNT);
    }

    public function getUrls()
    {
        return $this->getAttribute(self::URLS);
    }

    public function addUrl($key, $url)
    {
        $urls = $this->getUrls();

        $urls[$key] = $url;

        $this->setAttribute(self::URLS, $urls);

        return $urls;
    }

    public function setUrls($urls)
    {
        return $this->setAttribute('urls', $urls);
    }

    public function setFees($fees)
    {
        $this->setAttribute(self::FEES, $fees);
    }

    public function setServiceTax($servicetax)
    {
        assertTrue($servicetax >= 0);

        $this->setAttribute(self::SERVICE_TAX, $servicetax);
    }

    protected function getUrlsAttribute()
    {
        return json_decode($this->attributes[self::URLS], true);
    }

    protected function setUrlsAttribute($urls)
    {
        $urls = json_encode($urls);

        $this->attributes[self::URLS] = $urls;
    }

    protected function setDateAttribute()
    {
        $timestamp = Carbon::today('Asia/Kolkata')->timestamp;

        $this->attributes[self::DATE] = $timestamp;
    }
}
