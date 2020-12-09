<?php

namespace RZP\Models\PaperMandate\PaperMandateUpload;

use Carbon\Carbon;
use Illuminate\Support\Str;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\PaperMandate;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * @property PaperMandate\Entity    $paperMandate
 */
class Entity extends Base\PublicEntity
{
    const PAPER_MANDATE_ID            = 'paper_mandate_id';
    const UPLOADED_FILE_ID            = 'uploaded_file_id';
    const ENHANCED_FILE_ID            = 'enhanced_file_id';
    const STATUS                      = 'status';
    const STATUS_REASON               = 'status_reason';
    const TIME_TAKEN_TO_PROCESS       = 'time_taken_to_process';

    // extracted data from form
    const EXTRACTED_RAW_DATA          = 'extracted_raw_data';
    const UMRN                        = 'umrn';
    const NACH_DATE                   = 'nach_date';
    const SPONSOR_CODE                = 'sponsor_code';
    const UTILITY_CODE                = 'utility_code';
    const BANK_NAME                   = 'bank_name';
    const ACCOUNT_TYPE                = 'account_type';
    const IFSC_CODE                   = 'ifsc_code';
    const MICR                        = 'micr';
    const COMPANY_NAME                = 'company_name';
    const FREQUENCY                   = 'frequency';
    const AMOUNT_IN_NUMBER            = 'amount_in_number';
    const AMOUNT_IN_WORDS             = 'amount_in_words';
    const DEBIT_TYPE                  = 'debit_type';
    const START_DATE                  = 'start_date';
    const END_DATE                    = 'end_date';
    const UNTIL_CANCELLED             = 'until_cancelled';
    const NACH_TYPE                   = 'nach_type';
    const PHONE_NUMBER                = 'phone_number';
    const EMAIL_ID                    = 'email_id';
    const REFERENCE_1                 = 'reference_1';
    const REFERENCE_2                 = 'reference_2';
    const SIGNATURE_PRESENT_PRIMARY   = 'signature_present_primary';
    const SIGNATURE_PRESENT_SECONDARY = 'signature_present_secondary';
    const SIGNATURE_PRESENT_TERTIARY  = 'signature_present_tertiary';
    const PRIMARY_ACCOUNT_HOLDER      = 'primary_account_holder';
    const SECONDARY_ACCOUNT_HOLDER    = 'secondary_account_holder';
    const TERTIARY_ACCOUNT_HOLDER     = 'tertiary_account_holder';
    const ACCOUNT_NUMBER              = 'account_number';
    const FORM_CHECKSUM               = 'form_checksum';
    const NOT_MATCHING                = 'not_matching';

    const SUCCESS                     = 'success';
    const ENHANCED_IMAGE              = 'enhanced_image';
    const UPLOADED_IMAGE              = 'uploaded_image';

    protected $entity                 = 'paper_mandate_upload';

    protected static $sign            = 'pmu';

    protected $fillable = [
        self::UPLOADED_FILE_ID,
        self::EMAIL_ID,
        self::AMOUNT_IN_WORDS,
        self::UTILITY_CODE,
        self::REFERENCE_1,
        self::BANK_NAME,
        self::DEBIT_TYPE,
        self::MICR,
        self::FREQUENCY,
        self::SIGNATURE_PRESENT_TERTIARY,
        self::UNTIL_CANCELLED,
        self::SIGNATURE_PRESENT_SECONDARY,
        self::NACH_TYPE,
        self::ACCOUNT_NUMBER,
        self::NACH_DATE,
        self::PHONE_NUMBER,
        self::TERTIARY_ACCOUNT_HOLDER,
        self::UMRN,
        self::COMPANY_NAME,
        self::IFSC_CODE,
        self::REFERENCE_2,
        self::ACCOUNT_TYPE,
        self::AMOUNT_IN_NUMBER,
        self::END_DATE,
        self::SPONSOR_CODE,
        self::SIGNATURE_PRESENT_PRIMARY,
        self::SECONDARY_ACCOUNT_HOLDER,
        self::START_DATE,
        self::PRIMARY_ACCOUNT_HOLDER,
        self::FORM_CHECKSUM,
        self::EXTRACTED_RAW_DATA,
    ];

    protected $visible = [
        self::ID,
        self::PAPER_MANDATE_ID,
        self::MERCHANT_ID,
        self::UPLOADED_FILE_ID,
        self::ENHANCED_FILE_ID,
        self::STATUS,
        self::STATUS_REASON,
        self::TIME_TAKEN_TO_PROCESS,
        self::EMAIL_ID,
        self::AMOUNT_IN_WORDS,
        self::UTILITY_CODE,
        self::REFERENCE_1,
        self::BANK_NAME,
        self::DEBIT_TYPE,
        self::MICR,
        self::FREQUENCY,
        self::SIGNATURE_PRESENT_TERTIARY,
        self::UNTIL_CANCELLED,
        self::SIGNATURE_PRESENT_SECONDARY,
        self::NACH_TYPE,
        self::ACCOUNT_NUMBER,
        self::NACH_DATE,
        self::PHONE_NUMBER,
        self::TERTIARY_ACCOUNT_HOLDER,
        self::UMRN,
        self::COMPANY_NAME,
        self::IFSC_CODE,
        self::REFERENCE_2,
        self::ACCOUNT_TYPE,
        self::AMOUNT_IN_NUMBER,
        self::END_DATE,
        self::SPONSOR_CODE,
        self::SIGNATURE_PRESENT_PRIMARY,
        self::SECONDARY_ACCOUNT_HOLDER,
        self::START_DATE,
        self::PRIMARY_ACCOUNT_HOLDER,
        self::FORM_CHECKSUM,
        self::NOT_MATCHING,
        self::EXTRACTED_RAW_DATA,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::STATUS,
        self::STATUS_REASON,
        self::EMAIL_ID,
        self::AMOUNT_IN_WORDS,
        self::UTILITY_CODE,
        self::REFERENCE_1,
        self::BANK_NAME,
        self::DEBIT_TYPE,
        self::MICR,
        self::FREQUENCY,
        self::SIGNATURE_PRESENT_TERTIARY,
        self::UNTIL_CANCELLED,
        self::SIGNATURE_PRESENT_SECONDARY,
        self::NACH_TYPE,
        self::ACCOUNT_NUMBER,
        self::NACH_DATE,
        self::PHONE_NUMBER,
        self::TERTIARY_ACCOUNT_HOLDER,
        self::UMRN,
        self::COMPANY_NAME,
        self::IFSC_CODE,
        self::REFERENCE_2,
        self::ACCOUNT_TYPE,
        self::AMOUNT_IN_NUMBER,
        self::END_DATE,
        self::SPONSOR_CODE,
        self::SIGNATURE_PRESENT_PRIMARY,
        self::SECONDARY_ACCOUNT_HOLDER,
        self::START_DATE,
        self::PRIMARY_ACCOUNT_HOLDER,
        self::FORM_CHECKSUM,
        self::NOT_MATCHING,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::STATUS                      => Status::PENDING,
        self::STATUS_REASON               => null,
        self::UPLOADED_FILE_ID            => null,
        self::ENHANCED_FILE_ID            => null,
        self::EMAIL_ID                    => null,
        self::AMOUNT_IN_WORDS             => null,
        self::UTILITY_CODE                => null,
        self::REFERENCE_1                 => null,
        self::BANK_NAME                   => null,
        self::DEBIT_TYPE                  => null,
        self::MICR                        => null,
        self::FREQUENCY                   => null,
        self::SIGNATURE_PRESENT_TERTIARY  => null,
        self::UNTIL_CANCELLED             => null,
        self::SIGNATURE_PRESENT_SECONDARY => null,
        self::NACH_TYPE                   => null,
        self::ACCOUNT_NUMBER              => null,
        self::NACH_DATE                   => null,
        self::PHONE_NUMBER                => null,
        self::TERTIARY_ACCOUNT_HOLDER     => null,
        self::UMRN                        => null,
        self::COMPANY_NAME                => null,
        self::IFSC_CODE                   => null,
        self::REFERENCE_2                 => null,
        self::ACCOUNT_TYPE                => null,
        self::AMOUNT_IN_NUMBER            => null,
        self::END_DATE                    => null,
        self::SPONSOR_CODE                => null,
        self::SIGNATURE_PRESENT_PRIMARY   => null,
        self::SECONDARY_ACCOUNT_HOLDER    => null,
        self::START_DATE                  => null,
        self::PRIMARY_ACCOUNT_HOLDER      => null,
        self::FORM_CHECKSUM               => null,
        self::EXTRACTED_RAW_DATA          => null,
        self::NOT_MATCHING                => null,
    ];

    protected $fieldsToBeValidated = [
        self::FORM_CHECKSUM,
        self::SIGNATURE_PRESENT_PRIMARY,
    ];

    public function getEnhancedFileId()
    {
        return $this->getAttribute(self::ENHANCED_FILE_ID);
    }

    public function getUploadedFileId()
    {
        return $this->getAttribute(self::UPLOADED_FILE_ID);
    }

    public function getUtilityCode()
    {
        return $this->getAttribute(self::UTILITY_CODE);
    }

    public function getAmountInNumber()
    {
        return intval($this->getAttribute(self::AMOUNT_IN_NUMBER));
    }

    public function getSponsorCode()
    {
        return $this->getAttribute(self::SPONSOR_CODE);
    }

    public function getFormChecksum()
    {
        return $this->getAttribute(self::FORM_CHECKSUM);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getIfscCode()
    {
        return $this->getAttribute(self::IFSC_CODE);
    }

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getStatusReason()
    {
        return $this->getAttribute(self::STATUS_REASON);
    }

    public function getNotMatching()
    {
        if (empty($this->getAttribute(self::NOT_MATCHING)))
        {
            return [];
        }

        return json_decode($this->getAttribute(self::NOT_MATCHING), 1);
    }

    public function isSignaturePresentPrimary()
    {
        return $this->getAttribute(self::SIGNATURE_PRESENT_PRIMARY) === 'yes';
    }

    public function isAccountHolderPresentPrimary()
    {
        return $this->getAttribute(self::PRIMARY_ACCOUNT_HOLDER) !== null and
               $this->getAttribute(self::PRIMARY_ACCOUNT_HOLDER) !== '';
    }

    public function isSignaturePresentSecondary()
    {
        return $this->getAttribute(self::SIGNATURE_PRESENT_SECONDARY) === 'yes';
    }

    public function isAccountHolderPresentSecondary()
    {
        return $this->getAttribute(self::SECONDARY_ACCOUNT_HOLDER) !== null and
               $this->getAttribute(self::SECONDARY_ACCOUNT_HOLDER) !== '';
    }

    public function isSignaturePresentTertiary()
    {
        return $this->getAttribute(self::SIGNATURE_PRESENT_TERTIARY) === 'yes';
    }

    public function isAccountHolderPresentTertiary()
    {
        return $this->getAttribute(self::TERTIARY_ACCOUNT_HOLDER) !== null and
               $this->getAttribute(self::TERTIARY_ACCOUNT_HOLDER) !== '';
    }

    public function setEnhancedFileId(string $fileId)
    {
        $this->setAttribute(self::ENHANCED_FILE_ID, $fileId);
    }

    public function setNotMatching(string $notMatching)
    {
        $this->setAttribute(self::NOT_MATCHING, $notMatching);
    }

    public function setTimeTakenToProcess($timeTaken)
    {
        $this->setAttribute(self::TIME_TAKEN_TO_PROCESS, $timeTaken);
    }

    public function setStatus(string $status)
    {
        Status::isValidType($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setStatusReason(string $statusReason)
    {
        $this->setAttribute(self::STATUS_REASON, $statusReason);
    }

    public function paperMandate()
    {
        return $this->belongsTo(PaperMandate\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function toArrayPublic()
    {
        $publicArray = parent::toArrayPublic();

        $publicArray[self::NOT_MATCHING] = json_decode($publicArray[self::NOT_MATCHING], 1);

        $publicArray[self::SUCCESS] = $this->getStatus() === Status::ACCEPTED;

        $publicArray[self::ENHANCED_IMAGE] = (new PaperMandate\FileUploader($this->paperMandate))->getSignedUrl($this->getEnhancedFileId());

        // todo: once front end dashboard makes the changes remove
        $this->populateDataForBackWardCompatibility($publicArray);

        return $publicArray;
    }

    public function toArrayAdmin(): array
    {
        $adminArray = parent::toArrayAdmin();

        $adminArray[self::NOT_MATCHING] = json_decode($adminArray[self::NOT_MATCHING], 1);

        $adminArray[self::ENHANCED_IMAGE] = (new PaperMandate\FileUploader($this->paperMandate))->getSignedUrl($this->getEnhancedFileId());

        $adminArray[self::UPLOADED_IMAGE] = (new PaperMandate\FileUploader($this->paperMandate))->getSignedUrl($this->getUploadedFileId());

        $adminArray[PaperMandate\Entity::GENERATED_IMAGE] = $this->paperMandate->getGeneratedFormUrlTransient();

        return $adminArray;
    }

    public function validateExtractedData()
    {
        $fieldsNotMatching = $this->generateFieldsNotMatching();

        if (in_array(self::SIGNATURE_PRESENT_PRIMARY, $fieldsNotMatching) === true)
        {
            throw new BadRequestValidationFailureException(
                'signature is not detected in the NACH form',
                'SIGNATURE'
            );
        }

        if (in_array(self::FORM_CHECKSUM, $fieldsNotMatching))
        {
            throw new BadRequestValidationFailureException(
                'The uploaded form does not match with that in our records. ' .
                'Expected Form ID - '. $this->paperMandate->getFormChecksum() . '. ' .
                'Please verify the form and upload again.',
                ''
            );
        }
    }

    public function validateExtractedDataForPayment()
    {
        $fieldsNotMatching = $this->generateFieldsNotMatching();

        if (in_array(self::SIGNATURE_PRESENT_PRIMARY, $fieldsNotMatching) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NACH_FORM_SIGNATURE_IS_MISSING);
        }

        if (in_array(self::FORM_CHECKSUM, $fieldsNotMatching))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NACH_FORM_MISMATCH);
        }

        if (empty($fieldsNotMatching) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NACH_FORM_DATA_MISMATCH);
        }
    }

    private function generateFieldsNotMatching(): array
    {
        $fieldsNotMatching = [];

        foreach ($this->fieldsToBeValidated as $field)
        {
            $function = 'validate_' . $field;

            $function = Str::camel($function);

            $success = $this->$function();

            if ($success === false)
            {
                $fieldsNotMatching[] = $field;
            }
        }

        if (empty($fieldsNotMatching) === false)
        {
            $this->setStatus(Status::REJECTED);

            $this->setStatusReason('some fields are not matching');
        }
        else
        {
            $this->setStatus(Status::ACCEPTED);
        }

        $this->setNotMatching(json_encode($fieldsNotMatching));

        $this->saveOrFail();

        return $fieldsNotMatching;
    }

    protected function validateUtilityCode(): bool
    {
        return $this->getUtilityCode() === $this->paperMandate->getUtilityCode();
    }

    protected function validateAmountInNumber(): bool
    {
        $expected = $this->paperMandate->getAmount();

        $extracted = $this->getAmountInNumber();

        return $expected == $extracted;
    }

    protected function validateSponsorCode(): bool
    {
        $expected = strtoupper($this->paperMandate->getSponsorBankCode());

        $extracted = $this->getSponsorCode();

        return $expected === $extracted;
    }

    protected function validateFormChecksum(): bool
    {
        $expected = $this->paperMandate->getFormChecksum();

        $extracted = $this->getFormChecksum();

        return $expected === $extracted;
    }

    /*
     * for now form is accepted if  signature is present in
     * at least any one of the field
     */
    protected function validateSignaturePresentPrimary(): bool
    {
        return $this->isSignaturePresentPrimary() or
            $this->isSignaturePresentSecondary() or
            $this->isSignaturePresentTertiary();
    }

    protected function validateSignaturePresentSecondary(): bool
    {
        return $this->isAccountHolderPresentSecondary() ? $this->isSignaturePresentSecondary() : true;
    }

    protected function validateSignaturePresentTertiary(): bool
    {
        return $this->isAccountHolderPresentTertiary() ? $this->isSignaturePresentTertiary() : true;
    }

    protected function validateAccountNumber(): bool
    {
        $expected = $this->paperMandate->bankAccount->getAccountNumber();

        $extracted = $this->getAccountNumber();

        return $expected === $extracted;
    }

    protected function validateIfscCode(): bool
    {
        $expected = $this->paperMandate->bankAccount->getIfscCode();

        $extracted = $this->getIfscCode();

        return $expected === $extracted;
    }

    protected function validateAccountType(): bool
    {
        $expected = $this->paperMandate->bankAccount->getAccountType();

        $extracted = $this->getAccountType();

        return $expected === $extracted;
    }

    // todo: remove once backward compatibility is not required
    protected function populateDataForBackWardCompatibility(array &$publicArray)
    {
        $publicArray['errors']['not_matching'] = $publicArray[Entity::NOT_MATCHING];

        $extractedData = [
            [
                'key' => 'bank_account.account_number',
                'expected_value' => $this->paperMandate->bankAccount->getAccountNumber(),
                'extracted_value' => $publicArray[Entity::ACCOUNT_NUMBER],
            ],
            [
                'key' => 'bank_account.ifsc_code',
                'expected_value' => $this->paperMandate->bankAccount->getIfscCode(),
                'extracted_value' => $publicArray[Entity::IFSC_CODE],
            ],
            [
                'key' => 'bank_account.account_type',
                'expected_value' => $this->paperMandate->bankAccount->getAccountType(),
                'extracted_value' => $publicArray[Entity::ACCOUNT_TYPE],
            ],
            [
                'key' => 'merchant.name',
                'expected_value' => $this->paperMandate->terminal->merchant->getName(),
                'extracted_value' => $publicArray[Entity::COMPANY_NAME],
            ],
            [
                'key' => 'customer.name',
                'expected_value' => $this->paperMandate->bankAccount->getBeneficiaryName(),
                'extracted_value' => $publicArray[Entity::PRIMARY_ACCOUNT_HOLDER],
            ],
            [
                'key' => 'customer.email',
                'expected_value' => $this->paperMandate->bankAccount->getBeneficiaryEmail(),
                'extracted_value' => $publicArray[Entity::EMAIL_ID],
            ],
            [
                'key' => 'customer.contact',
                'expected_value' => $this->paperMandate->bankAccount->getBeneficiaryMobile(),
                'extracted_value' => $publicArray[Entity::PHONE_NUMBER],
            ],
            [
                'key' => 'utility_code',
                'expected_value' => $this->paperMandate->getUtilityCode(),
                'extracted_value' => $publicArray[Entity::UTILITY_CODE],
            ],
            [
                'key' => 'debit_type',
                'expected_value' => $this->paperMandate->getDebitType(),
                'extracted_value' => $publicArray[Entity::DEBIT_TYPE],
            ],
            [
                'key' => 'frequency',
                'expected_value' => $this->paperMandate->getFrequency(),
                'extracted_value' => $publicArray[Entity::FREQUENCY],
            ],
            [
                'key' => 'type',
                'expected_value' => $this->paperMandate->getType(),
                'extracted_value' => $publicArray[Entity::NACH_TYPE],
            ],
            [
                'key' => 'umrn',
                'expected_value' => $this->paperMandate->getUmrn(),
                'extracted_value' => $publicArray[Entity::UMRN],
            ],
            [
                'key' => 'amount',
                'expected_value' => $this->paperMandate->getAmount(),
                'extracted_value' => $publicArray[Entity::AMOUNT_IN_NUMBER],
            ],
            [
                'key' => 'sponsor_bank_code',
                'expected_value' => $this->paperMandate->getSponsorBankCode(),
                'extracted_value' => $publicArray[Entity::SPONSOR_CODE],
            ],
            [
                'key' => 'reference_1',
                'expected_value' => $this->paperMandate->getReference1(),
                'extracted_value' => empty($publicArray[self::REFERENCE_1]) ? null : $publicArray[self::REFERENCE_1],
            ],
            [
                'key' => 'reference_2',
                'expected_value' => $this->paperMandate->getReference2(),
                'extracted_value' => empty($publicArray[self::REFERENCE_2]) ? null : $publicArray[self::REFERENCE_2],
            ],
            [
                'key' => 'form_checksum',
                'expected_value' => $this->paperMandate->getFormChecksum(),
                'extracted_value' => $publicArray[self::FORM_CHECKSUM],
            ],
            [
                'key' => 'created_at',
                'expected_value' => $this->getFormattedDate($this->paperMandate->getCreatedAt()),
                'extracted_value' => $publicArray[self::NACH_DATE],
            ],
            [
                'key' => 'start_at',
                'expected_value' => $this->getFormattedDate($this->paperMandate->getStartAt()),
                'extracted_value' => $publicArray[self::START_DATE],
            ],
            [
                'key' => 'end_at',
                'expected_value' => $this->getFormattedDate($this->paperMandate->getEndAt()),
                'extracted_value' => empty($publicArray[self::END_DATE]) ? null : $publicArray[self::END_DATE],
            ],
            [
                'key' => 'until_cancelled',
                'expected_value' => empty($this->paperMandate->getEndAt()),
                'extracted_value' => boolval($publicArray[self::UNTIL_CANCELLED]),
            ],
        ];

        $publicArray['extracted_data'] = $extractedData;
    }

    protected function getFormattedDate($time)
    {
        if (empty($time) === true)
        {
            return null;
        }

        return Carbon::createFromTimestamp($time, Timezone::IST)->format('d/m/Y');
    }
}
