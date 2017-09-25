<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;
use RZP\Constants\Mode as RzpMode;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
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

        // These flows are initiated by the provider bank hitting
        // our APIs. Provider banks are currently authenticated by
        // registering them as apps, and using AppAuth.
        $this->provider = $this->app['basicauth']->getInternalApp();
    }

    /**
     * @param Entity $bankTransfer
     *
     * @return Entity|null
     */
    public function process(Entity $bankTransfer)
    {
        $this->setUtrInTestMode($bankTransfer);

        $isTransferExpected = $this->isTransferExpected($bankTransfer);

        if (($this->utrCheck($bankTransfer) === true) and
            ($isTransferExpected === true))
        {
            $bankTransfer->setExpected(true);

            $this->setMerchant();
        }
        else if ($isTransferExpected === false)
        {
            if ($this->checkReservedAccount($bankTransfer) === true)
            {
                return null;
            }

            $this->preProcessUnexpectedBankTransfer($bankTransfer);
        }
        else
        {
            //
            // The transfer is an expected one, i.e. it is made to a valid account
            // but the UTR is a duplicate, indicating that a payment is being processed
            // for a second time. In this case, we do not create anything but a
            // bank_transfer entity, marked as unexpected.
            //
            $bankTransfer->setExpected(false);

            $this->repo->saveOrFail($bankTransfer);

            return $bankTransfer;
        }

        $this->processBankTransfer($bankTransfer);

        $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESSING_SUCCESSFUL,
                $bankTransfer->toArray());

        return $bankTransfer;
    }

    protected function processBankTransfer(Entity $bankTransfer)
    {
        $paymentProcessor = new PaymentProcessor($this->merchant);

        $this->repo->transaction(function() use (
            $bankTransfer,
            $paymentProcessor)
        {
            $paymentInput = $this->bankTransferPaymentArray($bankTransfer);

            $res = $paymentProcessor->process($paymentInput);

            $payment = $this->repo
                            ->payment
                            ->findByPublicId($res['razorpay_payment_id']);

            $bankTransfer->payment()->associate($payment);

            $bankTransfer->merchant()->associate($this->merchant);

            $bankTransfer->virtualAccount()->associate($this->virtualAccount);

            $this->createAndAssociatePayerBankAccount($bankTransfer);

            $this->repo->saveOrFail($bankTransfer);

            $this->updateVirtualAccount($bankTransfer);

            if ($bankTransfer->isExpected() === true)
            {
                $paymentProcessor->autoCapturePayment($payment);
            }
        });
    }

    protected function preProcessUnexpectedBankTransfer(Entity $bankTransfer)
    {
        $bankTransfer->setExpected(false);

        $this->setDefaultMerchant();

        $this->createAndSetVirtualAccount($bankTransfer->getAmount());
    }

    protected function setUtrInTestMode(Entity $bankTransfer)
    {
        // Dashboard provider is used in test mode
        // to simulate payments to a virtual account.
        //
        // UTR is not sent by dashboard in test mode, but is exposed to the merchant.
        // So we add a mock UTR here itself, and skip the uniqueness check.
        if (($this->mode === RzpMode::TEST) and
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
                'existing_transfer' => $duplicateBankTransfer->toArray(),
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
                    'bankTransfer' => $bankTransfer->toArray(),
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

        if ($this->env !== 'production')
        {
            $defaultMerchantId = Account::TEST_ACCOUNT;
        }

        $this->merchant = $this->repo->merchant->findByPublicId($defaultMerchantId);
    }

    protected function createAndSetVirtualAccount(int $amount)
    {
        $data = $this->virtualAccountCreationArray($amount);

        $virtualAccount = (new VirtualAccount\Core)->create($data, $this->merchant);

        $this->virtualAccount = $virtualAccount;
    }

    protected function updateVirtualAccount(Entity $bankTransfer)
    {
        $this->virtualAccount->incrementAmountPaid($bankTransfer->getAmount());

        $this->virtualAccount->incrementAmountReceived($bankTransfer->getAmount());

        $this->repo->saveOrFail($this->virtualAccount);
    }

    protected function checkReservedAccount(Entity $bankTransfer)
    {
        $payeeAccount = $bankTransfer->getPayeeAccount();

        // Ignore payments made to reserved accounts, i.e. accounts that use the
        // reserved roots. We will use this for other cool stuff.
        if (VirtualAccount\Provider::isReservedAccount($payeeAccount, $this->provider) === true)
        {
            $this->trace->info(
                TraceCode::BANK_TRANSFER_RESERVED_ACCOUNT,
                $bankTransfer->toArray()
            );

            return true;
        }

        return false;
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

    protected function virtualAccountCreationArray(int $amount): array
    {
        return [
            VirtualAccount\Entity::AMOUNT_EXPECTED => $amount,
        ];
    }

    protected function createAndAssociatePayerBankAccount(Entity $bankTransfer)
    {
        $bankAccount = $this->createPayerBankAccount($bankTransfer);

        $bankTransfer->payerBankAccount()->associate($bankAccount);
    }

    protected function createPayerBankAccount(Entity $bankTransfer)
    {
        $bankAccount = new BankAccount\Entity;

        $bankAccountInput = $this->getBankAccountInput($bankTransfer);

        $bankAccount = $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        $bankAccount->merchant()->associate($bankTransfer->merchant);

        $bankAccount->associateVirtualAccount($bankTransfer->virtualAccount);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    protected function getBankAccountInput(Entity $bankTransfer)
    {
        $label = $this->getLabel($bankTransfer);

        $ifsc = $this->getPayerIfsc($bankTransfer);

        return [
            BankAccount\Entity::IFSC_CODE        => $ifsc,
            BankAccount\Entity::ACCOUNT_NUMBER   => $bankTransfer->getPayerAccount(),
            BankAccount\Entity::BENEFICIARY_NAME => $label,
        ];
    }

    protected function getLabel(Entity $bankTransfer)
    {
        $label = $bankTransfer->getPayerName();

        if (empty($label) === true)
        {
            $label = $bankTransfer->merchant->getBillingLabel();
        }

        return substr(preg_replace('/[^a-zA-Z0-9 ]+/', '', $label), 0, 39);
    }

    protected function getPayerIfsc(Entity $bankTransfer)
    {
        $ifsc = $bankTransfer->getPayerIfsc();

        if ((strlen($ifsc) !== BankAccount\Entity::IFSC_CODE_LENGTH) and
            ($bankTransfer->getMode() === Mode::IMPS))
        {
            $bankCode = substr($ifsc, 0, 3);

            $ifsc = BankCodes::getIfscForBankCode($bankCode);
        }

        return $ifsc;
    }

    protected function bankTransferPaymentArray(Entity $bankTransfer): array
    {
        $paymentArray = self::DEFAULT_BANK_TRANSFER_ARRAY;

        $paymentArray[Payment::AMOUNT]      = $bankTransfer->getAmount();
        $paymentArray[Payment::DESCRIPTION] = $bankTransfer->getDescription() ?? "";

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
