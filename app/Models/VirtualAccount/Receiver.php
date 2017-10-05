<?php

namespace RZP\Models\VirtualAccount;

use App;
use Lib\CRC16;
use RZP\Exception;
use RZP\Base\Luhn;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\BharatQr\Entity as BharatQr;
use RZP\Models\BankAccount\Entity as BankAccount;

class Receiver
{
    const BANK_ACCOUNT      = 'bank_account';
    // const VPA               = 'vpa';
    const BHARAT_QR         = 'bharat_qr';

    const TYPES = [
        self::BANK_ACCOUNT,
        // self::VPA,
        self::BHARAT_QR,
    ];

    const ROOT_LENGTH               = 4;
    // Handle length can be 3 also
    // const HANDLE_LENGTH             = 4;
    const DESCRIPTOR_LENGTH         = 9;
    const ACCOUNT_NUMBER_LENGTH     = 17;

    // No 0s and Os
    // No 1s and Is
    // No 5s and Ss
    // No 8s and Bs
    // No 2s and Zs
    const ACCOUNT_NUMBER_CHAR_SPACE       = '34679ACDEFGHJKLMNPQRTUVWXY';
    const MAX_ACCOUNT_GENERATION_ATTEMPTS = 10;

    const NUMBER_SPACE                    = '0123456789';

    protected $app;
    protected $merchant;
    protected $name;
    protected $descriptor;
    protected $trace;
    protected $repo;
    protected $mode;

    public function __construct(
        Merchant\Entity $merchant,
        string $name = null,
        string $descriptor = null)
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->merchant = $merchant;

        $this->name = $name;

        $this->descriptor = $descriptor;
    }

    public static function areTypesValid(array $receiverTypes): bool
    {
        $invalidTypes = array_diff($receiverTypes, self::TYPES);

        return (empty($invalidTypes) === true);
    }

    public function buildBankAccount(Entity $virtualAccount)
    {
        $bankAccount = new BankAccount;

        $bankAccountInput = $this->generateBankAccountInput();

        $bankAccount = $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        $bankAccount->merchant()->associate($this->merchant);

        $bankAccount->associateVirtualAccount($virtualAccount);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    public function buildBharatQr(Entity $virtualAccount)
    {
        $bharatQr = new BharatQr;

        $provider = Provider::BHARAT_QR;

        $visaIdentifier = $this->generateMerchantIdentifierForProvider($provider, 'visa');

        $mastercardIdentifier = $this->generateMerchantIdentifierForProvider($provider, 'mastercard');

        if (empty($virtualAccount->getAmountExpected()) === false)
        {
            $input[BharatQr::AMOUNT] = $virtualAccount->getAmountExpected();
        }

        $input[BharatQr::VISA_IDENTIFIER] = $visaIdentifier;

        $input[BharatQr::MASTER_CARD_IDENTIFIER] = $mastercardIdentifier;

        $defaultDetails = Provider::DEFAULT_DETAILS[$provider];

        $input = array_merge($input, $defaultDetails);

        $bharatQr = $bharatQr->build($input, 'addBharatQr');

        $bharatQr->generateId();

        $qrString = $this->buildDynamicQrString($bharatQr);

        $bharatQr->setQrString($qrString);

        $bharatQr->merchant()->associate($this->merchant);

        $bharatQr->source()->associate($virtualAccount);

        $this->repo->saveOrFail($bharatQr);

        return $bharatQr;
    }

    protected function buildDynamicQrString($bharatQr)
    {
        $qrString = $bharatQr->getQrString();

        $qrString .= $bharatQr->getDynamicTagString() .'6304';

        $crc = (new CRC16)->calculateCrc($qrString);

        $qrString .= $crc;

        return $qrString;
    }

    //network could be visa , mastercard or rupay
    protected function generateMerchantIdentifierForProvider(string $provider, string $network)
    {
        $acquirerCode = Provider::getAcquirerCode($provider, $network);

        $identifier  = $acquirerCode . '0' . $this->padWithRandomNumbers(7);

        return $identifier . Luhn::computeCheckDigit($identifier);
    }


    protected function generateBankAccountInput()
    {
        $provider = $this->selectProvider();

        $details = Provider::DEFAULT_DETAILS[$provider];

        $accountNumber = $this->generateAccountNumberForProvider($provider);

        $merchantDetails = [
            BankAccount::ACCOUNT_NUMBER     => $accountNumber,
            BankAccount::BENEFICIARY_NAME   => $this->name,
        ];

        return array_merge($details, $merchantDetails);
    }

    protected function selectProvider()
    {
        $provider = Provider::KOTAK;

        if ($this->mode === Mode::TEST)
        {
            $provider = Provider::DASHBOARD;
        }

        return $provider;
    }

    protected function generateAccountNumberForProvider(string $provider)
    {
        $bankCode = Provider::getBankCode($provider);

        $root = $this->getRoot($provider);

        $attempts = 0;

        while ($attempts <= self::MAX_ACCOUNT_GENERATION_ATTEMPTS)
        {
            $accountNumber = $this->generateNewAccountNumberWithRoot($root);

            $existingAccount = $this->repo->bank_account
                                    ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $bankCode);

            if ($existingAccount === null)
            {
                return $accountNumber;
            }

            $attempts++;
        }

        // This should never happen
        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_UNAVAILABLE);
    }

    /**
     * Generates unique account number for a given root
     *
     * Max length of account number is 18 characters. The three parts of
     * the account number are the root, the handle, and the descriptor.
     *
     * Each provider has some pre-decided roots or prefixes that must be used.
     * We use the merchant's handle if available, and VA descriptor if given,
     * otherwise use random characters.
     *
     * @param  string $root Root given by for provider of Virtual a/c services
     *
     * @return string Unique account number
     * @throws Exception\LogicException
     */
    protected function generateNewAccountNumberWithRoot(string $root)
    {
        $handle = $this->getHandle($root);

        $descriptor = $this->getDescriptor($handle);

        $accountNumber = strtoupper($root . $handle . $descriptor);

        $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_NUMBER_GENERATED,
                [
                    'root'          => $root,
                    'handle'        => $handle,
                    'descriptor'    => $descriptor,
                    'accountNumber' => $accountNumber,
                ]
            );

        if (strlen($accountNumber) > self::ACCOUNT_NUMBER_LENGTH)
        {
            throw new Exception\LogicException(
                'Error in account number generation.',
                null,
                [
                    'account_number'    => $accountNumber,
                    'max_length'        => self::ACCOUNT_NUMBER_LENGTH,
                ]);
        }

        return $accountNumber;
    }

    // If handle is not set, we use the default root (RAZO),
    // and later add the default handle.
    //
    // If handle is set, we use the standard root (RZRP)
    //
    protected function getRoot(string $provider)
    {
        $root = Provider::ROOT[$provider]['standard'];

        if ($this->merchant->getHandle() === null)
        {
            $root = Provider::ROOT[$provider]['default'];
        }

        return $root;
    }

    // If handle is not set, we use the default root (RAZO),
    // and now add the default handle (RPAY).
    //
    // If handle is set, we use the standard root (RZRP),
    // and add the chosen handle.
    //
    protected function getHandle(string $root)
    {
        $merchantHandle = $this->merchant->getHandle();

        if ($merchantHandle === null)
        {
            $merchantHandle = $this->getDefaultHandle($root);
        }

        return $merchantHandle;
    }

    // If handle is not set, descriptor is completely random.
    // If handle is set, we use the given desriptor.
    //
    // Merchant handles can be 3 or 4 characters. Max is 17,
    // so we pad with 17-4-n characters, i.e. 10 or 9.
    //
    protected function getDescriptor(string $handle)
    {
        $descriptor = $this->descriptor;

        if (($this->merchant->getHandle() === null) or
            ($descriptor === null))
        {
            $totalLength = self::ACCOUNT_NUMBER_LENGTH;

            $availableLength = $totalLength - self::ROOT_LENGTH - strlen($handle);

            $descriptor = $this->padWithRandomDigits($availableLength);
        }

        return $descriptor;
    }

    protected function getDefaultHandle(string $root)
    {
        return Provider::DEFAULT_HANDLE_MAPPING[$root];
    }

    protected function padWithRandomNumbers(int $desiredLength, string $str = '')
    {
        $requiredLength = $desiredLength - strlen($str);

        $pad = '';

        $charSpace = str_split(self::NUMBER_SPACE);

        while (strlen($pad) < $requiredLength)
        {
            $pad .= $charSpace[array_rand($charSpace)];
        }

        $str = $pad . $str;

        return $str;
    }


    protected function padWithRandomDigits(int $desiredLength, $str = '')
    {
        $requiredLength = $desiredLength - strlen($str);

        $pad = '';

        $charSpace = str_split(self::ACCOUNT_NUMBER_CHAR_SPACE);

        while (strlen($pad) < $requiredLength)
        {
            $pad .= $charSpace[array_rand($charSpace)];
        }

        $str = $pad . $str;

        return $str;
    }
}
