<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\FileStore;

class Entity extends Base\PublicEntity
{
    /**
     * @deprecated
     *
     * Previously we didn't use UFH and stored the file key names in
     * following two attributes.
     */
    const UPLOAD_FILE_URL           = 'upload_file_url';
    const DOWNLOAD_FILE_URL         = 'download_file_url';

    const STATUS                    = 'status';
    const PROCESSING                = 'processing';
    const TOTAL_COUNT               = 'total_count';
    const SUCCESS_COUNT             = 'success_count';
    const FAILURE_COUNT             = 'failure_count';
    const ATTEMPTS                  = 'attempts';
    const AMOUNT                    = 'amount';
    const PROCESSED_AMOUNT          = 'processed_amount';
    const COMMENT                   = 'comment';
    const PROCESSED_AT              = 'processed_at';
    const TYPE                      = 'type';
    const SUB_TYPE                  = 'sub_type';
    const GATEWAY                   = 'gateway';
    const FAILURE_REASON            = 'failure_reason';

    /**
     * Constants used in migration file.
     */
    const FILE_URL_LENGTH           = 100;
    const STATUS_LENGTH             = 20;

    /**
     * Additional constants
     */
    const FILE                      = 'file';
    const URL                       = 'url';
    const INPUT_FILE_PREFIX         = 'batch/upload/';
    const OUTPUT_FILE_PREFIX        = 'batch/download/';

    const INPUT_FILE                = 'input_file';
    const OUTPUT_FILE               = 'output_file';

    protected static $sign = 'batch';

    protected $entity = 'batch';

    protected $generateIdOnCreate = true;

    /**
     * Generators
     * - Id generation is required before save as it gets
     *   used in associations.
     *
     * @var array
     */
    protected static $generators = [
        self::ID,
    ];

    protected $fillable = [
        self::TYPE,
        self::GATEWAY,
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
        self::ATTEMPTS            => 0,
        self::STATUS              => Status::CREATED,
        self::PROCESSING          => 0,
        self::DOWNLOAD_FILE_URL   => null,
        self::TOTAL_COUNT         => 0,
        self::SUCCESS_COUNT       => null,
        self::FAILURE_COUNT       => null,
        self::AMOUNT              => null,
        self::PROCESSED_AMOUNT    => 0,
        self::GATEWAY             => null,
        self::FAILURE_REASON      => null,
        self::SUB_TYPE            => null,
        self::COMMENT             => null,
        self::PROCESSED_AT        => null,
    ];

    protected $casts = [
        self::TOTAL_COUNT      => 'int',
        self::SUCCESS_COUNT    => 'int',
        self::FAILURE_COUNT    => 'int',
        self::AMOUNT           => 'int',
        self::PROCESSED_AMOUNT => 'int',
        self::ATTEMPTS         => 'int',
        self::PROCESSING       => 'bool',
    ];

    // Relations

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
        //
        // For files of reconciliation type batches, we use a different UFH type
        // (hence S3 locations) for reasons.
        //
        $ufhType = ($this->isReconciliationType() === true) ?
                        FileStore\Type::BATCH_RECON_INPUT :
                        FileStore\Type::BATCH_INPUT;

        return $this->files()
                    ->where(FileStore\Entity::TYPE, $ufhType)
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

    // ----------------------- Getters -------------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function isProcessed(): bool
    {
        return ($this->getStatus() === Status::PROCESSED);
    }

    public function isPartiallyProcessed(): bool
    {
        return ($this->getStatus() === Status::PARTIALLY_PROCESSED);
    }

    public function isFailed(): bool
    {
        return ($this->getStatus() === Status::FAILED);
    }

    public function isProcessing(): bool
    {
        return $this->getAttribute(self::PROCESSING);
    }

    public function isProcessable(): bool
    {
        return (($this->isProcessed() === false) and ($this->isProcessing() === false));
    }

    public function getFailureCount()
    {
        return $this->getAttribute(self::FAILURE_COUNT);
    }

    public function getTotalCount()
    {
        return $this->getAttribute(self::TOTAL_COUNT);
    }

    public function isPaymentLinkType(): bool
    {
        return ($this->getType() === Type::PAYMENT_LINK);
    }

    public function isReconciliationType(): bool
    {
        return ($this->getType() === Type::RECONCILIATION);
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
    public function getFilePrefix(string $type): string
    {
        return ($type === self::INPUT_FILE) ?
                    self::INPUT_FILE_PREFIX :
                    self::OUTPUT_FILE_PREFIX;
    }

    /**
     * Returns headers based on status of batch.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        $type = $this->getType();

        if ($this->getStatus() === Status::CREATED)
        {
            return Header::getInputHeadersForType($type);
        }
        else
        {
            return Header::getOutputHeadersForType($type);
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

    public function getFileKeyWithExt(string $ext = FileStore\Format::XLSX): string
    {
        return $this->getFileKey() . '.' . $ext;
    }

    /**
     * Get local save directory.
     *
     * Used in Processor:
     * - To move temp php request to this location and pass the same to UFH
     * - To create output file at proper location.
     *
     * @param string    $type   type of batch file input / output
     *
     * @return string
     */
    public function getLocalSaveDir(string $type): string
    {
        return storage_path('files/filestore') . '/' . $this->getFilePrefix($type);
    }

    public function getLocalSavePath(string $type): string
    {
        return $this->getLocalSaveDir($type) . $this->getFileKeyWithExt();
    }

    // ----------------------- End  Getters --------------------------

    // ----------------------- Setters -------------------------------

    public function setUploadFileUrl(string $url)
    {
        $this->setAttribute(self::UPLOAD_FILE_URL, $url);
    }

    public function setDownloadFileUrl(string $url)
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

    public function setProcessing(bool $value)
    {
        $this->setAttribute(self::PROCESSING, $value);
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

    public function setFailureReason(string $failureReason)
    {
        $this->setAttribute(self::FAILURE_REASON, $failureReason);
    }

    public function unsetFailureReason()
    {
        $this->setAttribute(self::FAILURE_REASON, null);
    }

    public function setSubType(string $subType)
    {
        $this->setAttribute(self::SUB_TYPE, $subType);
    }

    // ----------------------- End Setters ---------------------------
}
