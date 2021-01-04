<?php

namespace RZP\Models\BankTransfer;

use App;
use Cache;
use Config;
use Request;
use Exception;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\VirtualAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use RZP\Exception\LogicException;
use RZP\Models\Feature\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\BankTransfer\HdfcEcms\StatusCode;
use RZP\Models\Payment\Processor\TerminalProcessor;

class Processor extends VirtualAccount\Processor
{
    const PAYER_BANK_ACCOUNT_MAX_LENGTH = 20;

    /*
     * This constant is to raise an alert to the finops team if merchant loads an amount greater than or equal to
     * 5000000000 (5cr in paise) to his virtual account (for banking product).
     */
    const AMOUNT_THRESHOLD_FOR_BANKING = 5000000000;

    const DATE_FORMAT = 'd/m/Y h:i A';

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

        $payeeAccount = $bankTransfer->getPayeeAccount();

        $duplicateBankTransfer = $this->repo
                                    ->bank_transfer
                                    ->findByUtrAndPayeeAccount($utr, $payeeAccount, $useWritePdo = true);

        if ($duplicateBankTransfer === null)
        {
            return false;
        }

        $this->trace->error(
            TraceCode::BANK_TRANSFER_PROCESS_DUPLICATE_UTR,
            [
                'message'           => 'Duplicate UTR received',
                'existing_transfer' => $duplicateBankTransfer->toArrayTrace(),
                'received_utr'      => $utr,
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
        if (($bankTransfer->getGateway() === VirtualAccount\Provider::HDFC_ECMS) and
            ($bankTransfer->getUnexpectedReason() !== null))
        {
            return null;
        }

        $this->checkIfAccountIsBlocked($bankTransfer);

        $deadlockRetryAttempts = 2;

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
        }, $deadlockRetryAttempts);


        // Currently dispatches transaction.created only for bank transfer on banking balance.
        $this->dispatchEventForTransactionCreated($bankTransfer);

        $this->refundOrCapturePayment($bankTransfer);

        return $bankTransfer;
    }

    protected function dispatchEventForTransactionCreated(Base\PublicEntity $bankTransfer)
    {
        if ($bankTransfer->isBalanceTypeBanking() === true)
        {
            $transactionCore = new Transaction\Core;

            if ($this->isLiveMode() === true)
            {
                $transactionCore->dispatchEventForTransactionCreated($bankTransfer->transaction);
            }
            else
            {
                $transactionCore->dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification($bankTransfer->transaction);
            }
        }
    }

    protected function processPaymentForPg(Entity $bankTransfer)
    {
        assertTrue($this->virtualAccount->isBalanceTypePrimary(), 'Attempted processing VA payment incorrectly!');
        assertTrue($this->repo->isTransactionActive(), 'Attempted processing VA payment without transaction!');

        $paymentInput = [];

        try
        {
            // Prepares payment input and creates payment and its transaction etc.
            $paymentInput = $this->getPaymentArray($bankTransfer);

            $terminal = (new TerminalProcessor())->getTerminalForBankTransfer($bankTransfer);

            $gatewayData[Payment\Entity::TERMINAL_ID] = $terminal->getId();

            switch ($bankTransfer->getGateway())
            {
                case VirtualAccount\Provider::HDFC_ECMS:
                    $this->createEcmsPayment($bankTransfer, $paymentInput, $gatewayData);

                    break;

                default:
                    $this->createPaymentOrUnexpected($bankTransfer, $paymentInput, $gatewayData);
            }

            $payment = $this->getPaymentProcessor()->getPayment();

            $bankTransfer->payment()->associate($payment);

            $this->createAndAssociatePayerBankAccount($bankTransfer);

            $this->repo->saveOrFail($bankTransfer);

            // Updates virtual account's stats.
            $this->virtualAccount->updateWithBankTransfer($bankTransfer);

            $this->repo->saveOrFail($this->virtualAccount);
        }
        catch (Exception $ex)
        {
            $this->app['diag']->trackBankTransferEvent(
                EventCode::BANK_TRANSFER_UNEXPECTED_PAYMENT,
                $bankTransfer,
                $ex,
                array_filter(
                    [
                        'error'                     => $ex->getMessage(),
                        Payment\Entity::ORDER_ID    => isset($paymentInput[Payment\Entity::ORDER_ID]) ? $paymentInput[Payment\Entity::ORDER_ID] : null,
                    ]
                )
            );

            throw $ex;
        }
    }

    protected function createUnexpectedPayment(Entity $bankTransfer, array $gatewayData = [])
    {
        $this->virtualAccount = (new VirtualAccount\Core())->createOrFetchSharedVirtualAccount();

        $this->setMerchant();

        $bankTransfer->merchant()->associate($this->merchant);

        $bankTransfer->virtualAccount()->associate($this->virtualAccount);

        $input = $this->getPaymentArray($bankTransfer);

        $this->paymentProcessor = new Payment\Processor\Processor($this->merchant);

        return $this->createPayment($input, $gatewayData);
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
                Entity::UTR          => $bankTransfer->getUtr(),
            ]);

        // Creates a transaction with bank transfer entity as source, merchant's banking balance gets credited.
        list ($txn, $feeSplit) = (new Transaction\Processor\BankTransfer($bankTransfer))->createTransaction();

        $this->repo->saveOrFail($txn);

        // Updates virtual account's stats.
        $this->virtualAccount->updateWithBankTransferForBanking($bankTransfer);

        $this->repo->saveOrFail($this->virtualAccount);

        if ($bankTransfer->getAmount() >= self::AMOUNT_THRESHOLD_FOR_BANKING)
        {
            $time = Carbon::now(Timezone::IST)->getTimestamp();

            $this->trace->info(TraceCode::AMOUNT_THRESHOLD_FOR_BANKING_ALERT,
                [
                    'bank_transfer_id'   => $bankTransfer->getId(),
                    'virtual_account_id' => $this->virtualAccount->getId(),
                    Entity::AMOUNT       => $bankTransfer->getAmount(),
                    Entity::MERCHANT_ID  => $bankTransfer->getMerchantId(),
                    Entity::TIME         => $time,
                    Entity::UTR          => $bankTransfer->getUtr(),
                ]);

            $message = "Merchant load greater than " . self::AMOUNT_THRESHOLD_FOR_BANKING . " for banking product";

            $time = Carbon::createFromTimestamp($time, Timezone::IST)->format(self::DATE_FORMAT);

            $data = [
                Entity::AMOUNT      => $bankTransfer->getAmount(),
                Entity::MERCHANT_ID => $bankTransfer->getMerchantId(),
                Entity::TIME        => $time,
            ];

            $this->app['slack']->queue(
                $message,
                $data,
                [
                    'channel'  => Config::get('slack.channels.x_finops'),
                ]
            );
        }
    }

    protected function createEcmsPayment(&$bankTransfer, array $input, array $gatewayData)
    {
        try
        {
            $this->getPaymentProcessor()->process($input, $gatewayData);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::INFO,
                TraceCode::VIRTUAL_ACCOUNT_FAILED_FOR_ORDER, ['input' => $input]);

            if ($e->getMessage() === PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH)
            {
                $this->pushVaPaymentFailedDueToOrderAmountMismatchEventToLake($input, $e);

                $bankTransfer->setUnexpectedReason(StatusCode::ORDER_AMOUNT_MISMATCH);
            }

            throw $e;
        }
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
            throw new LogicException('Payment made to blocked account', null, $bankTransfer->toArrayTrace());
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

        $virtualAccount = $bankAccount->source;

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
            '_'                         => [
                Payment\Analytics\Entity::LIBRARY => Payment\Analytics\Metadata::PUSH,
            ],
        ];

        $paymentArray = array_merge($paymentArray, $parentPaymentArray);

        if ($this->virtualAccount->hasOrder() === true)
        {
            $order = $this->virtualAccount->entity;

            $paymentArray[Payment\Entity::ORDER_ID] = $order->getPublicId();
        }

        $merchant = $this->virtualAccount->merchant;

        if ($merchant->isFeeBearerCustomerOrDynamic() === true)
        {
            $paymentArray[Payment\Entity::FEE] = (new Core)->getFeesForBankTransfer($bankTransfer, $merchant);
        }

        if (($this->virtualAccount->hasCustomer() === true) and
            ($merchant->isFeatureEnabled(Constants::CHECKOUT_VA_WITH_CUSTOMER) === true))
        {
            $paymentArray[Payment\Entity::CUSTOMER_ID] = $this->virtualAccount->customer->getPublicId();
        }

        return $paymentArray;
    }

    protected function useSharedVirtualAccount(Base\PublicEntity $bankTransfer): bool
    {
        if ($this->virtualAccount === null)
        {
            switch ($bankTransfer->getGateway())
            {
                case VirtualAccount\Provider::HDFC_ECMS:
                    $bankTransfer->setUnexpectedReason(HdfcEcms\StatusCode::TRANSACTION_NOT_FOUND);

                    break;

                default:
                    $bankTransfer->setUnexpectedReason(self::VIRTUAL_ACCOUNT_NOT_FOUND);
            }

            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_UNEXPECTED_PAYMENT,
                [
                    'entity' => $bankTransfer->toArrayTrace(),
                ]);

            $this->app['diag']->trackBankTransferEvent(
                EventCode::BANK_TRANSFER_UNEXPECTED_PAYMENT,
                $bankTransfer,
                null,
                ['error' => self::VIRTUAL_ACCOUNT_NOT_FOUND]
            );

            return true;
        }

        // VA payments for crypto merchants are blocked based on cache key
        if (($this->virtualAccount->merchant->isCategory2Cryptocurrency() === true) and
            ($this->areBankTransfersBlockedForCrypto() === true))
        {
            return true;
        }

        return parent::useSharedVirtualAccount($bankTransfer);
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
                $bankTransfer->toArrayTrace());
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
