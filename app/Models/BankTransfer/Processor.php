<?php

namespace RZP\Models\BankTransfer;

use App;
use Cache;
use Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\Transaction;
use RZP\Models\VirtualAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use RZP\Exception\LogicException;
use RZP\Models\BharatQr\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Payment\Processor\TerminalProcessor;

class Processor extends VirtualAccount\Processor
{
    const PAYER_BANK_ACCOUNT_MAX_LENGTH = 20;

    /**
     * Check if the UTR received has ever been encountered before for the same
     * account. If it has, this is a duplicate payment, being processed again.
     *
     * The ref number for IMPS (RRN) actually can be the same for
     * two distinct transactions (around the same time), as long
     * as the remitter bank is different. Here, we query by ref
     * number + source bank info to identify a duplicate.
     *
     * @param Base\PublicEntity $bankTransfer
     *
     * @return bool
     */
    protected function isDuplicate(Base\PublicEntity $bankTransfer): bool
    {
        $utr = $bankTransfer->getUtr();

        $payerIfsc = $bankTransfer->getPayerIfsc();

        $duplicateBankTransfer = $this->repo
                                      ->bank_transfer
                                      ->findByUtrAndPayerIfsc($utr, $payerIfsc, $useWritePdo = true);

        if ($duplicateBankTransfer === null)
        {
            return false;
        }

        $this->trace->error(
            TraceCode::BANK_TRANSFER_PROCESS_DUPLICATE_UTR,
            [
                'message'           => 'Duplicate UTR received',
                'existing_transfer' => $duplicateBankTransfer->toArray(),
                'received_utr'      => $bankTransfer->getUtr(),
            ]
        );

        return true;
    }

    /**
     * Processing the bank transfer for both PG and Banking scenarios.
     *
     * Common
     *  - Create bank transfer, associate with the merchant, and the identified VA
     *  - Create payer bank account, associate with bank transfer
     * PG:
     *  - Create payment (and associated txn), associate with the bank transfer
     *  - Update VA amount fields and status, if necessary
     * BB:
     *  - Create transaction, associate with bank_transfers
     *
     * @param  Base\PublicEntity $bankTransfer
     * @return null|Base\PublicEntity
     */
    protected function processPayment(Base\PublicEntity $bankTransfer)
    {
        $this->checkIfAccountIsBlocked($bankTransfer);

        $this->repo->transaction(function() use ($bankTransfer)
        {
            // Bank transfer's relation association
            $bankTransfer->merchant()->associate($this->merchant);

            $bankTransfer->virtualAccount()->associate($this->virtualAccount);

            $bankTransfer->balance()->associate($this->virtualAccount->balance);

            $this->repo->saveOrFail($bankTransfer);

            $balanceType = $this->virtualAccount->getBalanceType();

            switch ($balanceType)
            {
                case Balance\Type::PRIMARY:
                    $this->processPaymentForPg($bankTransfer);
                    break;

                case Balance\Type::BANKING:
                    $this->processPaymentForBanking($bankTransfer);
                    break;

                default:
                    throw new LogicException(
                        'Invalid balance type, could not process payment.',
                        null,
                        compact('balanceType'));
            }
        });

        // Currently dispatches transaction.created only for bank transfer on banking balance.
        if ($bankTransfer->isBalanceTypeBanking() === true)
        {
            (new Transaction\Core)->dispatchEventForTransactionCreated($txn);
        }

        $this->refundOrCapturePayment($bankTransfer);

        return $bankTransfer;
    }

    protected function processPaymentForPg(Entity $bankTransfer)
    {
        assertTrue($this->virtualAccount->isBalanceTypePrimary(), 'Attempted processing VA payment incorrectly!');
        assertTrue($this->repo->isTransactionActive(), 'Attempted processing VA payment without transaction!');

        // Prepares payment input and creates payment and its transaction etc.
        $paymentInput = $this->getPaymentArray($bankTransfer);

        $terminal = (new TerminalProcessor())->getTerminalForBankTransfer($bankTransfer);

        $gatewayData[Payment\Entity::TERMINAL_ID] = $terminal->getId();

        $this->createPayment($paymentInput, $gatewayData);

        $payment = $this->getPaymentProcessor()->getPayment();

        $bankTransfer->payment()->associate($payment);

        $this->createAndAssociatePayerBankAccount($bankTransfer);

        $this->repo->saveOrFail($bankTransfer);

        // Updates virtual account's stats.
        $this->virtualAccount->updateWithBankTransfer($bankTransfer);

        $this->repo->saveOrFail($this->virtualAccount);
    }

    protected function processPaymentForBanking(Entity $bankTransfer)
    {
        assertTrue($this->virtualAccount->isBalanceTypeBanking(), 'Attempted processing VA payment incorrectly!');
        assertTrue($this->repo->isTransactionActive(), 'Attempted processing VA payment without transaction!');

        $this->trace->info(
            TraceCode::BANK_TRANSFER_CREATE_TRANSACTION,
            [
                'bank_transfer_id'   => $bankTransfer->getId(),
                'virtual_account_id' => $this->virtualAccount->getId(),
            ]);

        // Creates a transaction with bank transfer entity as source, merchant's banking balance gets credited.
        list ($txn, $feeSplit) = (new Transaction\Processor\BankTransfer($bankTransfer))->createTransaction();

        $this->repo->saveOrFail($txn);

        // Updates virtual account's stats.
        $this->virtualAccount->updateWithBankTransferForBanking($bankTransfer);

        $this->repo->saveOrFail($this->virtualAccount);
    }

    protected function setGateway(Payment\Entity $payment, string $provider)
    {
        $paymentGateway = Gateway::$bankTransferProviderGateway[$provider];

        $payment->setGateway($paymentGateway);
    }

    protected function checkIfAccountIsBlocked(Base\PublicEntity $bankTransfer)
    {
        $payeeAccount = $bankTransfer->getPayeeAccount();

        //
        // Cases of duplicate VAs. Payments to these accounts are to be
        // blocked till the cases are resolved with the merchants.
        //
        // Throwing this exception will cause it to be traced critical,
        // and a slack notification sent to #tech_va_logs
        //
        $blockedAccounts = [
            '2223330048089327',
            '2223330004373571',
            '2223330035064789',
            '2223330035727499',
            '2223330036078115',
            '2223330051029132',
            '2223330053833583',
            '2223330058630167',
            '2223330062713538',
            '2223330066512545',
            '2223330098769820',
            '2223330001094652',
            '2223330015368288',
            '2223330022707272',
            '2223330024009748',
            '2223330024620707',
            '2223330036226710',
            '2223330036538719',
            '2223330066361910',
            '22233300678743457',
            '2223330082601751',
            '2223330093686538',
            '2223330098561246',
        ];

        if (in_array($payeeAccount, $blockedAccounts, true) === true)
        {
            throw new LogicException('Payment made to blocked account', null, $bankTransfer->toArray());
        }
    }

    private function getBankTransferEntity(Base\PublicEntity $bankTransfer): Entity
    {
        if (($bankTransfer instanceof Entity) === false)
        {
            throw new InvalidArgumentException('Not a valid class');
        }
        return $bankTransfer;
    }

    /**
     * Given a bank transfer, locate the bank account that is
     * being paid, and the associated active VA, if present.
     *
     * @param Base\PublicEntity $bankTransfer
     *
     * @return null|VirtualAccount\Entity
     */
    protected function getVirtualAccountFromEntity(Base\PublicEntity $transfer)
    {
        // Because PHP doesn't support generics, we are applying this hack.
        $bankTransfer = $this->getBankTransferEntity($transfer);

        $accountNumber = $bankTransfer->getPayeeAccount();

        $bankAccount = $this->getBankAccountFromNumber($accountNumber, $bankTransfer->getGateway());

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
     * Payment array use to send to Payment\Processor for bank transfer payments
     * Bank transfer description field may contain customer remarks, so use that.
     * If the VA has an associated customer, use those details as well.
     *
     * @param Base\PublicEntity $bankTransfer
     *
     * @return array
     */
    protected function getPaymentArray(Base\PublicEntity $bankTransfer): array
    {
        $parentPaymentArray = $this->getDefaultPaymentArray();

        $paymentArray = [
            Payment\Entity::CURRENCY    => Currency::INR,
            Payment\Entity::METHOD      => Payment\Method::BANK_TRANSFER,
            Payment\Entity::AMOUNT      => $bankTransfer->getAmount(),
            Payment\Entity::DESCRIPTION => $bankTransfer->getDescription() ?? '',
        ];

        $paymentArray = array_merge($paymentArray, $parentPaymentArray);

        if ($this->virtualAccount->hasOrder() === true)
        {
            $order = $this->virtualAccount->entity;

            $paymentArray[Payment\Entity::ORDER_ID] = $order->getPublicId();
        }

        $merchant = $this->virtualAccount->merchant;

        if ($merchant->isFeeBearerCustomer() === true)
        {
            $paymentArray[Payment\Entity::FEE] = (new Core)->getFeesForBankTransfer($bankTransfer, $merchant);
        }

        return $paymentArray;
    }

    protected function checkPaymentExpectedAndSetVirtualAccount(Base\PublicEntity $bankTransfer): bool
    {
        //
        // This needs to be done first because isPaymentExpected sets
        // $this->virtualAccount which is required in the below block
        //
        $isExpected = parent::checkPaymentExpectedAndSetVirtualAccount($bankTransfer);

        // VA payments for crypto merchants are blocked based on cache key
        if (($isExpected === true) and
            ($this->virtualAccount->merchant->isCategory2Cryptocurrency() === true) and
            ($this->areBankTransfersBlockedForCrypto() === true))
        {
            $this->virtualAccount = (new VirtualAccount\Core)->createOrFetchSharedVirtualAccount();

            return false;
        }

        return $isExpected;
    }

    protected function areBankTransfersBlockedForCrypto(): bool
    {
        try
        {
            $block = (bool) Cache::get(ConfigKey::BLOCK_BANK_TRANSFERS_FOR_CRYPTO);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                [
                    'virtual_account_id' => $this->virtualAccount->getId()
                ]);

            $block = false;
        }

        return $block;
    }

    /**
     * Find the bank account being paid. We search only amongst
     * the bank accounts that were created by the current provider.
     *
     * @param string $accountNumber
     *
     * @return BankAccount\Entity|null
     */
    protected function getBankAccountFromNumber(string $accountNumber, string $gateway)
    {
        $bankCode = VirtualAccount\Provider::getBankCode($gateway);

        $bankAccount = $this->repo
                            ->bank_account
                            ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $bankCode);

        return $bankAccount;
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
     * @param array  $bankAccountInput
     *
     * @return $this|BankAccount\Entity
     */
    protected function createPayerBankAccount(
        Entity $bankTransfer,
        array $bankAccountInput = [])
    {
        $bankAccount = new BankAccount\Entity;

        $bankAccountInput = PayerBankAccount::getBankAccountInput($bankTransfer, $bankAccountInput);

        $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        if ($bankAccount->getIfscCode() === null)
        {
            $this->trace->warning(
                TraceCode::BANK_TRANSFER_IFSC_CODE_MISSING,
                [
                    'imps_ifsc' => $bankTransfer->getPayerIfsc(),
                ]);
        }

        $bankAccount->merchant()->associate($bankTransfer->merchant);

        $bankAccount->source()->associate($bankTransfer->virtualAccount);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    protected function getReceiver()
    {
        return $this->virtualAccount->bankAccount;
    }
}
