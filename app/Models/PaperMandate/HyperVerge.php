<?php

namespace RZP\Models\PaperMandate;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class HyperVerge extends Base\Core
{
    const UMRN                     = 'UMRN';
    const NACH_DATE                = 'nachDate';
    const SPONSOR_CODE             = 'sponsorCode';
    const UTILITY_CODE             = 'utilityCode';
    const BANK_NAME                = 'bankName';
    const ACCOUNT_TYPE             = 'accountType';
    const ACCOUNT_NUMBER           = 'accountNumber';
    const IFSCCode                 = 'IFSCCode';
    const MICR                     = 'MICR';
    const COMPANY_NAME             = 'companyName';
    const FREQUENCY                = 'frequency';
    const AMOUNT_IN_NUMBER         = 'amountInNumber';
    const AMOUNT_IN_WORDS          = 'amountInWords';
    const DEBIT_TYPE               = 'debitType';
    const START_DATE               = 'startDate';
    const END_DATE                 = 'endDate';
    const UNTIL_CANCELLED          = 'untilCanceled';
    const NACH_TYPE                = 'NACHType';
    const PHONE_NUMBER             = 'phoneNumber';
    const EMAIL_ID                 = 'emailId';
    const REFERENCE_1              = 'reference1';
    const REFERENCE_2              = 'reference2';
    const PRIMARY_ACCOUNT_HOLDER   = 'primaryAccountHolder';
    const SECONDARY_ACCOUNT_HOLDER = 'secondaryAccountHolder';
    const TERTIARY_ACCOUNT_HOLDER  = 'tertiaryAccountHolder';
    const LOGO                     = 'logo';

    const OUTPUT_IMAGE             = 'outputImage';

    const SIGNATURE_PRESENT           = 'signaturePresentPrimary';
    const SECONDARY_SIGNATURE_PRESENT = 'signaturePresentSecondary';
    const TERTIARY_SIGNATURE_PRESENT  = 'signaturePresentTertiary';

    const IMAGE                    = 'base64AlignedJPEG';

    const VALUE                    = 'value';
    const TO_BE_REVIEWED           = 'to-be-reviewed';
    const DETAILS                  = 'details';

    const FORM_CHECKSUM            = 'uid';
    const SET_FORM_CHECKSUM        = 'setUid';
    const DETECT_FORM_CHECKSUM     = 'detectUid';

    // Bank account types
    const SB     = 'SB';
    const CA     = 'CA';
    const CC     = 'CC';
    const SB_NRO = 'SB-NRO';
    const SB_NRE = 'SB-NRE';
    const OTHERS = 'Others';

    // Debit types
    const MAXIMUM_AMOUNT = 'maximumAmount';
    const FIXED_AMOUNT   = 'fixedAmount';

    // Frequency Mapping
    static $frequencyMapping = [
        Frequency::AS_AND_WHEN_PRESENTED => 'whenPresent',
        Frequency::YEARLY                => 'yearly'
    ];

    static $frequencyMappingExtracted = [
        'whenPresented' => Frequency::AS_AND_WHEN_PRESENTED,
        'yearly'      => Frequency::YEARLY,
    ];

    static $toBeReviewed = [
        self::UMRN,
        self::NACH_DATE,
        self::SPONSOR_CODE,
        self::UTILITY_CODE,
        self::BANK_NAME,
        self::ACCOUNT_TYPE,
        self::ACCOUNT_NUMBER,
        self::IFSCCode,
        self::MICR,
        self::COMPANY_NAME,
        self::FREQUENCY,
        self::AMOUNT_IN_NUMBER,
        self::DEBIT_TYPE,
        self::START_DATE,
        self::END_DATE,
        self::UNTIL_CANCELLED,
        self::NACH_TYPE,
    ];

    public function generatePaperMandateForm(Entity $paperMandate): array
    {
        $input = $this->getGenerateMandateInput($paperMandate);

        $response = $this->app->hyperVerge->generateNACH($input, $paperMandate);

        return [
            Entity::GENERATED_IMAGE => $response[self::OUTPUT_IMAGE],
            Entity::FORM_CHECKSUM   => $response[self::FORM_CHECKSUM],
        ];
    }

    public function extractPaperMandateFormData(Entity $paperMandate, array $input): array
    {
        $extractedMandateData = $this->app->hyperVerge->extractNACHWithOutputImage($input, $paperMandate);

        $this->validateAndFormatExtractedData($extractedMandateData, $paperMandate);

        $traceExtractedMandateData = $this->getExtractedDataToTrace($extractedMandateData);

        unset($traceExtractedMandateData[self::DETAILS][self::IMAGE]);

        $this->trace->info(TraceCode::PAPER_MANDATE_EXTRACTED_DATA,
            [
                'paper_mandate'  => $paperMandate->toArrayPublic(),
                'extracted_data' => $traceExtractedMandateData,
            ]);

        $mappedMandateData = $this->mapFromHyperVerge($extractedMandateData[self::DETAILS]);

        return $mappedMandateData;
    }

    public function validateAndFormatExtractedData(array & $extractedMandateData, Entity $paperMandate)
    {
        foreach ($extractedMandateData[self::DETAILS] as $key => $item)
        {
            if ($key === self::IMAGE)
            {
                continue;
            }

            $extractedMandateData[self::DETAILS][$key] = $item[self::VALUE];

            // not considering confidence score for now
//            if ((isset($item[self::TO_BE_REVIEWED]) === true) and
//                ($item[self::TO_BE_REVIEWED] === 'yes') and
//                (in_array($key, self::$toBeReviewed) === true))
//            {
//                $data = [
//                    'paper_mandate'  => $paperMandate->toArray(),
//                    'extracted_data' => $extractedMandateData,
//                ];
//
//                throw new BadRequestValidationFailureException(
//                    'image is not clear',
//                    $key,
//                    $data
//                );
//            }
        }
    }

    public function mapFromHyperVerge(array $extractedMandateData): array
    {
        $mappedData = [];

        $mappedData[Entity::CUSTOMER][Customer\Entity::NAME]     = $extractedMandateData[self::PRIMARY_ACCOUNT_HOLDER];
        $mappedData[Entity::CUSTOMER][Customer\Entity::EMAIL]    = $extractedMandateData[self::EMAIL_ID];
        $mappedData[Entity::CUSTOMER][Customer\Entity::CONTACT]  = $extractedMandateData[self::PHONE_NUMBER];
        $mappedData[Entity::CUSTOMER][Entity::SIGNATURE_PRESENT] = $extractedMandateData[self::SIGNATURE_PRESENT] === 'no' ? false : true;

        $mappedData[Entity::CUSTOMER][Entity::SECONDARY_SIGNATURE_PRESENT] = $extractedMandateData[self::SECONDARY_SIGNATURE_PRESENT] === 'no' ? false : true;
        $mappedData[Entity::CUSTOMER][Entity::TERTIARY_SIGNATURE_PRESENT]  = $extractedMandateData[self::TERTIARY_SIGNATURE_PRESENT] === 'no' ? false : true;

        $mappedData[Entity::SECONDARY_ACCOUNT_HOLDER]            = $extractedMandateData[self::SECONDARY_ACCOUNT_HOLDER];
        $mappedData[Entity::TERTIARY_ACCOUNT_HOLDER]             = $extractedMandateData[self::TERTIARY_ACCOUNT_HOLDER];

        $mappedData[Entity::BANK_ACCOUNT][BankAccount\Entity::NAME]           = $extractedMandateData[self::BANK_NAME];
        $mappedData[Entity::BANK_ACCOUNT][BankAccount\Entity::ACCOUNT_NUMBER] = $extractedMandateData[self::ACCOUNT_NUMBER];
        $mappedData[Entity::BANK_ACCOUNT][BankAccount\Entity::IFSC_CODE]      = $extractedMandateData[self::IFSCCode];
        $mappedData[Entity::BANK_ACCOUNT][BankAccount\Entity::ACCOUNT_TYPE]   = $extractedMandateData[self::ACCOUNT_TYPE];

        $this->formatBankAccountFromExtraction($mappedData);

        $mappedData[Entity::MERCHANT][Merchant\Entity::NAME] = $extractedMandateData[self::COMPANY_NAME];

        $mappedData[Entity::UTILITY_CODE]                    = $extractedMandateData[self::UTILITY_CODE];

        $mappedData[Entity::DEBIT_TYPE]                      = $this->getFormattedDebitTypeFromExtracted($extractedMandateData[self::DEBIT_TYPE]);

        $mappedData[Entity::FREQUENCY]                       = self::$frequencyMappingExtracted[$extractedMandateData[self::FREQUENCY]] ?? '';

        $mappedData[Entity::TYPE]                            = $extractedMandateData[self::NACH_TYPE];

        $mappedData[Entity::UMRN]                            = empty($extractedMandateData[self::UMRN]) ? null : $extractedMandateData[self::UMRN];

        $mappedData[Entity::AMOUNT]                          = $this->getFormattedAmountFromExtracted($extractedMandateData[self::AMOUNT_IN_NUMBER]);

        $mappedData[Entity::SPONSOR_BANK_CODE]               = $extractedMandateData[self::SPONSOR_CODE];

        $mappedData[Entity::REFERENCE_1] = $extractedMandateData[self::REFERENCE_1];
        $mappedData[Entity::REFERENCE_2] = $extractedMandateData[self::REFERENCE_2];

        $mappedData[Entity::CREATED_AT]      = $extractedMandateData[self::NACH_DATE];

        $mappedData[Entity::START_AT]        = $extractedMandateData[self::START_DATE];

        $mappedData[Entity::END_AT]          = $extractedMandateData[self::END_DATE];

        $mappedData[Entity::UNTIL_CANCELLED] = $extractedMandateData[self::UNTIL_CANCELLED] === "true" ? true : false;

        $this->formatDatesExtracted($mappedData);

        $mappedData[Entity::ENHANCED_IMAGE]  = $extractedMandateData[self::IMAGE];

        $mappedData[Entity::FORM_CHECKSUM]   = $extractedMandateData[self::FORM_CHECKSUM];

        return $mappedData;
    }

    protected function getExtractedDataToTrace(array & $extractedData)
    {
        $extractedDataToTrace = $extractedData;

        unset($extractedDataToTrace[self::DETAILS][self::IMAGE]);
        unset($extractedDataToTrace[self::DETAILS][self::BANK_NAME]);
        unset($extractedDataToTrace[self::DETAILS][self::ACCOUNT_NUMBER]);
        unset($extractedDataToTrace[self::DETAILS][self::IFSCCode]);
        unset($extractedDataToTrace[self::DETAILS][self::ACCOUNT_TYPE]);

        return $extractedDataToTrace;
    }

    private function getFormattedAmountFromExtracted(int $amount): int
    {
        return $amount * 100;
    }

    private function formatDatesExtracted(array & $extracted)
    {
        if (isset($extracted[Entity::CREATED_AT]) === true)
        {
            $extracted[Entity::CREATED_AT] = $this->formatDateExtracted($extracted[Entity::CREATED_AT]);
        }

        if (isset($extracted[Entity::START_AT]) === true)
        {
            $extracted[Entity::START_AT] = $this->formatDateExtracted($extracted[Entity::START_AT]);
        }

        if (empty($extracted[Entity::END_AT]) === false)
        {
            $extracted[Entity::END_AT] = $this->formatDateExtracted($extracted[Entity::END_AT]);
        }
    }

    private function formatDateExtracted(string $date)
    {
        if (empty($date) === true)
        {
            return $date;
        }

        try
        {
            $dt = Carbon::createFromFormat('d/m/Y', $date);

            return $dt->getTimestamp();
        }
        catch (\InvalidArgumentException $e)
        {
            return $date;
        }
    }

    private function formatBankAccountFromExtraction(array & $extractedData)
    {
        $accountType = $extractedData[Entity::BANK_ACCOUNT][BankAccount\Entity::ACCOUNT_TYPE];

        switch ($accountType)
        {
            case self::SB:
                $formattedAccountType = BankAccount\AccountType::SAVINGS;
                break;
            case self::CA:
                $formattedAccountType = BankAccount\AccountType::CURRENT;
                break;
            default:
                $formattedAccountType = '';
                break;
        }

        $extractedData[Entity::BANK_ACCOUNT][BankAccount\Entity::ACCOUNT_TYPE] = $formattedAccountType;
    }

    private function getGenerateMandateInput(Entity $paperMandate): array
    {
        $input = [];

        $bankAccount = $paperMandate->bankAccount;

        $paperMandateDetails = $paperMandate->toArray();

        if (isset($paperMandateDetails[Entity::UMRN]) === true)
        {
            $input[self::UMRN] = $paperMandateDetails[Entity::UMRN];
        }

        $input[self::NACH_DATE] = $this->getFormattedDate($paperMandateDetails[Entity::CREATED_AT]);

        $input[self::SPONSOR_CODE] = $paperMandateDetails[Entity::SPONSOR_BANK_CODE];

        $input[self::UTILITY_CODE] = $paperMandateDetails[Entity::UTILITY_CODE];

        $input[self::BANK_NAME] = $bankAccount->getBankName();

        $input[self::ACCOUNT_TYPE] = $this->getAccountType($paperMandate);

        $input[self::IFSCCode] = $bankAccount->getIfscCode();

        $input[self::ACCOUNT_NUMBER] = stringify($bankAccount->getAccountNumber());

        if (empty($bankAccount->getBeneficiaryName()) === false)
        {
            $input[self::PRIMARY_ACCOUNT_HOLDER] = $bankAccount->getBeneficiaryName();
        }

        if (empty($bankAccount->getBeneficiaryMobile()) === false)
        {
            $input[self::PHONE_NUMBER] = $this->getFormattedContactNumber($bankAccount->getBeneficiaryMobile());
        }

        if (empty($bankAccount->getBeneficiaryEmail()) === false)
        {
            $input[self::EMAIL_ID] = $bankAccount->getBeneficiaryEmail();
        }

        $input[self::COMPANY_NAME] = $this->getCompanyName($paperMandate);

        $input[self::FREQUENCY] = self::$frequencyMapping[$paperMandateDetails[Entity::FREQUENCY]];

        $input[self::AMOUNT_IN_NUMBER] = $this->getFormattedAmountInNumber($paperMandateDetails[Entity::AMOUNT]);

        $input[self::AMOUNT_IN_WORDS] = $this->getFormattedAmountInWords($paperMandateDetails[Entity::AMOUNT]);

        $input[self::DEBIT_TYPE] = $this->getFormattedDebitType($paperMandateDetails[Entity::DEBIT_TYPE]);

        $input[self::START_DATE] = $this->getFormattedDate($paperMandateDetails[Entity::START_AT]);

        if (isset($paperMandateDetails[Entity::END_AT]) === true)
        {
            $input[self::END_DATE] = $this->getFormattedDate($paperMandateDetails[Entity::END_AT]);
        }
        else
        {
            $input[self::UNTIL_CANCELLED] = 'true';
        }

        $input[self::NACH_TYPE] = $paperMandateDetails[Entity::TYPE];

        if (isset($paperMandateDetails[Entity::REFERENCE_1]) === true)
        {
            $input[self::REFERENCE_1] = $paperMandateDetails[Entity::REFERENCE_1];
        }

        if (isset($paperMandateDetails[Entity::REFERENCE_2]) === true)
        {
            $input[self::REFERENCE_2] = $paperMandateDetails[Entity::REFERENCE_2];
        }

        if (isset($paperMandateDetails[Entity::SECONDARY_ACCOUNT_HOLDER]) === true)
        {
            $input[self::SECONDARY_ACCOUNT_HOLDER] = $paperMandateDetails[Entity::SECONDARY_ACCOUNT_HOLDER];
        }

        if (isset($paperMandateDetails[Entity::TERTIARY_ACCOUNT_HOLDER]) === true)
        {
            $input[self::TERTIARY_ACCOUNT_HOLDER] = $paperMandateDetails[Entity::TERTIARY_ACCOUNT_HOLDER];
        }

        $input[self::SET_FORM_CHECKSUM] = "yes";

        $logoUrl = $this->merchant->getLogoUrl();

        if (isset($logoUrl) === true)
        {
            //todo pass merchant url to hyperverge.
        }

        return $input;
    }

    protected function getCompanyName(Entity $paperMandate): string
    {
        $merchant = $paperMandate->terminal->merchant;

        if ($merchant->getId() === Constants::SHARED_TERMINAL_MERCHANT_ID)
        {
            return Constants::SHARED_TERMINAL_MERCHANT_NAME;
        }

        return $merchant->getName();
    }

    protected function getFormattedContactNumber($number)
    {
        return preg_replace('/^\+91/', '', $number);
    }

    protected function getFormattedDebitType(string $debitType)
    {
        switch ($debitType)
        {
            case DebitType::FIXED_AMOUNT:
                return self::FIXED_AMOUNT;
            case DebitType::MAXIMUM_AMOUNT:
                return self::MAXIMUM_AMOUNT;
            default:
                return null;
        }
    }

    protected function getFormattedDebitTypeFromExtracted(string $debitType)
    {
        switch ($debitType)
        {
            case self::FIXED_AMOUNT:
                return DebitType::FIXED_AMOUNT;
            case self::MAXIMUM_AMOUNT:
                return DebitType::MAXIMUM_AMOUNT;
            default:
                return null;
        }
    }

    protected function getFormattedDate(string $time)
    {
        return Carbon::createFromTimestamp($time, Timezone::IST)->format('d/m/Y');
    }

    protected function getAccountType(Entity $paperMandate)
    {
        switch ($paperMandate->bankAccount->getAccountType())
        {
            case BankAccount\AccountType::SAVINGS:
                return self::SB;
            case BankAccount\AccountType::CURRENT:
                return self::CA;
            default:
                return null;
        }
    }

    protected function getFormattedAmountInNumber(int $amount): string
    {
        // not considering paise
        return strval((int)($amount / 100));
    }

    public function getFormattedAmountInWords(int $amount): string
    {
        // not considering paise
        $amount -= $amount % 100;

        $formattedAmount = $this->getAmountInWords($amount);

        return $formattedAmount;
    }

    public function getAmountInWords(int $amount)
    {
        $paise = $amount % 100;

        $amount -= $paise;

        $amount /= 100;

        $amountInWords = $paise > 0 ? 'rupees and ' . $this->getNumberInWord($paise) . ' paise' : 'rupees';

        $counterMap = [
            0 => '',
            1 => 'thousand',
            2 => 'lakh',
            3 => 'crore',
        ];

        for ($i = 0; $amount > 0; $i++)
        {
            $divider = $i === 0 ? 1000 : 100;

            $numberInWord = $this->getNumberInWord($amount % $divider);

            if ($i > 0)
            {
                $numberInWord = empty($numberInWord) === false ?
                    $numberInWord . ' ' . ($counterMap[$i] ?? '') :
                    $numberInWord;
            }

            $amount -= $amount % $divider;

            $amount /= $divider;

            if (empty($numberInWord) === false)
            {
                $amountInWords = empty($amountInWords) === true ?
                    $numberInWord : $numberInWord . ' ' . $amountInWords;
            }
        }

        return $amountInWords;
    }

    protected function getNumberInWord(int $number)
    {
        if ($number === 0)
        {
            return '';
        }

        $numberToWordMap = [
            0   => 'zero',
            1   => 'one',
            2   => 'two',
            3   => 'three',
            4   => 'four',
            5   => 'five',
            6   => 'six',
            7   => 'seven',
            8   => 'eight',
            9   => 'nine',
            10  => 'ten',
            11  => 'eleven',
            12  => 'twelve',
            13  => 'thirteen',
            14  => 'fourteen',
            15  => 'fifteen',
            16  => 'sixteen',
            17  => 'seventeen',
            18  => 'eighteen',
            19  => 'nineteen',
            20  => 'twenty',
            30  => 'thirty',
            40  => 'forty',
            50  => 'fifty',
            60  => 'sixty',
            70  => 'seventy',
            80  => 'eighty',
            90  => 'ninety',
            100 => 'hundred',
        ];

        if ($number < 20)
        {
            return $numberToWordMap[$number];
        }

        if ($number < 100)
        {
            $remaining = $this->getNumberInWord($number % 10);

            $numberInWord = $numberToWordMap[$number - ($number % 10)];

            return empty($remaining) === true ?
                $numberInWord :
                $numberInWord . ' ' . $remaining;
        }

        $remaining = $this->getNumberInWord($number % 100);

        $numberInWord = $this->getNumberInWord($number / 100) . ' hundred';

        return empty($remaining) === true ?
            $numberInWord :
            $numberInWord . ' ' . $remaining;
    }
}
