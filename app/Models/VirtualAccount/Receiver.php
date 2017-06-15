<?php

namespace RZP\Models\VirtualAccount;

use App;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount\Entity as BankAccount;

class Receiver
{
    const BANK_ACCOUNT      = 'bank_account';
    // const VPA               = 'vpa';

    const TYPES = [
        self::BANK_ACCOUNT,
        // self::VPA,
    ];

    const ROOT_LENGTH               = 4;
    const HANDLE_LENGTH             = 4;
    const DESCRIPTOR_LENGTH         = 10;
    const ACCOUNT_NUMBER_LENGTH     = 18;
    // No 0s and Os
    const ACCOUNT_NUMBER_CHAR_SPACE = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';

    protected $merchant;
    protected $name;
    protected $descriptor;

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

    public static function isTypeValid(string $type): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    public function buildBankAccount()
    {
        $bankAccount = new BankAccount;

        $bankAccountInput = $this->generateBankAccountInput();

        $bankAccount = $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        $bankAccount->merchant()->associate($this->merchant);

        $bankAccount->setVirtual(true);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
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
            $provider = Provider::VVS;
        }

        return $provider;
    }

    protected function generateAccountNumberForProvider(string $provider)
    {
        $bankCode = Provider::getBankCode($provider);

        foreach (Provider::ROOT[$provider] as $root)
        {
            $accountNumber = $this->generateNewAccountNumberWithRoot($root);

            $existingAccount = $this->repo->bank_account
                                    ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $bankCode);

            if ($existingAccount === null)
            {
                return $accountNumber;
            }
        }

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
     * @return string Unique account number
     */
    protected function generateNewAccountNumberWithRoot(string $root)
    {
        $handle = $this->getHandle();

        $descriptor = $this->getDescriptor();

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

        assertTrue(strlen($accountNumber) == self::ACCOUNT_NUMBER_LENGTH);

        return $accountNumber;
    }

    protected function getHandle()
    {
        $merchantHandle = $this->merchant->getHandle();

        $accountHandle = $this->padWithRandomDigits(self::HANDLE_LENGTH, $merchantHandle);

        return $accountHandle;
    }

    protected function getDescriptor()
    {
        $accountDescriptor = $this->padWithRandomDigits(self::DESCRIPTOR_LENGTH, $this->descriptor);

        return $accountDescriptor;
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
