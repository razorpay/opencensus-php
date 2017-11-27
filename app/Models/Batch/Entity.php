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

    /**
     * Fields amount and processed_amount represent the total amounnt across
     * entities present in the batch input file, for batches like refund.
     */
    const AMOUNT                    = 'amount';
    const PROCESSED_AMOUNT          = 'processed_amount';

    const COMMENT                   = 'comment';
    const PROCESSED_AT              = 'processed_at';
    const TYPE                      = 'type';

    /**
     * Fields sub_type is used for further classification of the batch.
     * Currently being used for reconciliation batch and can have values like
     * combined | payment | refund
     */
    const SUB_TYPE                  = 'sub_type';
    const GATEWAY                   = 'gateway';
    const FAILURE_REASON            = 'failure_reason';

    /**
     * Constants used in migration file.
     */
    const STATUS_LENGTH             = 20;

    /**
     * Additional constants
     */
    const FILE                      = 'file';
    const FILES                     = 'files';
    const URL                       = 'url';
    const INPUT_FILE_PREFIX         = 'batch/upload/';
    const OUTPUT_FILE_PREFIX        = 'batch/download/';
    const INPUT_DETAILS             = 'input_details';

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
        self::SUB_TYPE,
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
        self::UPLOAD_FILE_URL     => '', // TODO: Remove after column dropped
        self::DOWNLOAD_FILE_URL   => null,
        self::TOTAL_COUNT         => 0,
        self::SUCCESS_COUNT       => 0,
        self::FAILURE_COUNT       => 0,
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

    /**
     * Overridden
     * Ref: validateInputByType
     *
     * @param  array  $input
     * @return Entity
     */
    public function build(array $input = [])
    {
        $this->input = $input;

        $this->modify($input);

        $this->validateInputByType($input);

        $this->generate($input);

        $this->unsetInput('create', $input);

        $this->fill($input);

        return $this;
    }

    /**
     * Does input validation for create based on batch type if defined else
     * there is one default create rule.
     *
     * @param array $input
     */
    protected function validateInputByType(array $input)
    {
        $operation = 'default_create';

        $type = $input[Entity::TYPE] ?? 'unknown';
        $rule = camel_case($type) . 'CreateRules';

        if (property_exists(Validator::class, $rule) === true)
        {
            $operation = $type . '_create';
        }

        $this->validateInput($operation, $input);
    }

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
                        FileStore\Type::RECONCILIATION_BATCH_INPUT :
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

    /**
     * Returns the latest file associated with this batch, be output/input type.
     *
     * @return FileStore\Entity
     */
    public function latestFile()
    {
        return $this->files()->latest()->first();
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

    public function getSubType()
    {
        return $this->getAttribute(self::SUB_TYPE);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getUploadFileUrl()
    {
        return $this->getAttribute(self::UPLOAD_FILE_URL);
    }

    public function getDownloadFileUrl()
    {
        return $this->getAttribute(self::DOWNLOAD_FILE_URL);
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

    public function getSuccessCount(): int
    {
        return $this->getAttribute(self::SUCCESS_COUNT);
    }

    public function getFailureCount(): int
    {
        return $this->getAttribute(self::FAILURE_COUNT);
    }

    public function getTotalCount(): int
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
     * @param string    $prefix     prefix to use while forming the path
     *
     * @return string
     */
    public function getLocalSaveDir(string $prefix): string
    {
        return storage_path('files/filestore') . '/' . $prefix;
    }

    public function getLocalSavePath(string $prefix): string
    {
        return $this->getLocalSaveDir($prefix) . $this->getFileKeyWithExt();
    }

    // ----------------------- End  Getters --------------------------

    // ----------------------- Setters -------------------------------

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
        Status::validateStatus($status);

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
