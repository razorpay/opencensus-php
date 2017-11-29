<?php

namespace RZP\Models\BankTransfer;

use App;

use Exception;
use RZP\Models\Base;
use RZP\Constants\Mode as RzpMode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Processor extends VirtualAccount\Processor
{
    const DEFAULT_BANK_TRANSFER_ARRAY = [
        Payment\Entity::CURRENCY => Currency::INR,
        Payment\Entity::METHOD   => Payment\Method::BANK_TRANSFER,
    ];

    const PAYER_BANK_ACCOUNT_MAX_LENGTH = 20;

    /**
     * Entry point for bank transfer process flow.
     * Check if the bankTransfer was an expected one.
     * - BankTransfer was expected?
     *   - Yes
     *     - UTR unique?
     *       - Yes
     *         - Process the payment towards the owner of the VA
     *       - No
     *         - Duplicate payment, save entity and ignore
     *   - No
     *     - Reserved account?
     *       - Yes
     *         - Do nothing
     *       - No
     *         - Process payment toward demo merchant, auto-refund it later.
     *
     * @param Entity|Base\PublicEntity $bankTransfer
     *
     * @return null|Entity
     */
    public function process(Base\PublicEntity $bankTransfer)
    {
        $this->setUtrInTestMode($bankTransfer);

        $isPaymentExpected = $this->isPaymentExpected($bankTransfer);

        if (($this->utrCheck($bankTransfer) === true) and
            ($isPaymentExpected === true))
        {
            $bankTransfer->setExpected(true);

            $this->setMerchant();
        }
        else if ($isPaymentExpected === false)
        {
            if ($this->checkReservedAccount($bankTransfer) === true)
            {
                return null;
            }

            $this->preProcessUnexpectedPayment($bankTransfer);
        }
        else
        {
            //
            // The transfer is an expected one, i.e. it is made to a valid account
            // but the UTR is a duplicate, indicating that a payment is being processed
            // for a second time. In this case, we do nothing.
            //

            return null;
        }

        $this->processBankTransfer($bankTransfer);

        $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESSING_SUCCESSFUL,
                $bankTransfer->toArray());

        return $bankTransfer;
    }

    /**
     * Processing the bank transfer
     *  - Create bank transfer, associate with the merchant, and the identified VA
     *  - Create payment, associate with the bank transfer
     *  - Create payer bank account, associate with bank transfer
     *  - Update VA amount fields and status, if necessary
     *
     * @param Entity $bankTransfer
     */
    protected function processBankTransfer(Entity $bankTransfer)
    {
        $paymentProcessor = new PaymentProcessor($this->merchant);

        $payment = $this->repo->transaction(function() use (
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

            $this->virtualAccount->updateWithBankTransfer($bankTransfer);

            $this->repo->saveOrFail($this->virtualAccount);

            return $payment;
        });

        if ($bankTransfer->isExpected() === true)
        {
            $paymentProcessor->autoCapturePayment($payment);
        }
    }

    /**
     * A UTR is required processing, but test providers like dashboard
     * do not give a UTR. In this case, we use a mocked UTR instead.
     *
     * @param Entity $bankTransfer
     */
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

    /**
     * Check if the UTR received has ever been encountered before.
     * If it has, this is a duplicate payment, being processed again.
     *
     * @param Entity $bankTransfer
     *
     * @return bool
     */
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

    /**
     * Mock UTR for test mode.
     * UTRs are supposed to be 16 or 22 chars, alphanumeric.
     *
     * @return string
     */
    protected function getMockedUtr(): string
    {
        return strtoupper(random_alphanum_string(22));
    }

    /**
     * Set the merchant for future processing.
     * Use the owner of the VA for this.
     */
    protected function setMerchant()
    {
        $this->merchant = $this->virtualAccount->merchant;
    }

    /**
     * Certain roots are reserved for Razorpay's own usage, eg. for inter-nodal transfers.
     *
     * @param Entity $bankTransfer
     *
     * @return bool
     */
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

    /**
     * Given a bank transfer, locate the bank account that is
     * being paid, and the associated active VA, if present.
     *
     * @param Base\PublicEntity $entity
     *
     * @return null|VirtualAccount\Entity
     */
    protected function getVirtualAccountFromEntity(Base\PublicEntity $entity)
    {
        // TODO: Put assert on entity type
        $accountNumber = $entity->getPayeeAccount();

        $bankAccount = $this->getBankAccountFromNumber($accountNumber);

        if ($bankAccount === null)
        {
            return null;
        }

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->getActiveVirtualAccountFromBankAccountId($bankAccount->getId());

        return $virtualAccount;
    }

    /**
     * Find the bank account being paid. We search only amongst
     * the bank accounts that were created by the current provider.
     *
     * @param string $accountNumber
     *
     * @return BankAccount\Entity|null
     */
    protected function getBankAccountFromNumber(string $accountNumber)
    {
        $bankCode = VirtualAccount\Provider::getBankCode($this->provider);

        $bankAccount = $this->repo
                            ->bank_account
                            ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $bankCode);

        return $bankAccount;
    }

    /**
     * Throwaway VAs for unexpected bank transfers don't need much to be created.
     *
     * @param int $amount
     *
     * @return array
     */
    protected function virtualAccountCreationArray(int $amount): array
    {
        return [
            VirtualAccount\Entity::AMOUNT_EXPECTED => $amount,
            VirtualAccount\Entity::RECEIVERS => [
                VirtualAccount\Entity::TYPES => [
                ],
            ],
        ];
    }

    /**
     * A payer bank account entity is created as well, at the time of payment itself.
     * This will be used to associate the payout, if this payment is ever refunded.
     *
     * @param Entity $bankTransfer
     */
    protected function createAndAssociatePayerBankAccount(Entity $bankTransfer)
    {
        try
        {
            $bankAccount = $this->createPayerBankAccount($bankTransfer);

            $bankTransfer->payerBankAccount()->associate($bankAccount);
        }
        catch (Exception $ex)
        {
            //
            // In some situations, we don't have enough info to create a bank account at all
            // It's fine, since we don't intend on allowing these payments to be refunded anyway.
            //
            $this->trace->traceException(
                $ex,
                Trace::INFO,
                TraceCode::BANK_TRANSFER_PAYER_BANK_ACCOUNT_SKIPPED,
                $bankTransfer->toArray());
        }
    }

    /**
     * Payer bank account, for future use in refunds,
     * is created and associated with merchant and VA.
     *
     * @param Entity $bankTransfer
     *
     * @return $this|BankAccount\Entity
     */
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

    /**
     * Bank account creation requires limited fields, since we
     * are using the addVirtualBankAccount validation rules.
     *
     * @param Entity $bankTransfer
     *
     * @return array
     */
    protected function getBankAccountInput(Entity $bankTransfer)
    {
        return [
            BankAccount\Entity::IFSC_CODE        => $this->getPayerIfsc($bankTransfer),
            BankAccount\Entity::ACCOUNT_NUMBER   => $this->getPayerAccount($bankTransfer),
            BankAccount\Entity::BENEFICIARY_NAME => $this->getLabel($bankTransfer),
        ];
    }

    /**
     * Label (or beneficiary name) for created payer bank account. Use the
     * payer_name given by Kotak if available, else merchant name. Sanitize!
     *
     * @param Entity $bankTransfer
     *
     * @return string
     */
    protected function getLabel(Entity $bankTransfer)
    {
        $label = $bankTransfer->getPayerName();

        $label = preg_replace('/[^a-zA-Z0-9 ]+/', '', $label);

        // Label could be empty AFTER the preg_replace step
        if (empty(trim($label)) === true)
        {
            $label = $bankTransfer->merchant->getBillingLabel();

            // Still necessary to sanitize merchant name
            $label = preg_replace('/[^a-zA-Z0-9 ]+/', '', $label);
        }

        return substr($label, 0, 39);
    }

    /**
     * Sanitizes account numbers received.
     *
     * @param Entity $bankTransfer
     *
     * @return null|string
     */
    protected function getPayerAccount(Entity $bankTransfer)
    {
        $account = $bankTransfer->getPayerAccount();

        $account = preg_replace('/[^a-zA-Z0-9]+/', '', $account);

        $account = BankCodes::modifyPayerAccountIfNeeded($account, $bankTransfer);

        return $account;
    }

    /**
     * We don't get valid IFSCs from Kotak for IMPS payments, only a bank code. To create the
     * payer bank account anyway, we derive the bank from the code, and use a random IFSC.
     * Later, IMPS refunds should go through, as IFSC isn't validated by Kotak for payments.
     *
     * @param Entity $bankTransfer
     *
     * @return mixed|null
     */
    public function getPayerIfsc(Entity $bankTransfer)
    {
        $ifsc = $bankTransfer->getPayerIfsc();

        if ((strlen($ifsc) !== BankAccount\Entity::IFSC_CODE_LENGTH) and
            ($bankTransfer->getMode() === Mode::IMPS))
        {
            //
            // Last 10 characters are customer phone number
            //
            $bankCode = substr($ifsc, 0, -10);

            $ifsc = BankCodes::getIfscForBankCode($bankCode);

            if ($ifsc === null)
            {
                $this->trace->warning(
                    TraceCode::BANK_TRANSFER_IFSC_CODE_MISSING,
                    [
                        'imps_ifsc' => $bankTransfer->getPayerIfsc(),
                    ]);
            }
        }

        return $ifsc;
    }

    /**
     * Payment array use to send to Payment\Processor for bank transfer payments
     * Bank transfer description field may contain customer remarks, so use that.
     * If the VA has an associated customer, use those details as well.
     *
     * @param Entity $bankTransfer
     *
     * @return array
     */
    protected function bankTransferPaymentArray(Entity $bankTransfer): array
    {
        $paymentArray = self::DEFAULT_BANK_TRANSFER_ARRAY;

        $paymentArray[Payment\Entity::AMOUNT]      = $bankTransfer->getAmount();
        $paymentArray[Payment\Entity::DESCRIPTION] = $bankTransfer->getDescription() ?? "";

        if ($this->virtualAccount->hasCustomer() === true)
        {
            $customer = $this->virtualAccount->customer;

            $paymentArray[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            $paymentArray[Payment\Entity::CONTACT]     = $customer->getContact();
            $paymentArray[Payment\Entity::EMAIL]       = $customer->getEmail();
        }

        return $paymentArray;
    }
}
