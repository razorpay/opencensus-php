<?php

namespace RZP\Models\BankAccount;

use Cache;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Models\Settings;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount;
use RZP\Models\Feature\Constants;
use RZP\Error\PublicErrorDescription;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as QrV2Entity;

class Generator extends Base\Core
{
    /**
     * Constants
     */
    const NUMERIC    = 'numeric';
    const DESCRIPTOR = 'descriptor';
    const BANKING    = 'banking';

    // No 0s and Os
    // No 1s and Is
    // No 5s and Ss
    // No 8s and Bs
    // No 2s and Zs
    const ACCOUNT_NUMBER_ALPHANUM_CHAR_SPACE    = '34679ACDEFGHJKLMNPQRTUVWXY';

    const ACCOUNT_NUMBER_NUM_CHAR_SPACE         = '0123456789';

    const VA_BANK_ACCOUNT_GENERATION            = 'va_bank_account_generation';

    const MAX_ACCOUNT_GENERATION_ATTEMPTS       = 10;

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    protected $options = [
        self::DESCRIPTOR => null,
        self::NUMERIC    => true,
        // Banking option causes terminal selection to use one with corresponding type set.
        self::BANKING    => false,
    ];

    public function __construct(Merchant\Entity $merchant, array $input)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->setBankAccountOptions($input);

        $this->mutex = $this->app['api.mutex'];
    }

    protected function buildBankAccountEntity(Base\PublicEntity $entity): Entity
    {
        $bankAccount = new Entity();

        $bankAccount->merchant()->associate($this->merchant);

        $bankAccount->source()->associate($entity);

        return $bankAccount;
    }

    protected function updateBankAccountEntity(
        Entity $bankAccount,
        Base\PublicEntity $entity,
        Terminal\Entity $terminal): Entity
    {
        $accountNumber = $this->generateBankAccountNumber($terminal);

        $providerBank = $this->getProviderBank($terminal);

        $bankAccountInput = $this->getBankAccountInput($accountNumber, $entity, $providerBank);

        $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        return $bankAccount;
    }

    protected function getTerminalForBankAccount(Entity $bankAccount): Terminal\Entity
    {

        $terminal = (new VirtualAccount\Provider())->getTerminalForMethod(Method::BANK_TRANSFER, $bankAccount, null, $this->options);

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'No Terminal applicable.',
                null,
                [
                    'merchant_id'   => $this->merchant->getId(),
                    'method'        => Method::BANK_TRANSFER,
                    'options'       => $this->options,
                ]);
        }

        return $terminal;
    }

    public function generate(Base\PublicEntity $entity): Entity
    {
        if ($entity instanceof VirtualAccount\Entity)
        {
            // Sets this option at this stage because in __construct the balance relation doesn't exist
            $this->options[self::BANKING] = $entity->isBalanceTypeBanking();
        }

        $bankAccount = $this->buildBankAccountEntity($entity);

        $isBalanceTypeBanking = $this->options[self::BANKING] !== null ? $this->options[self::BANKING] : false;

        $terminal = $this->fetchTerminal($bankAccount, $isBalanceTypeBanking);

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'No Terminal applicable.',
                null,
                [
                    'merchant_id'   => $this->merchant->getId(),
                    'method'        => Method::BANK_TRANSFER,
                    'options'       => $this->options,
                ]);
        }

        $attempts = 0;

        while ($attempts <= self::MAX_ACCOUNT_GENERATION_ATTEMPTS)
        {
            $bankAccount = $this->updateBankAccountEntity($bankAccount, $entity, $terminal);

            $savedBankAccount = $this->lockAndSaveBankAccount($bankAccount);

            if ($savedBankAccount !== null)
            {
                return $savedBankAccount;
            }
            else if($this->options[self::DESCRIPTOR] !== null)
            {
                /*
                 * Bank Account is null when same bank account already exist
                 * for any other virtual account in our system.
                 *
                 * But If Descriptor was passed by merchant then we throw
                 * bad request identical descriptor .
                 */
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_IDENTICAL_DESCRIPTOR,
                    'descriptor',
                    [
                        'terminal'      => $terminal->getId(),
                        'descriptor'    => $this->options[Generator::DESCRIPTOR],
                    ]);
            }

            $attempts++;
        }

        if ($entity instanceof QrV2Entity)
        {
            $this->trace->critical(TraceCode::QR_CODE_UNAVAILABLE,
                                   [
                                       'method'      => Method::BANK_TRANSFER,
                                       'options'     => $this->options,
                                       'terminal'    => $terminal->getId(),
                                       'merchant_id' => $this->merchant->getId(),
                                   ]);

            throw new Exception\LogicException(
                PublicErrorDescription::SERVER_ERROR_QR_CODE_GENERATION_FAILURE,
                ErrorCode::SERVER_ERROR_QR_CODE_GENERATION_FAILURE
            );
        }

        $this->trace->critical(ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_UNAVAILABLE,
                               [
                                   'method'      => Method::BANK_TRANSFER,
                                   'options'     => $this->options,
                                   'terminal'    => $terminal->getId(),
                                   'merchant_id' => $this->merchant->getId(),
                               ]);

        // This should never happen
        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_UNAVAILABLE);
    }

    /**
     * Grabs a lock on the account number, then checks if it is a
     * valid one, and if so, saves it to DB in the bank accounts table.
     *
     * This function is called in a loop, and is expected to return a bank
     * account entity. If an entity is not returned, and null is returned
     * instead, the calling function assumes that bank account was not created
     * for some reason (usually because the lock on that account number was
     * already taken by a different process), and creates a new account number
     * for the next attempt.
     *
     * This allows us to 'fail' an attempt at account generation by simply returning null.
     *
     * @param  Entity                   $bankAccount    Bank Account entity to be saved.
     * @return Entity|null                              Saved bank account, or null if no account was saved.
     */
    protected function lockAndSaveBankAccount(Entity $bankAccount)
    {
        $bankAccount = $this->mutex->acquireAndRelease(
            self::VA_BANK_ACCOUNT_GENERATION . $bankAccount->getAccountNumber(),
            function() use ($bankAccount)
            {
                $existingAccount = $this->repo->bank_account
                    ->findVirtualBankAccountByAccountNumberAndBankCode($bankAccount->getAccountNumber(), $bankAccount->getIfscCode(), true);

                if ($existingAccount !== null)
                {
                    // Account with this number already exists, fail this attempt
                    return;
                }

                $this->repo->saveOrFail($bankAccount);

                return $bankAccount;
            },
            60,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS);

        return $bankAccount;
    }

    protected function getBankAccountInput(
        string $accountNumber,
        Base\PublicEntity $entity,
        string $provider): array
    {
        $bankAccountInput = VirtualAccount\Provider::DEFAULT_DETAILS[$provider];

        $merchantDetails = [
            Entity::ACCOUNT_NUMBER     => $accountNumber,
            Entity::BENEFICIARY_NAME   => $this->options[Entity::NAME] ?? $entity->getName(),
        ];

        return array_merge($bankAccountInput, $merchantDetails);
    }

    protected function getCharSpace(): array
    {
        $charSpace = self::ACCOUNT_NUMBER_NUM_CHAR_SPACE;

        $numeric = $this->options[self::NUMERIC];

        if ($numeric === false)
        {
            $charSpace = self::ACCOUNT_NUMBER_ALPHANUM_CHAR_SPACE;
        }

        return str_split($charSpace);
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

    /*
     * This is temporarily used for virtual bank account terminal, until we
     * migrate gateway column in BankTransfer Entity from yesbank to bt_yesbank.
     */
    public function getProviderBank(Terminal\Entity $terminal): string
    {
        return str_replace('bt_' , '' , $terminal->getGateway());
    }

    protected function validateDescriptor(Terminal\Entity $terminal, $accountNumberLength)
    {
        $root = $this->getRoot($terminal);

        $handle = $this->getHandle($terminal);

        $descriptor = $this->options[Generator::DESCRIPTOR];

        $availableLength = $accountNumberLength - strlen($root) - strlen($handle);

        if (strlen($descriptor) > $availableLength)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_DESCRIPTOR_LENGTH,
                'descriptor',
                [
                    'descriptor' => $descriptor,
                ]);
        }
        //TODO: Later add a validation to only allow numeric digits in case of numeric account number.

        if ($terminal->isShared() === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Descriptor cannot be used with your account.',
                null,
                [
                    'options'       => $this->options,
                    'merchant_id'   => $this->merchant->getId(),
                ]);
        }
    }

    protected function getRoot(Terminal\Entity $terminal): string
    {
        return $terminal->getGatewayMerchantId();
    }

    protected function getHandle(Terminal\Entity $terminal): string
    {
        return $terminal->getGatewayMerchantId2() ?: '';
    }

    protected function getDescriptor(string $handle, string $root, $accountNumberLength): string
    {
        $descriptor = $this->options[Generator::DESCRIPTOR];

        if ($descriptor !== null)
        {
            return $descriptor;
        }

        $availableLength = $accountNumberLength - strlen($root) - strlen($handle);

        $descriptor = $this->padWithRandomDigits($availableLength);

        return $descriptor;
    }

    protected function generateBankAccountNumber(Terminal\Entity $terminal): string
    {
        $root = $this->getRoot($terminal);

        $handle = $this->getHandle($terminal);

        $accountNumberLength =  $this->getAccountNumberLength();

        if ($this->options[Generator::DESCRIPTOR] !== null)
        {
            $this->validateDescriptor($terminal, $accountNumberLength);
        }

        $descriptor = $this->getDescriptor($handle, $root, $accountNumberLength);

        $accountNumber = strtoupper($root . $handle . $descriptor);

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_NUMBER_GENERATED,
            [
                'root'          => $root,
                'handle'        => $handle,
                'descriptor'    => $descriptor,
                'accountNumber' => $accountNumber,
                'terminalId'    => $terminal->getId(),
            ]
        );

        if (strlen($accountNumber) > $accountNumberLength)
        {
            throw new Exception\LogicException(
                'Error in account number generation.',
                null,
                [
                    'account_number'    => $accountNumber,
                    'max_length'        => $accountNumberLength,
                ]);
        }

        return $accountNumber;
    }

    protected function setBankAccountOptions(array $input)
    {
        $this->options = array_merge($this->options, $input);

        $this->options[self::NUMERIC] = boolval($this->options[self::NUMERIC]);
    }

    public function getConfigs(VirtualAccount\Entity $virtualAccount)
    {
        $bankAccount = $this->buildBankAccountEntity($virtualAccount);

        $terminal = $this->getTerminalForBankAccount($bankAccount);

        return [
            'prefix'              => $this->getRoot($terminal) . $this->getHandle($terminal),
            'isDescriptorEnabled' => ($terminal->isShared() === false),
            'accountNumberLength' => $this->getAccountNumberLength(),
        ];
    }

    /**
     * @param Entity $bankAccount
     *
     * @return mixed|Terminal\Entity
     */
    protected function fetchTerminal(Entity $bankAccount, $isBalanceTypeBanking)
    {
        $merchantId = $bankAccount->getMerchantId();

        if ($this->merchant->org->isFeatureEnabled(Constants::ORG_NUMERIC_OPTION_FALSE) === true)
        {
            $this->options[self::NUMERIC] = false;
        }

        $terminalCaching = $this->app->razorx->getTreatment(
            $merchantId,
            Merchant\RazorxTreatment::SMART_COLLECT_TERMINAL_CACHING,
            $this->mode);

        $fetchTerminals = function() use ($bankAccount) {
            return (new VirtualAccount\Provider())->getTerminalForMethod(
                Method::BANK_TRANSFER,
                $bankAccount,
                null,
                $this->options);
        };
        $UnsupportedGatewayTerminalFilter = function($terminalAttributes)
        {
            $unsupportedProvider = false;
            if (array_key_exists(Terminal\Entity::GATEWAY, $terminalAttributes))
            {
                $provider                             = str_replace('bt_', '', $terminalAttributes[Terminal\Entity::GATEWAY]);
                $unSupportedProviderGatewayByRazorpay = (new VirtualAccount\Provider())->getUnsuportedProviderNamesByRazorpay();
                $unsupportedProvider                  = in_array($provider, $unSupportedProviderGatewayByRazorpay);
            }
            return ($unsupportedProvider == false);
        };

        if (($terminalCaching === Merchant\RazorxTreatment::RAZORX_VARIANT_ON) and ($isBalanceTypeBanking !== true))
        {
            $terminal = (new VirtualAccount\Provider())->getTerminals($merchantId, $fetchTerminals, $UnsupportedGatewayTerminalFilter);
        }
        else
        {
            $terminal = $fetchTerminals();
        }

        return $terminal;
    }

    public function getAccountNumberLength()
    {
        $accountNumberLength = (new Settings\Service)->getForMerchant(Settings\Module::VIRTUAL_ACCOUNT, Settings\Keys::ACCOUNT_NUMBER_LENGTH, $this->merchant);

        if ($accountNumberLength !== null)
        {
            return $accountNumberLength;
        }

        return Entity::ACCOUNT_NUMBER_LENGTH;
    }
}
