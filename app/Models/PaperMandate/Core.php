<?php

namespace RZP\Models\PaperMandate;

use Storage;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\File;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;
use RZP\Models\SubscriptionRegistration\SubscriptionRegistrationConstants;

class Core extends Base\Core
{
    const KEY             = 'key';
    const EXTRACTED_VALUE = 'extracted_value';
    const EXPECTED_VALUE  = 'expected_value';
    const NOT_MATCHING    = 'not_matching';

    // value of each key indicates whether to validate the the given key
    static $mandateFieldsExtracted = [
        Entity::UTILITY_CODE      => true,
        Entity::DEBIT_TYPE        => true,
        Entity::FREQUENCY         => true,
        Entity::TYPE              => true,
        Entity::UMRN              => true,
        Entity::AMOUNT            => true,
        Entity::SPONSOR_BANK_CODE => true,
        Entity::REFERENCE_1       => false,
        Entity::REFERENCE_2       => false,
    ];

    public function create(array $input, Customer\Entity $customer): Entity
    {
        $traceInput = $input;
        unset($traceInput[Entity::BANK_ACCOUNT]);

        $this->trace->info(TraceCode::PAPER_MANDATE_CREATE_REQUEST,
            [
                'input'       => $traceInput,
                'customer_id' => $customer->getId()
            ]);

        $paperMandate = (new Entity)->generateId();

        $paperMandate->merchant()->associate($this->merchant);

        $paperMandate->customer()->associate($customer);

        $this->setDefaultValuesForPaperMandate($paperMandate);

        $this->setTerminalDataForPaperMandate($paperMandate);

        $paperMandate->build($input);

        $bankAccount = $this->createBankAccount($input[Entity::BANK_ACCOUNT], $customer);

        $paperMandate->bankAccount()->associate($bankAccount);

        $this->repo->saveOrFail($paperMandate);

        $this->generateMandateForm($paperMandate, $input);

        $this->repo->loadRelations($paperMandate);

        $this->trace->info(TraceCode::PAPER_MANDATE_CREATED,
            [
                'paper_mandate' => $paperMandate->toArrayPublic(),
            ]);

        return $paperMandate;
    }

    public function authenticate(Entity $paperMandate, array $input)
    {
        $this->trace->info(
            TraceCode::PAPER_MANDATE_AUTHENTICATE_REQUEST,
            [
                'paper_mandate' => $paperMandate->toArrayPublic(),
            ]
        );

        $data = $this->extractDataAndValidateFromUploadedForm($paperMandate, $input);

        $uploadedFileId = $data[Entity::UPLOADED_FILE_ID];

        $validationResult = $data[Entity::VALIDATION_RESULT];

        if (empty($validationResult[SubscriptionRegistrationConstants::ERRORS]) === true)
        {
            $paperMandate->setUploadedFileId($uploadedFileId);

            $paperMandate->saveOrFail();
        }

        return $data;
    }

    public function validate(Entity $paperMandate, array $input)
    {
        $this->trace->info(
            TraceCode::PAPER_MANDATE_AUTHENTICATE_REQUEST,
            [
                'paper_mandate' => $paperMandate->toArrayPublic(),
            ]
        );

        $data = $this->extractDataAndValidateFromUploadedForm($paperMandate, $input);

        return $data;
    }

    protected function extractDataAndValidateFromUploadedForm(Entity $paperMandate, array $input)
    {
        $extractedPaperMandateData = (new HyperVerge)->extractPaperMandateFormData($paperMandate, $input);

        $validationResult = $this->validateExtractedData($extractedPaperMandateData, $paperMandate);

        $uploadedFileId = (new FileUploader($paperMandate))->uploadEnhancedForm(
            $extractedPaperMandateData[Entity::ENHANCED_IMAGE]
        );

        $this->trace->info(
            TraceCode::PAPER_MANDATE_FORM_ENHANCED,
            [
                Entity::ID               => $paperMandate->getPublicId(),
                Entity::UPLOADED_FILE_ID => $uploadedFileId,
            ]);

        return [
            Entity::UPLOADED_FILE_ID  => $uploadedFileId,
            Entity::VALIDATION_RESULT => $validationResult,
        ];
    }

    protected function setDefaultValuesForPaperMandate(Entity $paperMandate)
    {
        $startAtAfter = '+' . Constants::PAPER_MANDATE_START_AFTER_DAYS . ' days';

        $startAt = (new Carbon($startAtAfter))->setTime(0, 0, 0, 0);

        $paperMandate->setStartAt($startAt->timestamp);
    }

    protected function setTerminalDataForPaperMandate(Entity $paperMandate)
    {
        $terminal = $this->getTerminalForNachMethod();

        if ($terminal === null)
        {
            throw new LogicException(
                'terminal selected can\'t be null'
            );
        }

        $paperMandate->setTerminalId($terminal->getId());

        if (($this->mode !== Mode::LIVE) and
            ($terminal->getId() === Terminal\Shared::SHARP_RAZORPAY_TERMINAL))
        {
            $paperMandate->setUtilityCode('NACH00000000010000');

            $paperMandate->setSponsorBankCode('RANDOMBANK');
        }
        else
        {
            $paperMandate->setUtilityCode($terminal->getGatewayMerchantId());

            $paperMandate->setSponsorBankCode($terminal->getGatewayAccessCode());
        }
    }

    protected function getTerminalForNachMethod()
    {
        $paymentArray = (new Payment\Entity)->getDummyPaymentArray(Payment\Method::NACH);

        $paymentProcessor = new PaymentProcessor($this->merchant);

        return $paymentProcessor->processAndReturnTerminal($paymentArray);
    }

    protected function validateExtractedData(array & $extractedPaperMandateData, Entity $paperMandate)
    {
        $errors = [];

        $notMatching = [];

        $extractedData = [];

        $notMatching = array_merge($notMatching, $this->validateExtractedBankAccount($extractedPaperMandateData, $paperMandate, $extractedData));

        $notMatching = array_merge($notMatching, $this->validateExtractedMerchant($extractedPaperMandateData, $paperMandate, $extractedData));

        $notMatching = array_merge($notMatching, $this->validateExtractedCustomer($extractedPaperMandateData, $paperMandate, $extractedData));

        $notMatching = array_merge($notMatching, $this->validateExtractedPaperMandateData($extractedPaperMandateData, $paperMandate, $extractedData));

        if (empty($notMatching) === false)
        {
            $errors[self::NOT_MATCHING] = $notMatching;
        }

        return [SubscriptionRegistrationConstants::ERRORS => $errors, Entity::EXTRACTED_DATA => $extractedData];
    }

    protected function validateExtractedPaperMandateData(array $extractedPaperMandateData, Entity $paperMandate, array & $extractedData): array
    {
        $notMatching = [];

        $paperMandate = $paperMandate->toArray();

        foreach (self::$mandateFieldsExtracted as $key => $shouldValidate)
        {
            $extractedData[] = [
                self::KEY             => $key,
                self::EXPECTED_VALUE  => $paperMandate[$key],
                self::EXTRACTED_VALUE => $extractedPaperMandateData[$key]
            ];

            if (($shouldValidate === true) and
                ($paperMandate[$key] !== $extractedPaperMandateData[$key]))
            {
                $notMatching[] = $key;
            }
        }

        if (empty($paperMandate[Entity::FORM_CHECKSUM]) === false)
        {
            $extractedData[] = [
                self::KEY             => Entity::FORM_CHECKSUM,
                self::EXPECTED_VALUE  => $paperMandate[Entity::FORM_CHECKSUM],
                self::EXTRACTED_VALUE => $extractedPaperMandateData[Entity::FORM_CHECKSUM]
            ];

            if ($paperMandate[Entity::FORM_CHECKSUM] !== $extractedPaperMandateData[Entity::FORM_CHECKSUM])
            {
                $notMatching[] = Entity::FORM_CHECKSUM;
            }
        }

        $notMatching = array_merge($notMatching, $this->validateExtractedDates(
            $extractedPaperMandateData,
            $paperMandate,
            $extractedData
        ));

        return $notMatching;
    }

    protected function validateExtractedDates(array $extractedPaperMandateData, array $paperMandateDetails, array & $extractedData): array
    {
        $notMatching = [];

        $extractedData[] = [
            self::KEY             => Entity::CREATED_AT,
            self::EXPECTED_VALUE  => $this->getFormattedDate($paperMandateDetails[Entity::CREATED_AT]),
            self::EXTRACTED_VALUE => $this->getFormattedDate($extractedPaperMandateData[Entity::CREATED_AT])
        ];

        $extractedData[] = [
            self::KEY             => Entity::START_AT,
            self::EXPECTED_VALUE  => $this->getFormattedDate($paperMandateDetails[Entity::START_AT]),
            self::EXTRACTED_VALUE => $this->getFormattedDate($extractedPaperMandateData[Entity::START_AT])
        ];

        $extractedData[] = [
            self::KEY             => Entity::END_AT,
            self::EXPECTED_VALUE  => $this->getFormattedDate($paperMandateDetails[Entity::END_AT]),
            self::EXTRACTED_VALUE => $this->getFormattedDate($extractedPaperMandateData[Entity::END_AT])
        ];

        $untilCancelled = false;

        if (empty($paperMandateDetails[Entity::END_AT]) === true)
        {
            $untilCancelled = true;
        }

        $extractedData[] = [
            self::KEY             => Entity::UNTIL_CANCELLED,
            self::EXPECTED_VALUE  => $untilCancelled,
            self::EXTRACTED_VALUE => $extractedPaperMandateData[Entity::UNTIL_CANCELLED],
        ];

        if ($extractedPaperMandateData[Entity::UNTIL_CANCELLED] !== $untilCancelled)
        {
            $notMatching[] = Entity::UNTIL_CANCELLED;
        }

        return $notMatching;
    }

    protected function getFormattedDate($time)
    {
        if (isset($time) === false)
        {
            return $time;
        }

        return Carbon::createFromTimestamp((int) $time, Timezone::IST)->format('d/m/Y');
    }

    protected function validateExtractedBankAccount(array $extractedPaperMandateData, Entity $paperMandate, array & $extractedData): array
    {
        $notMatching = [];

        $bankAccount = $paperMandate->bankAccount;

        $bankAccountExtracted = $extractedPaperMandateData[Entity::BANK_ACCOUNT];

        if ($bankAccount->getAccountNumber() !== $bankAccountExtracted[BankAccount\Entity::ACCOUNT_NUMBER])
        {
            $notMatching[] = Entity::BANK_ACCOUNT . '.' . BankAccount\Entity::ACCOUNT_NUMBER;
        }

        $extractedData[] = [
            self::KEY             => Entity::BANK_ACCOUNT . '.' . BankAccount\Entity::ACCOUNT_NUMBER,
            self::EXPECTED_VALUE  => $bankAccount->getAccountNumber(),
            self::EXTRACTED_VALUE => $bankAccountExtracted[BankAccount\Entity::ACCOUNT_NUMBER]
        ];

        if ($bankAccount->getIfscCode() !== $bankAccountExtracted[BankAccount\Entity::IFSC_CODE])
        {
            $notMatching[] = Entity::BANK_ACCOUNT . '.' . BankAccount\Entity::IFSC_CODE;
        }

        $extractedData[] = [
            self::KEY             => Entity::BANK_ACCOUNT . '.' . BankAccount\Entity::IFSC_CODE,
            self::EXPECTED_VALUE  => $bankAccount->getIfscCode(),
            self::EXTRACTED_VALUE => $bankAccountExtracted[BankAccount\Entity::IFSC_CODE]
        ];

        if ($bankAccount->getAccountType() !== $bankAccountExtracted[BankAccount\Entity::ACCOUNT_TYPE])
        {
            $notMatching[] = Entity::BANK_ACCOUNT . '.' . BankAccount\Entity::ACCOUNT_TYPE;
        }

        $extractedData[] = [
            self::KEY             => Entity::BANK_ACCOUNT . '.' . BankAccount\Entity::ACCOUNT_TYPE,
            self::EXPECTED_VALUE  => $bankAccount->getAccountType(),
            self::EXTRACTED_VALUE => $bankAccountExtracted[BankAccount\Entity::ACCOUNT_TYPE]
        ];

        return $notMatching;
    }

    protected function validateExtractedMerchant(array $extractedPaperMandateData, Entity $paperMandate, array & $extractedData): array
    {
        $notMatching = [];

        $merchant = $paperMandate->merchant;

        $extractedMerchant = $extractedPaperMandateData[Entity::MERCHANT];

        if (strtoupper($merchant->getName()) !== $extractedMerchant[Merchant\Entity::NAME])
        {
            $notMatching[] = Entity::MERCHANT . '.' . BankAccount\Entity::NAME;
        }

        $extractedData[] = [
            self::KEY             => Entity::MERCHANT . '.' . BankAccount\Entity::NAME,
            self::EXPECTED_VALUE  => strtoupper($merchant->getName()),
            self::EXTRACTED_VALUE => $extractedMerchant[Merchant\Entity::NAME]
        ];

        return $notMatching;
    }

    protected function validateExtractedCustomer(array $extractedPaperMandateData, Entity $paperMandate, array & $extractedData): array
    {
        $notMatching = [];

        $customer = $paperMandate->customer;

        $extractedCustomer = $extractedPaperMandateData[Entity::CUSTOMER];

        if (($extractedCustomer[Entity::TERTIARY_SIGNATURE_PRESENT] === true) and
            ($extractedCustomer[Entity::SECONDARY_SIGNATURE_PRESENT] === false))
        {
            throw new BadRequestValidationFailureException(
                'tertiary signature can\'t be present without secondary signature',
                Entity::SECONDARY_SIGNATURE_PRESENT,
                [
                    'paper_mandate'  => $paperMandate->toArrayPublic(),
                    'extracted_data' => $extractedPaperMandateData,
                ]
            );
        }

        if ($extractedCustomer[Entity::SIGNATURE_PRESENT] === false)
        {
            throw new BadRequestValidationFailureException(
                'signature is not detected in the NACH form',
                Entity::SIGNATURE_PRESENT,
                [
                    'paper_mandate'  => $paperMandate->toArrayPublic(),
                    'extracted_data' => $extractedPaperMandateData,
                ]
            );
        }

        $extractedData[] = [
            self::KEY             => Entity::CUSTOMER . '.' . Customer\Entity::NAME,
            self::EXPECTED_VALUE  => strtoupper($customer->getName()),
            self::EXTRACTED_VALUE => $extractedCustomer[Customer\Entity::NAME]
        ];

        $extractedData[] = [
            self::KEY             => Entity::CUSTOMER . '.' . Customer\Entity::EMAIL,
            self::EXPECTED_VALUE  => $customer->getEmail(),
            self::EXTRACTED_VALUE => $extractedCustomer[Customer\Entity::EMAIL]
        ];

        $extractedData[] = [
            self::KEY             => Entity::CUSTOMER . '.' . Customer\Entity::CONTACT,
            self::EXPECTED_VALUE  => strtoupper($customer->getContact()),
            self::EXTRACTED_VALUE => $extractedCustomer[Customer\Entity::CONTACT]
        ];

        return $notMatching;
    }

    protected function generateMandateForm(Entity $paperMandate, array $input)
    {
        if ((isset($input[Entity::GENERATE_FORM]) === true) and
            ($input[Entity::GENERATE_FORM] === false))
        {
            return;
        }

        $data = (new HyperVerge)->generatePaperMandateForm($paperMandate);

        $generatedFileId = (new FileUploader($paperMandate))->saveCreatedMandateAndFileId($data[Entity::GENERATED_IMAGE]);

        $this->trace->info(
            TraceCode::PAPER_MANDATE_FORM_GENERATED,
            [
                Entity::ID                => $paperMandate->getPublicId(),
                Entity::GENERATED_FILE_ID => $generatedFileId,
            ]);

        $paperMandate->setGeneratedFileId($generatedFileId);

        $paperMandate->setFormChecksum($data[Entity::FORM_CHECKSUM]);

        $paperMandate->saveOrFail();
    }

    function storeImageFileInStorage($base64String, $output_file)
    {
        Storage::put($output_file, base64_decode($base64String));

        return $this->getStorageDir() . $output_file;
    }

    protected function getStorageDir()
    {
        $path = Storage::disk('local')->getAdapter()->getPathPrefix();

        return $path;
    }

    protected function createBankAccount(array $bankAccountInput, Customer\Entity $customer): BankAccount\Entity
    {
        $this->setDefaultValuesForBank($bankAccountInput, $customer);

        $bankAccountCore = new BankAccount\Core();

        $bankAccount = $bankAccountCore->addOrUpdateBankAccountForCustomer($bankAccountInput, $customer);

        return $bankAccount;
    }

    protected function setDefaultValuesForBank(array & $bankInput, Customer\Entity $customer)
    {
        if ((array_key_exists(BankAccount\Entity::BENEFICIARY_EMAIL, $bankInput) == false) and
            (empty($customer->getEmail()) === false))
        {
            $bankInput[BankAccount\Entity::BENEFICIARY_EMAIL] = $customer->getEmail();
        }

        if ((array_key_exists(BankAccount\Entity::BENEFICIARY_MOBILE, $bankInput) == false) and
            (empty($customer->getContact()) === false))
        {
            $bankInput[BankAccount\Entity::BENEFICIARY_MOBILE] = $customer->getContact();
        }

        if (isset($bankInput[BankAccount\Entity::ACCOUNT_TYPE]) === false)
        {
            $bankInput[BankAccount\Entity::ACCOUNT_TYPE] = BankAccount\AccountType::SAVINGS;
        }
    }
}
