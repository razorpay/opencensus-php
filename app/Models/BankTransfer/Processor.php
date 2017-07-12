<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount;
use RZP\Models\Merchant\Account;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Processor extends Base\Core
{
    protected $virtualAccount;
    protected $core;
    protected $provider;
    protected $merchant;

    const DEFAULT_BANK_TRANSFER_ARRAY = [
        Payment::CURRENCY => Currency::INR,
        Payment::METHOD   => Method::BANK_TRANSFER,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        $this->core = new Core;

        // These flows are initiated by the provider bank hitting
        // our APIs. Provider banks are currently authenticated by
        // registering them as apps, and using AppAuth.
        $this->provider = $this->app['basicauth']->getInternalApp();
    }

    public function process(array $input)
    {
        $bankTransfer = $this->core->create($input);

        $this->setUtrInTestMode($bankTransfer);

        if (($this->utrCheck($bankTransfer) === true) and
            ($this->isTransferExpected($bankTransfer) === true))
        {
            $bankTransfer->setExpected(true);

            $this->setMerchant();

            $this->processBankTransferForMerchant($bankTransfer, $this->merchant);

            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESSING_SUCCESSFUL,
                $bankTransfer->toArrayPublic()
            );
        }
        else if ($this->isTransferExpected($bankTransfer) === false)
        {
            $this->processUnexpectedBankTransfer($bankTransfer);
        }
        else
        {
            $bankTransfer->setExpected(false);

            $this->repo->saveOrFail($bankTransfer);
        }

        return $bankTransfer;
    }

    protected function processBankTransferForMerchant(Entity $bankTransfer, Merchant $merchant)
    {
        $paymentProcessor = new PaymentProcessor($merchant);

        $this->repo->transaction(function() use (
            $bankTransfer,
            $paymentProcessor,
            $merchant)
        {
            $paymentInput = $this->bankTransferPaymentArray($bankTransfer);

            $res = $paymentProcessor->process($paymentInput);

            $payment = $this->repo
                            ->payment
                            ->findByPublicId($res['razorpay_payment_id']);

            $bankTransfer->payment()->associate($payment);

            $bankTransfer->merchant()->associate($merchant);

            $bankTransfer->virtualAccount()->associate($this->virtualAccount);

            $this->repo->saveOrFail($bankTransfer);

            $this->updateVirtualAccount($bankTransfer);

            if ($bankTransfer->isExpected() === true)
            {
                $paymentProcessor->autoCapturePayment($payment);
            }
        });
    }

    protected function processUnexpectedBankTransfer(Entity $bankTransfer)
    {
        $payeeAccount = $bankTransfer->getPayeeAccount();

        // Ignore payments made to reserved accounts, i.e. accounts that use the
        // reserved roots. We will use this for other cool stuff.
        if (VirtualAccount\Provider::isReservedAccount($payeeAccount, $this->provider) === true)
        {
            return;
        }

        $bankTransfer->setExpected(false);

        $this->setDefaultMerchant();

        $this->createAndSetVirtualAccount($bankTransfer);

        $this->processBankTransferForMerchant($bankTransfer, $this->merchant);
    }

    protected function setUtrInTestMode(Entity $bankTransfer)
    {
        // Dashboard provider is used in test mode
        // to simulate payments to a virtual account.
        //
        // UTR is not sent by dashboard in test mode, but is exposed to the merchant.
        // So we add a mock UTR here itself, and skip the uniqueness check.
        if (($this->mode === Mode::TEST) and
            ($this->provider === VirtualAccount\Provider::DASHBOARD) and
            ($this->env !== 'testing'))
        {
            $mockedUtr = $this->getMockedUtr();

            $bankTransfer->setUtr($mockedUtr);
        }
    }

    protected function utrCheck(Entity $bankTransfer): bool
    {
        $utr = $bankTransfer->getUtr();

        $duplicateBankTransfer = $this->repo
                                      ->bank_transfer
                                      ->findByUtr($utr);

        if ($duplicateBankTransfer === null)
        {
            return true;
        }

        $this->trace->error(
            TraceCode::BANK_TRANSFER_PROCESS_DUPLICATE_UTR,
            [
                'message'           => 'Duplicate UTR received',
                'existing_transfer' => $duplicateBankTransfer->toArrayPublic(),
                'received_utr'      => $bankTransfer->getUtr(),
            ]
        );

        return false;
    }

    protected function getMockedUtr(): string
    {
        return strtoupper(random_alphanum_string(22));
    }

    protected function isTransferExpected(Entity $bankTransfer): bool
    {
        $this->setVirtualAccount($bankTransfer);

        if ($this->virtualAccount === null)
        {
            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESSING_FAILED,
                [
                    'message'      => 'Invalid account number',
                    'bankTransfer' => $bankTransfer->toArrayPublic(),
                ]
            );

            return false;
        }

        return true;
    }

    protected function setVirtualAccount(Entity $bankTransfer)
    {
        $this->virtualAccount = $this->getVirtualAccountFromBankTransfer($bankTransfer);
    }

    protected function setMerchant()
    {
        $this->merchant = $this->virtualAccount->merchant;
    }

    protected function setDefaultMerchant()
    {
        $defaultMerchantId = Account::DEMO_PAGE_ACCOUNT;

        if ($this->mode === Mode::TEST)
        {
            $defaultMerchantId = Account::TEST_ACCOUNT;
        }

        $this->merchant = $this->repo->merchant->findByPublicId($defaultMerchantId);
    }

    protected function createAndSetVirtualAccount(Entity $bankTransfer)
    {
        $data = $this->virtualAccountCreationArray($bankTransfer);

        $virtualAccount = (new VirtualAccount\Core)->create($data, $this->merchant);

        $this->virtualAccount = $virtualAccount;
    }

    protected function updateVirtualAccount(Entity $bankTransfer)
    {
        $this->virtualAccount->incrementAmountPaid($bankTransfer->getAmount());

        $this->virtualAccount->incrementAmountReceived($bankTransfer->getAmount());

        $this->repo->saveOrFail($this->virtualAccount);
    }

    protected function getVirtualAccountFromBankTransfer(Entity $bankTransfer)
    {
        $accountNumber = $bankTransfer->getPayeeAccount();

        $bankAccount = $this->getBankAccountFromNumber($accountNumber);

        if (count($bankAccount) === 0)
        {
            return null;
        }

        $bankAccountId = $bankAccount->getId();

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->getActiveVirtualAccountFromBankAccountId($bankAccountId);

        return $virtualAccount;
    }

    protected function getBankAccountFromNumber(string $accountNumber)
    {
        $bankCode = VirtualAccount\Provider::getBankCode($this->provider);

        $bankAccount = $this->repo
                            ->bank_account
                            ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $bankCode);

        return $bankAccount;
    }

    protected function virtualAccountCreationArray(Entity $bankTransfer): array
    {
        return [
            VirtualAccount\Entity::AMOUNT_EXPECTED => $bankTransfer->getAmount(),
        ];
    }

    protected function bankTransferPaymentArray(Entity $bankTransfer): array
    {
        $paymentArray = self::DEFAULT_BANK_TRANSFER_ARRAY;

        $paymentArray[Payment::AMOUNT]      = $bankTransfer->getAmount();

        if ($this->virtualAccount->hasCustomer() === true)
        {
            $customer = $this->virtualAccount->customer;

            $paymentArray[Payment::CUSTOMER_ID] = $customer->getPublicId();
            $paymentArray[Payment::CONTACT]     = $customer->getContact();
            $paymentArray[Payment::EMAIL]       = $customer->getEmail();
        }

        return $paymentArray;
    }
}
