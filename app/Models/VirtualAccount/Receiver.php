<?php

namespace RZP\Models\VirtualAccount;

use App;
use RZP\Exception;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount\Entity as BankAccount;

class Receiver
{
    const BANK_ACCOUNT      = 'bank_account';
    // const VPA               = 'vpa';
    const QR_CODE           = 'qr_code';

    const TYPES = [
        self::BANK_ACCOUNT,
        // self::VPA,
        self::QR_CODE,
    ];

    const ROOT_LENGTH               = 4;
    // Handle length can be 3 also
    const STANDARD_HANDLE_LENGTH    = 4;
    const DESCRIPTOR_LENGTH         = 9;
    const ACCOUNT_NUMBER_LENGTH     = 17;

    const DEFAULT_BANK_ACCOUNT_OPTIONS = [
        self::DESCRIPTOR => null,
        self::NUMERIC    => true,
    ];

    const NUMERIC    = 'numeric';
    const DESCRIPTOR = 'descriptor';

    // No 0s and Os
    // No 1s and Is
    // No 5s and Ss
    // No 8s and Bs
    // No 2s and Zs
    const ACCOUNT_NUMBER_ALPHANUM_CHAR_SPACE = '34679ACDEFGHJKLMNPQRTUVWXY';
    const ACCOUNT_NUMBER_NUM_CHAR_SPACE      = '0123456789';
    const MAX_ACCOUNT_GENERATION_ATTEMPTS    = 10;

    protected $app;
    protected $merchant;
    protected $name;
    protected $descriptor;
    protected $trace;
    protected $repo;
    protected $mode;
    protected $numeric;

    public function __construct(
        Merchant\Entity $merchant,
        string $name = null)
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
    }

    public static function areTypesValid(array $receiverTypes): bool
    {
        $invalidTypes = array_diff($receiverTypes, self::TYPES);

        return (empty($invalidTypes) === true);
    }

    public function buildBankAccount(Entity $virtualAccount, array $options): BankAccount
    {
        $bankAccount = new BankAccount;

        $bankAccountInput = $this->generateBankAccountInput($options);

        $bankAccount = $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        $bankAccount->merchant()->associate($this->merchant);

        $bankAccount->associateVirtualAccount($virtualAccount);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    public function buildQrCode(Entity $virtualAccount): QrCode\Entity
    {
        $qrCode = new QrCode\Entity;

        $input = $this->getQrCodeEntityParams($virtualAccount);

        $qrCode = $qrCode->build($input);

        $qrCode->generateId();

        $qrCode->merchant()->associate($this->merchant);

        $qrCode->source()->associate($virtualAccount);

        $qrCode = $qrCode->generateQrString();

        $this->repo->saveOrFail($qrCode);

        return $qrCode;
    }

    protected function getQrCodeEntityParams(Entity $virtualAccount): array
    {
        $input = [
            // For now it is set bharat qr as default
            QrCode\Entity::PROVIDER  => Provider::BHARAT_QR,
            QrCode\Entity::AMOUNT    => $virtualAccount->getAmountExpected(),
        ];

        return $input;
    }

    protected function generateBankAccountInput(array $options): array
    {
        $provider = $this->selectProvider();

        $details = Provider::DEFAULT_DETAILS[$provider];

        $this->setOptions($options);

        $accountNumber = $this->generateAccountNumberForProvider($provider);

        $merchantDetails = [
            BankAccount::ACCOUNT_NUMBER     => $accountNumber,
            BankAccount::BENEFICIARY_NAME   => $this->name,
        ];

        return array_merge($details, $merchantDetails);
    }

    /**
     * Numeric accounts are the default.
     * Descriptor cannot be used with numeric.
     * Merchants without handle set are not allowed non-numeric accounts
     *
     * @param array $options
     */
    protected function setOptions(array $options)
    {
        $options = array_merge(self::DEFAULT_BANK_ACCOUNT_OPTIONS, $options);

        $this->numeric = boolval($options[self::NUMERIC]);

        $this->descriptor = $options[self::DESCRIPTOR];

        $handle = $this->merchant->getHandle();

        Validator::validateDescriptor($this->descriptor, $handle);

        if (($this->numeric === true) and
            ($this->descriptor !== null))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Descriptor cannot be used for numeric accounts.');
        }
        else if (($this->numeric === false) and
                 ($handle === null))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Alphabetical account numbers cannot be used as merchant handle is not set.');
        }
    }

    protected function selectProvider(): string
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

        $attempts = 0;

        while ($attempts <= self::MAX_ACCOUNT_GENERATION_ATTEMPTS)
        {
            $accountNumber = $this->generateNewAccountNumberForProvider($provider);

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
    protected function generateNewAccountNumberForProvider(string $provider): string
    {
        $root = $this->getRoot($provider);

        $handle = $this->getHandle($root);

        $descriptor = $this->getDescriptor($handle, $root);

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

    /**
     * By default, we use the default numeric root and later add default handle.
     * Use the alphabetical roots when non-numeric is explicitly requested.
     *
     * @param  string $provider
     * @return string $root
     */
    protected function getRoot(string $provider): string
    {
        $root = Provider::ROOT[$provider]['default'];

        if ($this->numeric === false)
        {
            $root = Provider::ROOT[$provider]['alpha'];

            $handle = $this->merchant->getHandle();

            if (strlen($handle) !== self::STANDARD_HANDLE_LENGTH)
            {
                $root = Provider::ROOT[$provider]['special'];
            }
        }

        return $root;
    }

    /**
     * By default, we use the default numeric root,
     * and now add the default numeric handle.
     *
     * If non-numeric is requested, we use merchant handle.
     *
     * @param  string $root
     * @return string $handle
     */
    protected function getHandle(string $root): string
    {
        $handle = $this->merchant->getHandle();

        if ($this->numeric === true)
        {
            $handle = $this->getDefaultHandle($root);
        }

        return $handle;
    }

    /**
     * Random descriptor is used if numeric account is needed, or if
     * descriptor isn't given. Otherwise, given descriptor is used.
     *
     * Merchant handles can be 3 or 4 characters. Max is 17,
     * so we pad with 17-4-n characters, i.e. 10 or 9.
     *
     * @param  string $handle [description]
     * @param  string $root   [description]
     * @return [type]         [description]
     */
    protected function getDescriptor(string $handle, string $root): string
    {
        $descriptor = $this->descriptor;

        if (($this->numeric === true) or
            ($descriptor === null))
        {
            $totalLength = self::ACCOUNT_NUMBER_LENGTH;

            $availableLength = $totalLength - strlen($root) - strlen($handle);

            $descriptor = $this->padWithRandomDigits($availableLength);
        }

        return $descriptor;
    }

    protected function getDefaultHandle(string $root): string
    {
        return Provider::DEFAULT_HANDLE_MAPPING[$root];
    }

    protected function padWithRandomDigits(int $desiredLength): string
    {
        $pad = '';

        $charSpace = $this->getCharSpace();

        while (strlen($pad) < $desiredLength)
        {
            $pad .= $charSpace[array_rand($charSpace)];
        }

        return $pad;
    }

    protected function getCharSpace(): array
    {
        $charSpace = self::ACCOUNT_NUMBER_NUM_CHAR_SPACE;

        if ($this->numeric === false)
        {
            $charSpace = self::ACCOUNT_NUMBER_ALPHANUM_CHAR_SPACE;
        }

        return str_split($charSpace);
    }
}
