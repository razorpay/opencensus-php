<?php

namespace RZP\Models\VirtualAccount;

use App;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\BankAccount\Entity as BankAccount;

class Receiver
{
    const BANK_ACCOUNT      = 'bank_account';
    // const VPA               = 'vpa';

    const TYPES = [
        self::BANK_ACCOUNT,
        // self::VPA,
    ];

    const ACCOUNT_NUMBER_LENGTH = 20;

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

        $bankAccount->associateMerchant($this->merchant);

        $bankAccount->merchant()->associate($this->merchant);

        $bankAccount->setVirtual(true);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    protected function generateBankAccountInput()
    {
        $provider = $this->selectProvider();

        $details = Provider::DEFAULT_DETAILS[$provider];

        $merchantDetails = [
            BankAccount::ACCOUNT_NUMBER     => $this->generateAccountNumberForProvider($provider),
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

    /**
     * Generates unique account number for a given provider
     *
     * Each provider has a pre-decided 'master' or prefix that must be used.
     * Max length of account number is 20 characters. We use the timestamp
     * in seconds, followed by random digits to pad.
     *
     * So if YesBank is giving us a master of length 6, and timestamps are
     * currently 10 digits long, this logic allows us to generate ~10000
     * unique numbers every second.
     *
     * @param  string $provider Descripter for provider of Virtual a/c services
     * @return string Unique account number
     */
    protected function generateAccountNumberForProvider($provider)
    {
        $master = Provider::ROOT[$provider];

        $timestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        $accountNumber = $master . $timestamp;

        $digits = self::ACCOUNT_NUMBER_LENGTH - strlen($accountNumber);

        // TODO:
        // * Simplify this, make it easier to type
        // * Use descriptor when provided

        $accountNumber .= rand(pow(10, $digits - 1), pow(10, $digits) - 1);

        return $accountNumber;
    }
}
