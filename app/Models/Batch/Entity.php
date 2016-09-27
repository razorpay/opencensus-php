<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const UPLOAD_FILE_URL           = 'uploaded_file_url';
    const DOWNLOAD_FILE_URL         = 'download_file_url';
    const STATUS                    = 'status';
    const TOTAL_COUNT               = 'total_count';
    const SUCCESS_COUNT             = 'success_count';
    const FAILURE_COUNT             = 'failure_count';
    const ATTEMPTS                  = 'attempts';
    const AMOUNT                    = 'amount';
    const PROCESSED_AMOUNT          = 'processed_amount';
    const COMMENT                   = 'comment';
    const PROCESSED_AT              = 'processed_at';
    const TYPE                      = 'type';

    const FILE_URL_LENGTH           = 100;
    const STATUS_LENGTH             = 20;
    const FILE                      ='file';

    protected $table = \RZP\Constants\Table::BATCH;

    protected static $sign = 'batch';

    protected $entity = 'batch';

    protected $generateIdOnCreate = true;

    protected static $generators = array(self::ID);

    protected $fillable = array(
        self::UPLOAD_FILE_URL,
        self::STATUS,
        self::TOTAL_COUNT,
        self::ATTEMPTS,
        self::TYPE,
    );

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::UPLOAD_FILE_URL,
        self::DOWNLOAD_FILE_URL,
        self::STATUS,
        self::AMOUNT,
        self::PROCESSED_AMOUNT,
        self::TOTAL_COUNT,
        self::SUCCESS_COUNT,
        self::FAILURE_COUNT,
        self::PROCESSED_AT,
        self::ATTEMPTS,
        self::TYPE,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::TYPE,
        self::STATUS,
        self::TOTAL_COUNT,
        self::SUCCESS_COUNT,
        self::FAILURE_COUNT,
        self::ATTEMPTS,
        self::AMOUNT,
        self::PROCESSED_AMOUNT,
        self::PROCESSED_AT,
        self::CREATED_AT,
    );

    protected $defaults = array(
        self::ATTEMPTS                       => 0,
        self::STATUS                         => Status::CREATED,
        self::DOWNLOAD_FILE_URL              => null,
        self::SUCCESS_COUNT                  => null,
        self::FAILURE_COUNT                  => null,
        self::AMOUNT                         => null,
        self::PROCESSED_AMOUNT               => 0,
        self::COMMENT                        => null,
        self::PROCESSED_AT                   => null,
    );

    protected $casts = array(
        self::TOTAL_COUNT                    => 'int',
        self::SUCCESS_COUNT                  => 'int',
        self::FAILURE_COUNT                  => 'int',
        self::AMOUNT                         => 'int',
        self::PROCESSED_AMOUNT               => 'int',
        self::ATTEMPTS                       => 'int',
    );

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getProcessedAmount()
    {
        return $this->getAttribute(self::PROCESSED_AMOUNT);
    }

    public function getUploadFileUrl()
    {
        return $this->getAttribute(self::UPLOAD_FILE_URL);
    }

    public function getDownloadFileUrl()
    {
        return $this->getAttribute(self::DOWNLOAD_FILE_URL);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getTotalCount()
    {
        return $this->getAttribute(self::TOTAL_COUNT);
    }

    public function getSuccessCount()
    {
        return $this->getAttribute(self::SUCCESS_COUNT);
    }

    public function getFailureCount()
    {
        return $this->getAttribute(self::FAILURE_COUNT);
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getProcessedAt()
    {
        return $this->getAttribute(self::PROCESSED_AT);
    }

    public function setUploadFileUrl($url)
    {
        $this->setAttribute(self::UPLOAD_FILE_URL, $url);
    }

    public function setDownloadFileUrl($url)
    {
        $this->setAttribute(self::DOWNLOAD_FILE_URL, $url);
    }

    public function setSuccessCount($count)
    {
        $this->setAttribute(self::SUCCESS_COUNT, $count);
    }

    public function setFailureCount($count)
    {
        $this->setAttribute(self::FAILURE_COUNT, $count);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setProcessedAmount($amount)
    {
        $this->setAttribute(self::PROCESSED_AMOUNT, $amount);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setAttempts($attempts)
    {
        $this->setAttribute(self::ATTEMPTS, $attempts);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setProcessedAt($processedAt)
    {
        $this->setAttribute(self::PROCESSED_AT, $processedAt);
    }

    public function setTotalCount($totalCount)
    {
        $this->setAttribute(self::TOTAL_COUNT, $totalCount);
    }
}
