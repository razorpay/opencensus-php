<?php

namespace RZP\Models\FundTransfer\Batch;

use RZP\Models\Base;
use RZP\Models\FileStore\Entity as FileStore;
use Carbon\Carbon;
use RZP\Constants\Timezone;

/**
 * This entity contains aggregated settlement/payout data.
 * It is updated during settlement/payout creation.
 * If the update fails and the creation
 * goes through successfully, this will not get updated.
 * We will need to update it manually via a route.
 */
class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const DATE                  = 'date';
    const TYPE                  = 'type';
    const CHANNEL               = 'channel';
    const AMOUNT                = 'amount';
    const PROCESSED_AMOUNT      = 'processed_amount';
    const FEES                  = 'fees';
    const API_FEE               = 'api_fee';
    const GATEWAY_FEE           = 'gateway_fee';
    const TOTAL_COUNT           = 'total_count';
    const PROCESSED_COUNT       = 'processed_count';
    const TRANSACTION_COUNT     = 'transaction_count';
    const TAX                   = 'tax';
    const URLS                  = 'urls';
    const INITIATED_AT          = 'initiated_at';
    const TXT_FILE_ID           = 'txt_file_id';
    const EXCEL_FILE_ID         = 'excel_file_id';
    const RECONCILED_AT         = 'reconciled_at';
    const RETURNED_AT           = 'returned_at';

    protected $entity = 'batch_fund_transfer';

    protected $generateIdOnCreate = true;

    protected static $delimiter = '';

    protected $fillable = [
        self::TYPE,
        self::CHANNEL,
        self::AMOUNT,
        self::PROCESSED_AMOUNT,
        self::FEES,
        self::TOTAL_COUNT,
        self::PROCESSED_COUNT,
        self::TRANSACTION_COUNT,
        self::TAX,
        self::INITIATED_AT,
        self::API_FEE,
        self::GATEWAY_FEE,
        self::URLS,
        self::TXT_FILE_ID,
        self::EXCEL_FILE_ID,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::TYPE,
        self::DATE,
        self::CHANNEL,
        self::AMOUNT,
        self::PROCESSED_AMOUNT,
        self::FEES,
        self::API_FEE,
        self::GATEWAY_FEE,
        self::TOTAL_COUNT,
        self::PROCESSED_COUNT,
        self::TRANSACTION_COUNT,
        self::TAX,
        self::URLS,
        self::INITIATED_AT,
        self::TXT_FILE_ID,
        self::EXCEL_FILE_ID,
        self::RECONCILED_AT,
        self::RETURNED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
    ];

    protected static $generators = [
        self::DATE
    ];

    protected $dates = [
        self::DATE,
        self::INITIATED_AT,
        self::RECONCILED_AT,
        self::RETURNED_AT
    ];

    protected $casts = [
        self::AMOUNT                => 'int',
        self::PROCESSED_AMOUNT      => 'int',
        self::FEES                  => 'int',
        self::DATE                  => 'int',
        self::TAX                   => 'int',
        self::API_FEE               => 'int',
        self::GATEWAY_FEE           => 'int',
        self::INITIATED_AT          => 'int',
        self::TOTAL_COUNT           => 'int',
        self::PROCESSED_COUNT       => 'int',
        self::TRANSACTION_COUNT     => 'int',
    ];

    protected $defaults = [
        self::PROCESSED_AMOUNT   => 0,
        self::PROCESSED_COUNT    => 0,
    ];

    protected function generateDate($input)
    {
        $timestamp = Carbon::today(Timezone::IST)->getTimestamp();

        $this->setAttribute(self::DATE, $timestamp);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getTotalCount()
    {
        return $this->getAttribute(self::TOTAL_COUNT);
    }

    public function getTransactionCount()
    {
        return $this->getAttribute(self::TRANSACTION_COUNT);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
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

    public function incrementAmount($value)
    {
        $this->increment(self::AMOUNT, $value);
    }

    public function incrementFees($value)
    {
        $this->increment(self::FEES, $value);
    }

    public function incrementTax($value)
    {
        $this->increment(self::TAX, $value);
    }

    public function incrementTotalCount()
    {
        $this->increment(self::TOTAL_COUNT);
    }

    public function incrementTransactionCount($value)
    {
        $this->increment(self::TRANSACTION_COUNT, $value);
    }

    public function setAmount($value)
    {
        $this->setAttribute(self::AMOUNT, $value);
    }

    public function setTotalCount($value)
    {
        $this->setAttribute(self::TOTAL_COUNT, $value);
    }

    public function setTransactionCount($value)
    {
        $this->setAttribute(self::TRANSACTION_COUNT, $value);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setUrls($urls)
    {
        return $this->setAttribute('urls', $urls);
    }

    public function setTxtFileId($id)
    {
        $this->setAttribute(self::TXT_FILE_ID, $id);
    }

    public function setExcelFileId($id)
    {
        $this->setAttribute(self::EXCEL_FILE_ID, $id);
    }

    public function setFees($fees)
    {
        $this->setAttribute(self::FEES, $fees);
    }

    public function setTax($tax)
    {
        assertTrue($tax >= 0);

        $this->setAttribute(self::TAX, $tax);
    }

    public function setProcessedCount($count)
    {
        $this->setAttribute(self::PROCESSED_COUNT, $count);
    }

    public function setProcessedAmount($amount)
    {
        $this->setAttribute(self::PROCESSED_AMOUNT, $amount);
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

    protected function setTxtFileIdAttribute($id)
    {
        if ($id === null)
        {
            return;
        }

        $this->attributes[self::TXT_FILE_ID] = FileStore::verifyIdAndSilentlyStripSign($id);
    }

    protected function setExcelFileIdAttribute($id)
    {
        if ($id === null)
        {
            return;
        }

        $this->attributes[self::EXCEL_FILE_ID] = FileStore::verifyIdAndSilentlyStripSign($id);
    }
}
