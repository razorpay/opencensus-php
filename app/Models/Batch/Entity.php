<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\FileStore;

/**
 * @property Merchant\Entity    $merchant
 */
class Entity extends Base\PublicEntity
{
    use Base\Traits\HasCreator;

    const NAME                      = 'name';
    const STATUS                    = 'status';
    const PROCESSING                = 'processing';
    const TOTAL_COUNT               = 'total_count';
    const PROCESSED_COUNT           = 'processed_count';
    const SUCCESS_COUNT             = 'success_count';
    const FAILURE_COUNT             = 'failure_count';
    const ATTEMPTS                  = 'attempts';
    const CREATOR                   = 'creator';
    const CREATOR_ID                = 'creator_id';
    const CREATOR_TYPE              = 'creator_type';
    const SCHEDULE                  = 'schedule';

    /**
     * Fields below are used for accepting payload
     * from Batch Service to send mail after batch completion.
     */
    const BATCH                     = 'batch';
    const BUCKET_TYPE               = 'bucket_type';
    const OUTPUT_FILE_PATH          = 'output_file_path';
    const DOWNLOAD_FILE             = 'download_file';
    const SETTINGS                  = 'settings';

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
     * Derived attribute: holds percentage of rows processed
     */
    const PROCESSED_PERCENTAGE      = 'processed_percentage';

    /**
     * Constants used in migration file.
     */
    const STATUS_LENGTH             = 20;

    /**
     * Constants used in Batch processing.
     */
    const IDEMPOTENCY_ID_LENGTH     = 30;

    /**
     * Additional constants
     */
    const FILE                      = 'file';
    const FILE_ID                   = 'file_id';
    const FILES                     = 'files';
    const URL                       = 'url';
    const INPUT_FILE_PREFIX         = 'batch/upload/';
    const OUTPUT_FILE_PREFIX        = 'batch/download/';
    const VALIDATED_FILE_PREFIX     = 'batch/validated/';
    const CONFIG                    = 'config';
    const APPLICATION_ID            = 'application_id';
    // For payout type batch we verify otp first before proceeding with batch creation.
    const OTP                       = 'otp';

    /**
     * Constants used for batch stats api
     */
    const STATS                     = 'stats';

    /**
     * Constant used for batch multiple fetch api to include settings
     */
    const WITH_CONFIG               = 'with_config';

    const TOKEN = 'token';

    /**
     * For filtering multiple types
     */
    const TYPES                      = 'types';

    protected static $sign = 'batch';

    protected $entity = 'batch';

    protected $generateIdOnCreate = true;

    /**
     * Determines if batch entity was created in Create flow(has file upload)
     * or Validate flow(has file id as input). In former case(old case) we need
     * to throw validation errors whereas in later case we don't throw any
     * validation error but save the errors in a file and return the file id.
     */
    protected $createdByFileUpload = false;

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
        self::NAME,
        self::TYPE,
        self::GATEWAY,
        self::SUB_TYPE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::TYPE,
        self::STATUS,
        self::TOTAL_COUNT,
        self::SUCCESS_COUNT,
        self::FAILURE_COUNT,
        self::PROCESSED_COUNT,
        self::PROCESSED_PERCENTAGE,
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
        self::TOTAL_COUNT         => 0,
        self::PROCESSED_COUNT     => 0,
        self::SUCCESS_COUNT       => 0,
        self::FAILURE_COUNT       => 0,
        self::AMOUNT              => null,
        self::PROCESSED_AMOUNT    => 0,
        self::GATEWAY             => null,
        self::FAILURE_REASON      => null,
        self::SUB_TYPE            => null,
        self::NAME                => null,
        self::COMMENT             => null,
        self::PROCESSED_AT        => null,
    ];

    protected $casts = [
        self::TOTAL_COUNT      => 'int',
        self::PROCESSED_COUNT  => 'int',
        self::SUCCESS_COUNT    => 'int',
        self::FAILURE_COUNT    => 'int',
        self::AMOUNT           => 'int',
        self::PROCESSED_AMOUNT => 'int',
        self::ATTEMPTS         => 'int',
        self::PROCESSING       => 'bool',
    ];

    protected $appends = [
        self::PROCESSED_PERCENTAGE,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::PROCESSED_COUNT,
        self::PROCESSED_PERCENTAGE,
    ];

    /**
     * {@inheritDoc}
     * Ref: validateInputByType
     */
    public function build(array $input = [], string $operation = 'create')
    {
        $this->input = $input;

        $this->modify($input);

        $this->validateInputByType($input, $operation);

        // Todo: https://github.com/razorpay/spine/issues/25
        $this->getValidator()->validateOtp($input);

        $this->generate($input);

        $this->unsetInput('create', $input);

        $this->fill($input);

        return $this;
    }

    /**
     * Does input validation for create based on batch type if defined else there is one default create rule.
     *
     * @param array $input
     * @param bool  $operation Indicates if called in the batch validate flow or normal create flow
     */
    protected function validateInputByType(array $input, string $operation = 'create')
    {
        $type = $input[Entity::TYPE] ?? 'unknown';
        $ruleKey = camel_case("{$type}_{$operation}_rules");
        $fallbackRuleKey = camel_case("{$type}_create_rules");

        if (property_exists(Validator::class, $ruleKey) === true)
        {
            $validationOp = snake_case(str_before($ruleKey, 'Rules'));
        }
        else if (property_exists(Validator::class, $fallbackRuleKey) === true)
        {
            $validationOp = snake_case(str_before($fallbackRuleKey, 'Rules'));
        }
        else
        {
            $validationOp = 'default_create';
        }

        $this->validateInput($validationOp, $input);
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
     * The file which our processor creates with validation
     * results only. This is available to user to download.
     * Currently available only for payment_links.
     *
     * @return FileStore\Entity
     */
    public function validatedFile()
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, FileStore\Type::BATCH_VALIDATED)
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

    /**
     * Returns the latest file (of the given type) associated with this batch.
     *
     * @param string $type
     * @return FileStore\Entity
     */
    public function latestFileByType(string $type)
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, $type)
                    ->latest()
                    ->first();
    }

    /**
     * Returns all the files (of the given type) associated with this batch.
     *
     * @param string $type
     * @return FileStore\Entity
     */
    public function filesByType(string $type)
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, $type)
                    ->latest()
                    ->get();
    }

    // ----------------------- Getters -------------------------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

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

    public function getFailureReason()
    {
        return $this->getAttribute(self::FAILURE_REASON);
    }

    public function getAttempts(): int
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    // TODO: Add type hint after deployed
    public function getProcessedCount()
    {
        return $this->getAttribute(self::PROCESSED_COUNT);
    }

    public function getProcessedPercentage(): int
    {
        return $this->getAttribute(self::PROCESSED_PERCENTAGE);
    }

    public function isPaymentLinkType(): bool
    {
        return ($this->getType() === Type::PAYMENT_LINK);
    }

    public function isReconciliationType(): bool
    {
        return ($this->getType() === Type::RECONCILIATION);
    }

    public function isPayoutType(): bool
    {
        return ($this->getType() === Type::PAYOUT);
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

    public function isCreatedByFileUpload(): bool
    {
        return $this->createdByFileUpload;
    }

    public function toArrayTrace(array $fields = [], array $extra = []): array
    {
        // Always merges ID and MERCHANT_ID to trace fields
        $fields = array_merge($fields, [self::ID, self::MERCHANT_ID]);

        return array_merge($this->only($fields), $extra);
    }

    public function toArrayTraceAll(): array
    {
        return $this->attributesToArray();
    }

    /**
     * Gets dimensions for metrics around batch module
     * @param  array $extra Additional key, value pair of dimensions
     * @return array
     */
    public function getMetricDimensions(array $extra = []): array
    {
        return $extra + [
                'type'            =>  $this->getType(),
                'sub_type'        =>  $this->getSubType(),
                'gateway'         =>  $this->getGateway(),
            ];
    }
    // ----------------------- End  Getters --------------------------

    // ----------------------- Setters -------------------------------
    public function setName(string $name)
    {
        $this->setAttribute(self::NAME, $name);
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
        Status::validateStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    /**
     * At the time of retrying failed batch we temporarily set status to null so
     * that processer code will continue and evaluate new status and set at the end.
     * Note that null is not a valid status and if processor failed to evaluate &
     * set new status entity save will fail (which is expected & good).
     */
    public function setStatusNull()
    {
        $this->setAttribute(self::STATUS, null);
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
        $attempts = $this->getAttribute(self::ATTEMPTS);

        $this->setAttribute(self::ATTEMPTS, $attempts + 1);
    }

    public function incrementFailureCount(int $failureCount = 1)
    {
        $this->increment(self::FAILURE_COUNT, $failureCount);
    }

    public function incrementSuccessCount(int $successCount = 1)
    {
        $this->increment(self::SUCCESS_COUNT, $successCount);
    }

    public function incrementProcessedCount()
    {
        $this->increment(self::PROCESSED_COUNT);
    }

    public function unsetProcessedCount()
    {
        $this->setAttribute(self::PROCESSED_COUNT, 0);
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

    public function setCreatedByFileUpload(bool $createdByFileUpload)
    {
        $this->createdByFileUpload = $createdByFileUpload;
    }

    // ----------------------- End Setters ---------------------------

    // ----------------------- Appends -------------------------------

    /**
     * Gets derived attribute, currently only exposed on admin auth (via corresponding public setter method)
     * @return int
     */
    public function getProcessedPercentageAttribute(): int
    {
        $processedCount = $this->getProcessedCount();
        $totalCount     = $this->getTotalCount();

        return ($totalCount !== 0) ? (($processedCount / $totalCount) * 100) : 0;
    }

    // ----------------------- End Appends ---------------------------

    // ----------------------- Public Setters ------------------------

    public function setPublicProcessedCountAttribute(array & $output)
    {
        if (app('basicauth')->isAdminAuth() === false)
        {
            unset($output[self::PROCESSED_COUNT]);
        }
    }

    public function setPublicProcessedPercentageAttribute(array & $output)
    {
        if (app('basicauth')->isAdminAuth() === false)
        {
            unset($output[self::PROCESSED_PERCENTAGE]);
        }
    }

    // ----------------------- End Public Setters --------------------
}
