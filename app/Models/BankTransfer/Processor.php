<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Currency\Currency;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Payment\Entity as Payment;
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

            $this->processExpectedBankTransfer($bankTransfer);

            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESSING_SUCCESSFUL,
                $bankTransfer->toArrayPublic()
            );
        }
        else
        {
            $bankTransfer->setExpected(false);

            $this->repo->saveOrFail($bankTransfer);
        }

        return $bankTransfer;
    }

    protected function processExpectedBankTransfer(Entity $bankTransfer)
    {
        $paymentProcessor = new PaymentProcessor($this->merchant);

        $this->repo->transaction(function() use ($bankTransfer, $paymentProcessor)
        {
            $paymentInput = $this->bankTransferPaymentArray($bankTransfer);

            $res = $paymentProcessor->process($paymentInput);

            $payment = $this->repo
                            ->payment
                            ->findByPublicId($res['razorpay_payment_id']);

            $bankTransfer->payment()->associate($payment);

            $bankTransfer->merchant()->associate($this->merchant);

            $bankTransfer->virtualAccount()->associate($this->virtualAccount);

            $this->repo->saveOrFail($bankTransfer);

            $this->updateVirtualAccount($bankTransfer);
        });
    }

    protected function setUtrInTestMode(Entity $bankTransfer)
    {
        // Dashboard provider is used in test mode
        // to simulate payments to a virtual account.
        //
        // UTR is not sent by dashboard in test mode, but is exposed to the merchant.
        // So we add a mock UTR here itself, and skip the uniqueness check.
        if (($this->mode === Mode::TEST) and
            ($this->provider === Provider::DASHBOARD) and
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
        $bankCode = Provider::getBankCode($this->provider);

        $bankAccount = $this->repo
                            ->bank_account
                            ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $bankCode);

        return $bankAccount;
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
