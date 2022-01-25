<?php

namespace RZP\Models\PaperMandate;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;

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

        $input[self::BANK_NAME] = substr($bankAccount->getBankName(), 0, 45);

        $input[self::ACCOUNT_TYPE] = $this->getAccountType($paperMandate);

        $input[self::IFSCCode] = $bankAccount->getIfscCode();

        $input[self::ACCOUNT_NUMBER] = stringify($bankAccount->getAccountNumber());

        if (empty($bankAccount->getBeneficiaryName()) === false)
        {
            $input[self::PRIMARY_ACCOUNT_HOLDER] = substr($bankAccount->getBeneficiaryName(), 0, 32);
        }

        if (empty($bankAccount->getBeneficiaryMobile()) === false)
        {
            $input[self::PHONE_NUMBER] = $this->getFormattedContactNumber($bankAccount->getBeneficiaryMobile());
        }

        if (empty($bankAccount->getBeneficiaryEmail()) === false)
        {
            $input[self::EMAIL_ID] = substr($bankAccount->getBeneficiaryEmail(), 0, 40);
        }

        $input[self::COMPANY_NAME] = substr($this->getCompanyName($paperMandate), 0, 50);

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

    public function getCompanyName(Entity $paperMandate): string
    {
        $terminal = $paperMandate->terminal;

        // ICICI and CITI seem to have different requirements for name
        if ($terminal->getGatewayAcquirer() === Gateway::ACQUIRER_ICIC)
        {
            $merchant = $paperMandate->merchant;

            $label = $merchant->getBillingLabel();

            $filteredLabel = preg_replace('/[^a-zA-Z]+/', '', $label);

            $name = str_limit($filteredLabel, 20, '');
        }
        else
        {
            $merchant = $terminal->merchant;

            if ($merchant->getId() === Constants::SHARED_TERMINAL_MERCHANT_ID)
            {
                return Constants::SHARED_TERMINAL_MERCHANT_NAME;
            }

            $name = $merchant->getName();
        }

        return $name;
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
            case BankAccount\AccountType::CC:
                return self::CC;
            case BankAccount\AccountType::NRE:
                return self::SB_NRE;
            case BankAccount\AccountType::NRO:
                return self::SB_NRO;
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
