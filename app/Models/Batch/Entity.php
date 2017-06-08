<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\FileStore;


class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';

    /**
     * @deprecated
     *
     * Previously we didn't use UFH and stored the file key names in
     * following two attributes.
     */
    const UPLOAD_FILE_URL           = 'upload_file_url';
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
    const FILE                      = 'file';

    protected static $sign = 'batch';

    protected $entity = 'batch';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
    ];

    protected $fillable = [
        self::TYPE,
    ];

    protected $public = [
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
    ];

    protected $defaults = [
        self::ATTEMPTS          => 0,
        self::STATUS            => Status::CREATED,
        self::DOWNLOAD_FILE_URL => null,
        self::SUCCESS_COUNT     => null,
        self::FAILURE_COUNT     => null,
        self::AMOUNT            => null,
        self::PROCESSED_AMOUNT  => 0,
        self::COMMENT           => null,
        self::PROCESSED_AT      => null,
    ];

    protected $casts = [
        self::TOTAL_COUNT      => 'int',
        self::SUCCESS_COUNT    => 'int',
        self::FAILURE_COUNT    => 'int',
        self::AMOUNT           => 'int',
        self::PROCESSED_AMOUNT => 'int',
        self::ATTEMPTS         => 'int',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function files()
    {
        return $this->morphMany('RZP\Models\FileStore\Entity', 'entity');
    }

    /**
     * The file which user uploads when creating the batch entity.
     *
     * @return FileStore\Entity
     */
    public function inputFile()
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, FileStore\Type::BATCH_INPUT)
                    ->latest()
                    ->first();
    }

    /**
     * The file which our processor creates finally with processed results.
     * This is available to user to download.
     *
     * @return FileStore\Entity
     */
    public function outputFile()
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, FileStore\Type::BATCH_OUTPUT)
                    ->latest()
                    ->first();
    }

    // ----------------------- Getters ---------------------------------------------
    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getProcessedAmount()
    {
        return $this->getAttribute(self::PROCESSED_AMOUNT);
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

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    /**
     * Returns prefix for the file. Prefix are mostly used to get a folder like
     * structure on S3. We have different prefix for created and output batch
     * files, for convenience.
     *
     * @param string|null $status
     *
     * @return string
     */
    public function getFilePrefix(string $status = null): string
    {
        $status = $status ?: $this->getStatus();

        if ($status === Status::CREATED)
        {
            return 'batch/upload/';
        }
        else
        {
            return 'batch/download/';
        }
    }

    /**
     * Returns key for file. Id is being used for key.
     *
     * @return string
     */
    public function getFileKey(): string
    {
        return $this->getId();
    }

    public function getFileKeyWithExt(): string
    {
        return $this->getFileKey() . '.' . FileStore\Format::XLSX;
    }

    /**
     * Get local save directory.
     *
     * Used in Processor:
     * - To move temp php request to this location and pass the same to UFH
     * - To create output file at proper location.
     *
     * @param string|null $status
     *
     * @return string
     */
    public function getLocalSaveDir(string $status = null): string
    {
        return storage_path('files/filestore') . '/' . $this->getFilePrefix($status);
    }

    public function getLocalSavePath(string $status = null)
    {
        return $this->getLocalSaveDir($status) . $this->getFileKeyWithExt();
    }

    // ----------------------- Setters ---------------------------------------------

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

    public function incrementAttempts()
    {
        $this->increment(self::ATTEMPTS);
    }

    public function isProcessed()
    {
        return ($this->getStatus() === Status::PROCESSED);
    }
}
