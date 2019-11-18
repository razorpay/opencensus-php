<?php

namespace RZP\Models\PaperMandate;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Base\Traits\NotesTrait;

/**
 * @property Merchant\Entity    $merchant
 * @property BankAccount\Entity $bankAccount
 * @property Customer\Entity    $customer
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    const BANK_ACCOUNT_ID             = 'bank_account_id';
    const CUSTOMER_ID                 = 'customer_id';
    const AMOUNT                      = 'amount';
    const STATUS                      = 'status';
    const UMRN                        = 'umrn';
    const SPONSOR_BANK_CODE           = 'sponsor_bank_code';
    const UTILITY_CODE                = 'utility_code';
    const DEBIT_TYPE                  = 'debit_type';
    const TYPE                        = 'type';
    const FREQUENCY                   = 'frequency';
    const REFERENCE_1                 = 'reference_1';
    const REFERENCE_2                 = 'reference_2';
    const START_AT                    = 'start_at';
    const END_AT                      = 'end_at';
    const SECONDARY_ACCOUNT_HOLDER    = 'secondary_account_holder';
    const TERTIARY_ACCOUNT_HOLDER     = 'tertiary_account_holder';
    const TERMINAL_ID                 = 'terminal_id';
    const GENERATED_FILE_ID           = 'generated_file_id';
    const UPLOADED_FILE_ID            = 'uploaded_file_id';
    const FORM_CHECKSUM               = 'form_checksum';

    const BANK_ACCOUNT                = 'bank_account';

    const CUSTOMER                    = 'customer';

    const MERCHANT                    = 'merchant';

    const SIGNATURE_PRESENT           = 'signature_present';
    const SECONDARY_SIGNATURE_PRESENT = 'secondary_signature_present';
    const TERTIARY_SIGNATURE_PRESENT  = 'tertiary_signature_present';
    const UNTIL_CANCELLED             = 'until_cancelled';
    const ENHANCED_IMAGE              = 'enhanced_image';
    const FORM_UPLOADED               = 'form_uploaded';
    const URL                         = 'url';
    const VALIDATION_RESULT           = 'validation_result';
    const EXTRACTED_DATA              = 'extracted_data';

    const GENERATE_FORM               = 'generate_form';

    const DEFAULT_AMOUNT              = 10000000;

    protected $entity                 = 'paper_mandate';

    protected static $sign            = 'ppm';

    protected $generateIdOnCreate     = true;

    protected $fillable = [
        self::AMOUNT,
        self::TYPE,
        self::DEBIT_TYPE,
        self::FREQUENCY,
        self::REFERENCE_1,
        self::REFERENCE_2,
        self::START_AT,
        self::END_AT,
        self::SECONDARY_ACCOUNT_HOLDER,
        self::TERTIARY_ACCOUNT_HOLDER,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::STATUS,
        self::BANK_ACCOUNT,
        self::CUSTOMER,
        self::UMRN,
        self::SPONSOR_BANK_CODE,
        self::UTILITY_CODE,
        self::TYPE,
        self::DEBIT_TYPE,
        self::FREQUENCY,
        self::REFERENCE_1,
        self::REFERENCE_2,
        self::START_AT,
        self::END_AT,
        self::SECONDARY_ACCOUNT_HOLDER,
        self::TERTIARY_ACCOUNT_HOLDER,
        self::CREATED_AT,
    ];

    public $public = [
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::STATUS,
        self::UMRN,
        self::SPONSOR_BANK_CODE,
        self::UTILITY_CODE,
        self::TYPE,
        self::DEBIT_TYPE,
        self::FREQUENCY,
        self::REFERENCE_1,
        self::REFERENCE_2,
        self::START_AT,
        self::END_AT,
        self::CREATED_AT,
    ];

    protected $embeddedRelations   = [
        self::BANK_ACCOUNT,
    ];

    public $defaults = [
        self::AMOUNT                   => self::DEFAULT_AMOUNT,
        self::STATUS                   => Status::CREATED,
        self::TYPE                     => Type::CREATE,
        self::FREQUENCY                => Frequency::AS_AND_WHEN_PRESENTED,
        self::DEBIT_TYPE               => DebitType::MAXIMUM_AMOUNT,
        self::UMRN                     => null,
        self::REFERENCE_1              => null,
        self::REFERENCE_2              => null,
        self::END_AT                   => null,
        self::SPONSOR_BANK_CODE        => 'RATN0TREASU', // dummy value, will be removed in payment/nach PR
        self::UTILITY_CODE             => 'NACH00000000013149', // dummy value, will be removed in payment/nach PR
        self::SECONDARY_ACCOUNT_HOLDER => null,
        self::TERTIARY_ACCOUNT_HOLDER  => null,
    ];

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getReference1()
    {
        return $this->getAttribute(self::REFERENCE_1);
    }

    public function getReference2()
    {
        return $this->getAttribute(self::REFERENCE_1);
    }

    public function getGeneratedFileID()
    {
        return $this->getAttribute(self::GENERATED_FILE_ID);
    }

    public function getUploadedFileID()
    {
        return $this->getAttribute(self::UPLOADED_FILE_ID);
    }

    public function getGeneratedFormUrl()
    {
        $generatedFileId = $this->getGeneratedFileID();

        if (empty($generatedFileId) === true)
        {
            return null;
        }

        return (new FileUploader)->getSignedUrl($generatedFileId);
    }

    public function getUploadedFormUrl()
    {
        $uploadedFileId = $this->getUploadedFileID();

        if (empty($uploadedFileId) === true)
        {
            return null;
        }

        return (new FileUploader)->getSignedUrl($uploadedFileId);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setGeneratedFileId(string $fileId)
    {
        $this->setAttribute(self::GENERATED_FILE_ID, $fileId);
    }

    public function setUploadedFileId(string $fileId)
    {
        $this->setAttribute(self::UPLOADED_FILE_ID, $fileId);
    }

    public function setStartAt(int $timestamp)
    {
        $this->setAttribute(self::START_AT, $timestamp);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount\Entity::class);
    }
}
