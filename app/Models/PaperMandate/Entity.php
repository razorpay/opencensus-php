<?php

namespace RZP\Models\PaperMandate;

use App;
use Config;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\Customer;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Base\Traits\NotesTrait;

/**
 * @property Merchant\Entity    $merchant
 * @property BankAccount\Entity $bankAccount
 * @property Customer\Entity    $customer
 * @property Terminal\Entity    $terminal
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
    const GENERATED_FORM_URL          = 'generated_form_url';
    const GENERATED_FORM_URL_EXPIRE   = 'generated_form_url_expire';
    const UPLOADED_FILE_ID            = 'uploaded_file_id';
    const FORM_CHECKSUM               = 'form_checksum';

    const BANK_ACCOUNT                = 'bank_account';

    const CUSTOMER                    = 'customer';

    const MERCHANT                    = 'merchant';

    const PAPER_MANDATE_UPLOAD_ID     = 'paper_mandate_upload_id';

    const SIGNATURE_PRESENT           = 'signature_present';
    const SECONDARY_SIGNATURE_PRESENT = 'secondary_signature_present';
    const TERTIARY_SIGNATURE_PRESENT  = 'tertiary_signature_present';
    const UNTIL_CANCELLED             = 'until_cancelled';
    const ENHANCED_IMAGE              = 'enhanced_image';
    const FORM_UPLOADED               = 'form_uploaded';
    const URL                         = 'url';
    const VALIDATION_RESULT           = 'validation_result';
    const EXTRACTED_DATA              = 'extracted_data';

    const GENERATED_IMAGE             = 'generated_image';

    const DEFAULT_AMOUNT              = 10000000;

    const IS_NACH_FORM_UPLOADED       = 'is_nach_uploaded';

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
        self::TERMINAL_ID,
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
        self::TERMINAL_ID,
        self::FORM_CHECKSUM,
        self::GENERATED_FILE_ID,
        self::GENERATED_FORM_URL,
        self::GENERATED_FORM_URL_EXPIRE,
        self::UPLOADED_FILE_ID,
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
        self::FORM_CHECKSUM,
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
        self::SECONDARY_ACCOUNT_HOLDER => null,
        self::TERTIARY_ACCOUNT_HOLDER  => null,
        self::FORM_CHECKSUM            => null,
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
        return $this->getAttribute(self::REFERENCE_2);
    }

    public function getGeneratedFileID()
    {
        return $this->getAttribute(self::GENERATED_FILE_ID);
    }

    public function getUploadedFileID()
    {
        return $this->getAttribute(self::UPLOADED_FILE_ID);
    }

    public function isGeneratedFormUrlExpired()
    {
        return true;

        // Todo: enable once ufh s3 signed url timeout bug is fixed
//        $expiry = $this->getAttribute(self::GENERATED_FORM_URL_EXPIRE);
//
//        $bufferTime = '+' . Constants::SHORT_URL_BUFFER_TIME . ' days';
//
//        if (($expiry === null) or ($expiry < (new Carbon($bufferTime))->getTimestamp()))
//        {
//            return true;
//        }
//
//        return false;
    }

    public function getGeneratedFormUrlTransient()
    {
        $generatedFileId = $this->getGeneratedFileID();

        if (empty($generatedFileId) === true)
        {
            return null;
        }

        return (new FileUploader($this))->getSignedShortUrl(
            $generatedFileId,
            Constants::MAX_SIGNED_URL_TIMEOUT
        );
    }

    public function getGeneratedFormUrl(Invoice\Entity $invoice)
    {
        $baseInvoiceUrl = Config::get('app.invoice');

        $url = $baseInvoiceUrl . '/'. App::getFacadeRoot()['rzp.mode'] . '/'. $invoice->getSignedIdOrNull($invoice->getId()) . '/downloadnach';

        $shortUrl = (new FileUploader($this))->getShortUrl($url);

        return $shortUrl;
    }

    public function getUploadedFormUrl()
    {
        $uploadedFileId = $this->getUploadedFileID();

        if (empty($uploadedFileId) === true)
        {
            return null;
        }

        return (new FileUploader($this))->getSignedShortUrl($uploadedFileId);
    }

    public function setGeneratedFormUrl($generatedFormUrl)
    {
        $this->setAttribute(self::GENERATED_FORM_URL, $generatedFormUrl);
    }

    public function setGeneratedFormUrlExpire($generatedFormUrlExpire)
    {
        $this->setAttribute(self::GENERATED_FORM_URL_EXPIRE, $generatedFormUrlExpire);
    }

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }

    public function getStartAt()
    {
        return $this->getAttribute(self::START_AT);
    }

    public function getEndAt()
    {
        return $this->getAttribute(self::END_AT);
    }

    public function getSecondaryAccountHolder()
    {
        return $this->getAttribute(self::SECONDARY_ACCOUNT_HOLDER);
    }

    public function getTertiaryAccountHolder()
    {
        return $this->getAttribute(self::TERTIARY_ACCOUNT_HOLDER);
    }

    public function getUtilityCode()
    {
        return $this->getAttribute(self::UTILITY_CODE);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getSponsorBankCode()
    {
        return $this->getAttribute(self::SPONSOR_BANK_CODE);
    }

    public function getFormChecksum()
    {
        return $this->getAttribute(self::FORM_CHECKSUM);
    }

    public function getDebitType()
    {
        return $this->getAttribute(self::DEBIT_TYPE);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getUmrn()
    {
        return $this->getAttribute(self::UMRN);
    }

    public function getFrequency()
    {
        return $this->getAttribute(self::FREQUENCY);
    }

    public function getCreatedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function isFormUploadedSuccessfully(): bool
    {
        return $this->getStatus() === Status::AUTHENTICATED;
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

    public function setUtilityCode($utilityCode)
    {
        $this->setAttribute(self::UTILITY_CODE, $utilityCode);
    }

    public function setSponsorBankCode($sponsorBankCode)
    {
        $this->setAttribute(self::SPONSOR_BANK_CODE, $sponsorBankCode);
    }

    public function setTerminalId($terminalId)
    {
        $this->setAttribute(self::TERMINAL_ID, $terminalId);
    }

    public function setFormChecksum($checksum)
    {
        $this->setAttribute(self::FORM_CHECKSUM, $checksum);
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

    public function terminal()
    {
        return $this->belongsTo(Terminal\Entity::class);
    }

    public function paperMandateUploads()
    {
        return $this->hasMany(PaperMandateUpload\Entity::class);
    }
}
