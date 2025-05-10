<?php

namespace RZP\Models\BankTransfer;

use App;
use Mail;
use Cache;
use Config;
use Request;
use Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Jobs\Transactions;
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
use RZP\Constants as DefaultConstants;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\GatewayTimeoutException;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankTransfer\HdfcEcms\StatusCode;
use RZP\Models\FundLoadingDowntime\Notifications;
use RZP\Models\Payment\Processor\TerminalProcessor;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankTransfer\Constants as BankTransferConstants;
use RZP\Mail\Merchant\RazorpayX\FundLoadingFailed as FundLoadingFailedMail;
use RZP\Models\Transaction\Processor\Ledger\FundLoading as LedgerFundLoading;

class Processor extends VirtualAccount\Processor
{
    const PAYER_BANK_ACCOUNT_MAX_LENGTH = 20;

    /*
     * This constant is to raise an alert to the finops team if merchant loads an amount greater than or equal to
     * 5000000000 (5cr in paise) to his virtual account (for banking product).
     */
    const AMOUNT_THRESHOLD_FOR_BANKING = 5000000000;

    const DATE_FORMAT = 'd/m/Y h:i A';

    const TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING = 'TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING';

    const ACCOUNT_NUMBER = 'account_number';

    const IFSC_CODE = 'ifsc_code';

    const BANK_TRANSFER_ID = 'bank_transfer_id';

    /**
     * We use the below set of prefixes to decide if the bank transfer belongs to RazorpayX or not.
     * This array needs to be updated if there are any changes to existing prefixes or addition of new prefixes.
     * Also update the arrays 'YES_BANK_PREFIXES' and 'ICICI_BANK_PREFIXES' in FundLoadingDowntime/Notifications.php
     * accordingly.
     * @see Notifications
     */
    const PAYEE_ACCOUNT_PREFIXES_FOR_X = [
        //icici prefixes
        '3434',
        '5656',
        //yesbank prefixes
        '787878',
        '456456',
        //axis prefixes
        VirtualAccount\Provider::AXIS_COMMON_IFSC
    ];

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

        if($bankTransfer->isSkipDuplicateEnabled() === true)
        {

            $bankTransfer->removeSkipDuplicateCheckAttribute();

            return false;
        }

        $utr = $bankTransfer->getUtr();

        $payeeAccount = $bankTransfer->getPayeeAccount();

        $amount = $bankTransfer->getAmount();

        $duplicateBankTransfer = $this->repo
            ->bank_transfer
            ->findByUtrAndPayeeAccountAndAmount($utr, $payeeAccount, $amount, $useWritePdo = true);

        if ($duplicateBankTransfer !== null) {
            $this->trace->error(
                TraceCode::BANK_TRANSFER_PROCESS_DUPLICATE_UTR,
                [
                    'message' => 'Duplicate UTR received',
                    'existing_transfer' => $duplicateBankTransfer->toArrayTrace(),
                    'received_utr' => $utr,
                    Entity::GATEWAY => $bankTransfer->getGateway(),
                    Entity::REQUEST_SOURCE => $bankTransfer->getRequestSource() ?? '',
                ]
            );

            return true;
        }

        // Since receiving a duplicate UTR with different Payee Account Number is a rare scenario,
        // we can log it and alert concerned person which will help in identifying issue earlier
        // in case of any wrong info received from bank.
        $duplicateUtr = $this->repo
            ->bank_transfer
            ->findByUtr($utr, $useWritePdo = true);

        if ($duplicateUtr !== null) {
            $traceInfo = [
                'message' => 'Duplicate UTR received with different Payee Account Number',
                'existing_transfer' => $duplicateUtr->toArrayTrace(),
                'received_utr' => $utr,
                Entity::GATEWAY => $bankTransfer->getGateway(),
                Entity::REQUEST_SOURCE => $bankTransfer->getRequestSource() ?? '',
            ];

            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESS_WITH_EXISTING_UTR,
                $traceInfo
            );

            (new SlackNotification)->send(
                'Possible money loss, please evaluate.',
                $traceInfo,
                null,
                1,
                'x-finops');
        }

        return false;
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
     * @param Base\PublicEntity $bankTransfer
     * @return null|Base\PublicEntity
     */
    protected function processPayment(Base\PublicEntity $bankTransfer)
    {
        if (($bankTransfer->getGateway() === VirtualAccount\Provider::HDFC_ECMS) and
            ($bankTransfer->getUnexpectedReason() !== null)) {
            return null;
        }

        $this->checkIfAccountIsBlocked($bankTransfer);

        $deadlockRetryAttempts = 2;

        /**
         * assigning to a temporary variable and then cloning it in the transaction. In case the transaction fails the
         * attribute 'exists' of the bank transfer entity doesn't reset, in saveOrFail the exists flag is checked and based
         * on this flag it identifies whether an insert query or an update query has to be performed. In case of retries,
         * since in last attempt while performing saveOrFail this flag was set but not committed to Db due to some failure
         * in the transaction. In retry, since this flag was already set saveOrFail didn't attempt to perform the insert query.
         * Hence cloning it.
         * slack thread for reference: https://razorpay.slack.com/archives/C013868TRK4/p1624456352459200?thread_ts=1623937828.416500&cid=C013868TRK4
         */
        $tempBankTransfer = $bankTransfer;

        $isCollectXBankTransferPayment = false;

        if ($bankTransfer->isCollectXBankTransfer() === true)
        {
            $isCollectXBankTransferPayment = true;

            // Removing the attribute as we don't need it anymore and the flag defined above can be used
            $bankTransfer->removeCollectXAttributes();
        }

        $bankTransfer = $this->repo->transaction(function () use ($tempBankTransfer, $isCollectXBankTransferPayment) {
            $bankTransfer = clone $tempBankTransfer;

            // Bank transfer's relation association
            $bankTransfer->merchant()->associate($this->merchant);

            $bankTransfer->virtualAccount()->associate($this->virtualAccount);

            $bankTransfer->balance()->associate($this->virtualAccount->balance);

            $this->verifyPayerUsingBankingAccountTpvIfEnabledAndSaveBankTransfer($bankTransfer, false, $isCollectXBankTransferPayment);

            $balanceType = $this->virtualAccount->getBalanceType();

            // Logs to get the bank transfer id as well
            $this->trace->info(TraceCode::BANK_TRANSFER_CREATED,
                [
                    'balance_type' => $balanceType,
                    'virtual_account_id' => $this->virtualAccount->getId(),
                    'bank_transfer_id' => $bankTransfer->getId(),
                    'utr' => $bankTransfer->getUtr(),
                    'unexpected_reason' => $bankTransfer->getUnexpectedReason(),
                    Entity::GATEWAY => $bankTransfer->getGateway(),
                    Entity::REQUEST_SOURCE => $bankTransfer->getRequestSource() ?? '',
                    'isCollectxBankTransfer' => $isCollectXBankTransferPayment,
                ]
            );

            switch ($balanceType) {
                case Balance\Type::PRIMARY:
                    $this->processPaymentForPg($bankTransfer);
                    break;

                case Balance\Type::BANKING:
                    if ($isCollectXBankTransferPayment === true)
                    {
                        $this->processPaymentForPg($bankTransfer, $isCollectXBankTransferPayment);
                    }
                    else
                    {
                        $this->processPaymentForBanking($bankTransfer);
                    }
                    break;

                default:
                    throw new LogicException(
                        'Invalid balance type, could not process payment.',
                        null,
                        compact('balanceType'));
            }

            return $bankTransfer;

        }, $deadlockRetryAttempts);

        // feature flag based call to Ledger service
        if ($this->virtualAccount->isBalanceTypeBanking() === true and $isCollectXBankTransferPayment === false) {
            if ($bankTransfer->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true) {

                $ledgerJournalResponse = $this->processLedgerForReverseShadow($bankTransfer);

                if (($ledgerJournalResponse != null)  and ($this->isLedgerReverseShadowLatestTxnBalanceExperimentEnabled($bankTransfer->getMerchantId()) === false)) {

                    $this->trace->info(TraceCode::TRANSACTION_CREATED_WEBHOOK_SYNC_FIRE,
                        ["merchantId" =>
                            $this->merchant->getMerchantId(),
                            "Transaction" => $ledgerJournalResponse['body'][Entity::ID]
                        ]);

                    (new Transaction\Core)->dispatchEventForLedgerEntryCreated($ledgerJournalResponse['body'][Entity::ID]);
                }

            } else {
                $this->processLedgerForShadow($bankTransfer);
            }
        }

        // Currently dispatches transaction.created only for bank transfer on banking balance.
        $this->sendEventForTransactionCreated($bankTransfer, $isCollectXBankTransferPayment);

        $payment = $bankTransfer->payment;

        if($payment !== null and $payment->identifierForCollectxPayment() === true)
        {
            $payment->bankTransfer = $bankTransfer;

            $this->triggerActionsPostAutoCaptureForCollectXPayment($payment);
        }

        $this->refundOrCapturePayment($bankTransfer);

        return $bankTransfer;
    }

    protected function triggerActionsPostAutoCaptureForCollectXPayment(Payment\Entity $payment): void
    {
        $this->trace->info(
            TraceCode::POST_PAYMENT_AUTO_CAPTURE_ACTIONS_TRIGGERED,
            [
                'payment' => $payment,
            ]);

        $virtualAccountCore = new VirtualAccount\Core;

        $virtualAccountCore->eventVirtualAccountCredited($payment);
    }

    protected function processLedgerForShadow(Entity $bankTransfer)
    {
        try {
            // Fetching terminal to get the terminal_id which will be the identifier to uniquely
            // identify accounts in case of fund loading.
            $terminal = (new TerminalProcessor())->getTerminalForBankTransfer($bankTransfer);

            // Pushing transaction to ledger which will create this transaction in ledger DB.
            $this->processLedgerFundLoading($bankTransfer, $terminal->getPublicId(), $terminal->getAccountType());
        } catch (\Throwable $ex) {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_FUND_LOADING_TERMINAL_ID_NOT_FOUND,
                [
                    'error' => $ex->getMessage(),
                    self::BANK_TRANSFER_ID => $bankTransfer->getId(),
                ]);
        }
    }

    /**
     * @param Entity $bankTransfer
     *
     *
     * This function proceses txn in reverse shadow mode
     * We call ledger in sync and use ledger response to
     * create txn in api db in async
     */
    protected function processLedgerForReverseShadow(Entity $bankTransfer)
    {
        // create journal in sync

        // Fetching terminal to get the terminal_id which will be the identifier to uniquely
        // identify accounts in case of fund loading.
        $ledgerResponse = null;
        $terminal = (new TerminalProcessor())->getTerminalForBankTransfer($bankTransfer);

        $ledgerPayload = (new LedgerFundLoading)->createPayloadForJournalEntry($bankTransfer, $terminal->getPublicId(), $terminal->getAccountType());
        try {
            $ledgerResponse = (new LedgerFundLoading)->createJournalEntry($ledgerPayload);
            $bankTransfer->setStatus(Status::PROCESSED);
            $this->repo->saveOrFail($bankTransfer);
        } catch (\Throwable $ex) {
            // trace and ignore exception as it will be retries in async
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR_IN_CREDIT_FLOW,
                [
                    'bank_transfer_id' => $bankTransfer->getId(),
                    'ledger_request' => $ledgerPayload,
                ]
            );
        }
        return $ledgerResponse;
    }

    protected function sendEventForTransactionCreated(Entity $bankTransfer, $isCollectXPayment = false)
    {
        if ($isCollectXPayment === true)
        {
            // We do not need to dispatch for collectX payments
            return;
        }
        if ($bankTransfer->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false) {
            if ($bankTransfer->isBalanceTypeBanking() === true) {
                $this->dispatchEventForTransactionCreated($bankTransfer, $bankTransfer->transaction);
            }
        }
    }

    public function dispatchEventForTransactionCreatedForTxnDependentMerchants(Base\PublicEntity $bankTransfer, Transaction\Entity $transaction): void
    {
        if ($this->isLedgerReverseShadowLatestTxnBalanceExperimentEnabled($bankTransfer->getMerchantId()) === true)
        {

            $this->trace->info(TraceCode::TRANSACTION_CREATED_WEBHOOK_ASYNC_FIRE,
                [
                    'transactionId' => $transaction->getId(),
                    'merchantId' => $bankTransfer->getMerchantId()
                ]);

            $this->dispatchEventForTransactionCreated($bankTransfer, $transaction);
        }

    }

    public function dispatchEventForTransactionCreated(Base\PublicEntity $bankTransfer, Transaction\Entity $transaction)
    {
        if ($bankTransfer->isBalanceTypeBanking() === true) {
            $transactionCore = new Transaction\Core;

            if ($this->isLiveMode() === true) {
                $transactionCore->dispatchEventForTransactionCreated($transaction);
            } else {
                $transactionCore->dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification($transaction);
            }
        }
    }
    public function dispatchEventForLedgerTransactionCreated(Base\PublicEntity $bankTransfer, string $txnId, string $merchantId)
    {
        if ($bankTransfer->isBalanceTypeBanking() === true) {
            $transactionCore = new Transaction\Core;

            if($this->isLedgerReverseShadowBtAsyncWebhookExperimentEnabled($merchantId) === true) {
                if ($this->isLiveMode() === true) {
                    $transactionCore->dispatchEventForLedgerTransactionCreated($txnId, $merchantId);
                } else {
                    $transactionCore->dispatchEventForLedgerTransactionCreatedWithoutEmailOrSmsNotification($txnId, $merchantId);
                }
            }
        }
    }

    protected function processPaymentForPg(Entity $bankTransfer, bool $isCollectXBankTransferPayment = false)
    {
        if (!$isCollectXBankTransferPayment)
        {
            assertTrue($this->virtualAccount->isBalanceTypePrimary(), 'Attempted processing VA payment incorrectly!');
        }

        assertTrue($this->repo->isTransactionActive(), 'Attempted processing VA payment without transaction!');

        $paymentInput = [];

        try {
            // Prepares payment input and creates payment and its transaction etc.
            $paymentInput = $this->getPaymentArray($bankTransfer);

            $terminal = $this->fetchTerminal($bankTransfer, $isCollectXBankTransferPayment);

            $gatewayData[Payment\Entity::TERMINAL_ID] = $terminal->getId();

            switch ($bankTransfer->getGateway()) {
                case VirtualAccount\Provider::HDFC_ECMS:
                    $this->createEcmsPayment($bankTransfer, $paymentInput, $gatewayData);

                    break;

                default:
                    $this->createPaymentOrUnexpected($bankTransfer, $paymentInput, $gatewayData, $isCollectXBankTransferPayment);
            }

            $payment = $this->getPaymentProcessor()->getPayment();

            $bankTransfer->payment()->associate($payment);

            // Doing this here as in case of Banking balance flow, we have associated the payer bank account before this
            // and it can come here in case of banking account tpv failure.
            if (empty($bankTransfer->payerBankAccount) === true) {
                $this->createAndAssociatePayerBankAccount($bankTransfer);
            }

            $bankTransfer->setStatus(Status::PROCESSED);

            $this->repo->saveOrFail($bankTransfer);

            // Updates virtual account's stats.
            $this->virtualAccount->updateWithBankTransfer($bankTransfer);

            $this->repo->saveOrFail($this->virtualAccount);

            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_AMOUNT_FIELDS_UPDATED, [
                    'amount'             => $bankTransfer->getAmount(),
                    'virtual_account_id' => $this->virtualAccount->getId(),
                ]
            );

        } catch (Exception $ex) {
            $this->app['diag']->trackBankTransferEvent(
                EventCode::BANK_TRANSFER_UNEXPECTED_PAYMENT,
                $bankTransfer,
                $ex,
                array_filter(
                    [
                        'error' => $ex->getMessage(),
                        Payment\Entity::ORDER_ID => isset($paymentInput[Payment\Entity::ORDER_ID]) ? $paymentInput[Payment\Entity::ORDER_ID] : null,
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
                'bank_transfer_id' => $bankTransfer->getId(),
                'virtual_account_id' => $this->virtualAccount->getId(),
                Entity::UTR => $bankTransfer->getUtr(),
                Entity::GATEWAY => $bankTransfer->getGateway(),
                Entity::REQUEST_SOURCE => $bankTransfer->getRequestSource() ?? '',
            ]);

        // In case reverse shadow feature is false, we create transaction entry in sync
        if ($bankTransfer->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false) {
            // Creates a transaction with bank transfer entity as source, merchant's banking balance gets credited.
            list ($txn, $feeSplit) = (new Transaction\Processor\BankTransfer($bankTransfer))->createTransaction();

            $this->repo->saveOrFail($txn);
            // since for a shadow flow a transaction creation in
            // API means BT is processed
            $bankTransfer->setStatus(Status::PROCESSED);


            $bankTransfer->setTransactionId($txn->getId());

            $this->repo->saveOrFail($bankTransfer);
        }
        // Updates virtual account's stats.
        $this->virtualAccount->updateWithBankTransferForBanking($bankTransfer);

        $this->repo->saveOrFail($this->virtualAccount);

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_AMOUNT_FIELDS_UPDATED, [
                'amount'             => $bankTransfer->getAmount(),
                'virtual_account_id' => $this->virtualAccount->getId(),
            ]
        );

        if ($bankTransfer->getAmount() >= self::AMOUNT_THRESHOLD_FOR_BANKING) {
            $time = Carbon::now(Timezone::IST)->getTimestamp();

            $this->trace->info(TraceCode::AMOUNT_THRESHOLD_FOR_BANKING_ALERT,
                [
                    'bank_transfer_id' => $bankTransfer->getId(),
                    'virtual_account_id' => $this->virtualAccount->getId(),
                    Entity::AMOUNT => $bankTransfer->getAmount(),
                    Entity::MERCHANT_ID => $bankTransfer->getMerchantId(),
                    Entity::TIME => $time,
                    Entity::UTR => $bankTransfer->getUtr(),
                ]);

            $message = "Merchant load greater than " . self::AMOUNT_THRESHOLD_FOR_BANKING . " for banking product";

            $time = Carbon::createFromTimestamp($time, Timezone::IST)->format(self::DATE_FORMAT);

            $data = [
                Entity::AMOUNT => $bankTransfer->getAmount(),
                Entity::MERCHANT_ID => $bankTransfer->getMerchantId(),
                Entity::TIME => $time,
            ];

            $this->app['slack']->queue(
                $message,
                $data,
                [
                    'channel' => Config::get('slack.channels.x_finops'),
                ]
            );
        }

        try {
            // If the fund loading has happened to the common merchant account, we need to refund the money
            // back by creating a payout to the payer account.
            if ($bankTransfer->virtualAccount->getId() === VirtualAccount\Entity::SHARED_ID_BANKING) {
                // If the payee account is of IDFC bank VA then we need to raise the slack alert
                if($bankTransfer[Entity::PAYEE_IFSC] == VirtualAccount\Provider::IDFC_COMMON_IFSC &&
                    ( in_array(substr($bankTransfer[Entity::PAYEE_ACCOUNT], 0, 4) , VirtualAccount\Provider::IDFC_VA_PREFIX) === true) &&
                    empty($bankTransfer[Entity::PAYER_IFSC]))
                {
                    // For such cases we need to raise a slack alert for manual refund creation.
                    // This manual refund creation will be done by the finops team.
                    // We will create a bank transfer in this case, assuming payout will be processed manually
                    $this->trace->info(TraceCode::IDFC_VA_MANUAL_REFUND_CREATION, [
                        'virtual_account_id' => $bankTransfer->getVirtualAccountId(),
                        'amount' => $bankTransfer->getAmount(),
                        'payer_bank_account_id' => $bankTransfer->getPayerBankAccountId(),
                        'utr' => $bankTransfer->getUtr(),
                        'bank_transfer_id' => $bankTransfer->getId(),
                    ]);

                    $metric = \RZP\Models\Payout\Metric::FUND_LOADING_VA_CALLBACK_FAILURE;

                    $this->trace->count(
                        $metric,
                        [
                            'trace_code' => TraceCode::IDFC_VA_MANUAL_REFUND_CREATION,
                            'route_name' => $this->app['api.route']->getCurrentRouteName()
                        ]);

                    return;
                }

                (new PayoutsClient)->refundFundLoadingViaPayout($bankTransfer);
            }
        } catch (\Throwable $ex) {
            $message = null;

            if ((method_exists($ex, 'getMessage') === true) and
                ($ex->getMessage() === 'Call to a member function getName() on null')) {

                $message = 'Payer Bank Account Creation Failure for Refund';
            }

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::RX_FUND_LOADING_REFUND_PAYOUT_CREATION_FAILED, [
                'additional_info' => $message
            ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_FUND_LOADING_REFUND_PAYOUT_CREATION_FAILED);
        }
    }

    protected function createEcmsPayment(&$bankTransfer, array $input, array $gatewayData)
    {
        try {
            $this->getPaymentProcessor()->process($input, $gatewayData);
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::INFO,
                TraceCode::VIRTUAL_ACCOUNT_FAILED_FOR_ORDER, ['input' => $input]);

            if ($e->getMessage() === PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH) {
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

        if (in_array($payeeAccount, $blockedAccounts, true) === true) {
            throw new LogicException('Payment made to blocked account', null, $bankTransfer->toArrayTrace());
        }
    }

    private function getBankTransferEntity(Base\PublicEntity $bankTransfer): Entity
    {
        if (($bankTransfer instanceof Entity) === false) {
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

        if ($bankAccount === null) {
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

        $gatewayIfsc = VirtualAccount\Provider::IFSC[$bankTransfer->getGateway()];

        if (empty($gatewayIfsc) === true) {
            throw new LogicException('Gateway not matches the bank account', null);
        }

        if (($gatewayIfsc !== $this->virtualAccount->bankAccount->getIfscCode()) and ($this->virtualAccount->bankAccount2 !== null) and ($gatewayIfsc === $this->virtualAccount->bankAccount2->getIfscCode())) {
            $parentPaymentArray['receiver']['id'] = $this->virtualAccount->bankAccount2->getPublicId();
        }

        $paymentArray = [
            Payment\Entity::CURRENCY => Currency::INR,
            Payment\Entity::METHOD => Payment\Method::BANK_TRANSFER,
            Payment\Entity::AMOUNT => $bankTransfer->getAmount(),
            Payment\Entity::DESCRIPTION => $bankTransfer->getDescription() ?? '',
            '_' => [
                Payment\Analytics\Entity::LIBRARY => Payment\Analytics\Metadata::PUSH,
            ],
        ];

        $paymentArray = array_merge($paymentArray, $parentPaymentArray);

        if ($this->virtualAccount->hasOrder() === true) {
            $order = $this->virtualAccount->entity;

            $paymentArray[Payment\Entity::ORDER_ID] = $order->getPublicId();
        }

        $merchant = $this->virtualAccount->merchant;

        if ($merchant->isFeeBearerCustomerOrDynamic() === true) {
            $paymentArray[Payment\Entity::FEE] = (new Core)->getFeesForBankTransfer($bankTransfer, $merchant);
        }

        if (($this->virtualAccount->hasCustomer() === true) and
            ($merchant->isFeatureEnabled(Constants::CHECKOUT_VA_WITH_CUSTOMER) === true)) {
            $paymentArray[Payment\Entity::CUSTOMER_ID] = $this->virtualAccount->customer->getPublicId();
        }

        return $paymentArray;
    }

    protected function useSharedVirtualAccount(Base\PublicEntity $bankTransfer): bool
    {
        if ($this->virtualAccount === null) {
            switch ($bankTransfer->getGateway()) {
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
            ($this->areBankTransfersBlockedForCrypto() === true)) {
            return true;
        }

        return parent::useSharedVirtualAccount($bankTransfer);
    }

    protected function areBankTransfersBlockedForCrypto(): bool
    {
        try {
            $block = (bool)Cache::get(ConfigKey::BLOCK_BANK_TRANSFERS_FOR_CRYPTO);
        } catch (\Throwable $ex) {
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
            ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $bankCode, true);

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
        try {
            $bankAccount = $this->createPayerBankAccount($bankTransfer);

            $bankTransfer->payerBankAccount()->associate($bankAccount);
        } catch (Exception $ex) {
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
     * @param array $bankAccountInput
     *
     * @return $this|BankAccount\Entity
     */
    protected function createPayerBankAccount(
        Entity $bankTransfer,
        array  $bankAccountInput = [])
    {
        $bankAccount = new BankAccount\Entity;

        $bankAccountInput = PayerBankAccount::getBankAccountInput($bankTransfer, $bankAccountInput);

        $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        if ($bankAccount->getIfscCode() === null) {
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

    /*
     * This method checks if Balance type is banking or not -
     * 1. If not banking, save bank transfer.
     * 2. If banking, check whether TPV is enabled for the merchant or not -
     *      2.1. If no, save bank transfer.
     *      2.2. If yes, check whether tpv account exists for the payee details -
     *              2.2.1. If yes, save bank transfer.
     *              2.2.2. If no, dissociate balance, virtual account, and merchant from bank transfer and re-associate
     *                     with the shared virtual account and it's corresponding merchant and primary balance. This is
     *                     done so as to make the transaction happen as if it were to a invalid payee account and then
     *                     get refunded eventually.
     */
    protected function verifyPayerUsingBankingAccountTpvIfEnabledAndSaveBankTransfer(
        Entity $bankTransfer,
        bool   $isValidationFlow = false,
        bool   $isCollectXBankTransferPayment = false)

    {
        $balanceType = $this->virtualAccount->getBalanceType();

        $this->trace->info(TraceCode::BANK_TRANSFER_BEFORE_SAVE_DETAILS,
            [
                'balance_type' => $balanceType,
                'virtual_account_id' => $this->virtualAccount->getId(),
                'validation_flow' => $isValidationFlow,
                'isCollectXBankTransferPayment' => $isCollectXBankTransferPayment,
            ]
        );

        if (($balanceType === Balance\Type::BANKING) and
            ($isCollectXBankTransferPayment === false))
        {
            if (!$isValidationFlow) {
                $this->createAndAssociatePayerBankAccount($bankTransfer);

                $payerDetails = [
                    BankAccount\Entity::ACCOUNT_NUMBER =>
                        PayerBankAccount::getBankAccountInput($bankTransfer)[BankAccount\Entity::ACCOUNT_NUMBER],
                ];
            } else {
                $payerAccountNumber = PayerBankAccount::getPayerAccount($bankTransfer);

                $payerDetails = [
                    BankAccount\Entity::ACCOUNT_NUMBER => $payerAccountNumber,
                ];
            }

            /*
             * If the payer account is globally whitelisted, we don't have to check the tpv flow at all.
             * Hence, returning directly from here.
             */
            if ($this->isGloballyWhitelistedPayerAccount($payerDetails) === true) {
                if (!$isValidationFlow) {
                    $this->repo->saveOrFail($bankTransfer);
                }

                // Logs to get the bank transfer id as well
                $this->trace->info(
                    TraceCode::GLOBAL_WHITELISTED_ACCOUNT_FUND_LOADING_FOR_BANKING_ACCOUNT_BANK_TRANSFER_CREATED,
                    [
                        'balance_type' => $balanceType,
                        'virtual_account_id' => $this->virtualAccount->getId(),
                        'bank_transfer_id' => $bankTransfer->getId(),
                        'validation_flow' => $isValidationFlow
                    ]);

                return true;
            }

            $merchantId = $this->virtualAccount->getMerchantId();

            // This provides a granular approach to disable tpv for some specific merchants.
            $disableTpvFeature = $this->merchant->isFeatureEnabled(Feature\Constants::DISABLE_TPV_FLOW);

            // We also disable the TPV, if the transfer is for the RazorpayX common merchant.
            if ($bankTransfer->getVirtualAccountId() === VirtualAccount\Entity::SHARED_ID_BANKING) {
                $disableTpvFeature = true;

                $bankTransfer->setExpected(false);
            }

            $balanceId = $this->virtualAccount->getBalanceId();

            $this->trace->info(TraceCode::FUND_LOADING_FOR_BANKING_ACCOUNT_TRIGGERED,
                [
                    'disable_tpv_feature' => $disableTpvFeature,
                    'merchant_id' => $merchantId,
                    'balance_id' => $balanceId,
                    'validation_flow' => $isValidationFlow
                ]
            );

            $payrollValidationViaX = $this->virtualAccount->merchant->isFeatureEnabled(Constants::PAYROLL_SAV);

            if ($payrollValidationViaX === true && $disableTpvFeature === false)
            {
                $isValid = $this->handlePayrollTpv($bankTransfer, $merchantId, $balanceId, $isValidationFlow);

                if ($isValidationFlow)
                {
                    return $isValid;
                }

                if ($isValidationFlow === false and $isValid === false)
                {
                    $this->handleNonTpvAccount($bankTransfer, $merchantId, $balanceId);

                    return false;
                }
            }

            /* This checks if tpv is not disabled via the disable feature flag, tpv checks are applied on the bank
               transfer.
               We only check for tpv in live mode as in test mode this check shouldn't exist.
             */
            else if (($disableTpvFeature === false) and
                ($this->isLiveMode() === true)) {
                $payerAccountNumber = $payerDetails[BankAccount\Entity::ACCOUNT_NUMBER];

                $bankingAccountTpv = $this->repo->banking_account_tpv
                    ->getApprovedActiveTpvAccountWithPayerAccountNumber(
                        $merchantId,
                        $balanceId,
                        $payerAccountNumber);

                if (empty($bankingAccountTpv) === false) {
                    $this->trace->info(TraceCode::TPV_ACCOUNT_FUND_LOADING_FOR_BANKING_ACCOUNT_TRIGGERED,
                        [
                            'disable_tpv_feature' => $disableTpvFeature,
                            'merchant_id' => $merchantId,
                            'balance_id' => $balanceId,
                            'banking_account_tpv_id' => $bankingAccountTpv->getId(),
                            'validation_flow' => $isValidationFlow
                        ]
                    );

                    if ($isValidationFlow) {
                        return true;
                    }
                } else {
                    $this->trace->info(TraceCode::NON_TPV_ACCOUNT_FUND_LOADING_FOR_BANKING_ACCOUNT_TRIGGERED,
                        [
                            'disable_tpv_feature' => $disableTpvFeature,
                            'merchant_id' => $merchantId,
                            'balance_id' => $balanceId,
                            'validation_flow' => $isValidationFlow
                        ]
                    );

                    if ($isValidationFlow) {
                        return false;
                    }

                    $this->handleNonTpvAccount($bankTransfer, $merchantId, $balanceId);

                    return false;
                }
            }
        }

        if (!$isValidationFlow) {
            // this will record BT in created state.
            $this->repo->saveOrFail($bankTransfer);
        }

        return true;
    }

    //
    // NOTE: After the function `dissociateExpectedRelationsForBankTransfer`, we associate the
    // bank_transfer to the shared razorpay virtual account. We are saving the original merchant
    // that the transfer was meant to go to so that we can send that merchant an email regarding
    // their failed fund loading attempt
    //
    public function handleNonTpvAccount($bankTransfer, $merchantId, $balanceId)
    {
        $actualMerchantId = $bankTransfer->getMerchantId();

        $this->dissociateExpectedRelationsForBankTransfer($bankTransfer);

        $this->setParamsToEnsurePaymentIsNotCaptured($bankTransfer);

        $this->virtualAccount = (new VirtualAccount\Core)->fetchSharedBankingVirtualAccount();

        $bankTransfer->setExpected(false);

        $this->merchant = $this->virtualAccount->merchant;

        $this->associateExpectedRelationsForBankTransfer($bankTransfer);

        // SaveOrFail needs to be done before the send mail, because id is created when entity is saved.
        $this->repo->saveOrFail($bankTransfer);

        // Logs to get the bank transfer id as well
        $this->trace->info(TraceCode::NON_TPV_ACCOUNT_FUND_LOADING_FOR_BANKING_ACCOUNT_BANK_TRANSFER_CREATED,
            [
                'disable_tpv_feature' => false,
                'merchant_id' => $merchantId,
                'balance_id' => $balanceId,
                'bank_transfer_id' => $bankTransfer->getId(),
            ]
        );

        $this->sendFundLoadingFailedEmail($bankTransfer->getId(), $actualMerchantId);
    }

    public function handlePayrollTpv($bankTransfer, $merchantID, $balanceID, $isValidationFlow): bool
    {
        $xPayrollService = $this->app['xpayroll'];

        $response = $xPayrollService->sendPayrollTpvRequestAndGetResponse($bankTransfer, $this->mode);

        if (empty($response) === false && $response['is_valid'] === true)
        {
            $this->trace->info(TraceCode::TPV_ACCOUNT_FUND_LOADING_FOR_BANKING_ACCOUNT_TRIGGERED,
                [
                    'disable_tpv_feature' => false,
                    'merchant_id' => $merchantID,
                    'balance_id' => $balanceID,
                    'validation_flow' => $isValidationFlow
                ]
            );

            return true;
        }
        else
        {
            $this->trace->info(TraceCode::NON_TPV_ACCOUNT_FUND_LOADING_FOR_BANKING_ACCOUNT_TRIGGERED,
                [
                    'disable_tpv_feature' => false,
                    'merchant_id' => $merchantID,
                    'balance_id' => $balanceID,
                    'validation_flow' => $isValidationFlow
                ]
            );

            return false;
        }
    }

    /*
     * This method is used to check whether the payer account is from a globally supported list present saved on redis,
     * if yes, we don't check for tpv flow at all as this is always enabled for everyone. This is done because we need
     * to whitelist account details of Razorpay as these are used for settling PG funds to X VA (via settlement or
     * settlement on-demand).
     */
    protected function isGloballyWhitelistedPayerAccount(array $payerDetails)
    {
        /*
         * The value for the key will be an array of arrays with the following structure
         * config:rx_globally_whitelisted_payer_accounts_for_fund_loading => [
         *  [
         *      'account_number' => {{account_number}}
         *      'ifsc_code'      => {{ifsc_code}}
         *  ]
         *  [
         *      'account_number' => {{account_number}}
         *      'ifsc_code'      => {{ifsc_code}}
         *  ]
         *  .
         *  .
         *  .
         * ]
         */
        $globalWhitelistedPayerAccounts = (new AdminService)->getConfigKey(
            [
                'key' => ConfigKey::RX_GLOBALLY_WHITELISTED_PAYER_ACCOUNTS_FOR_FUND_LOADING
            ]
        );

        $payerAccountNumber = $payerDetails[BankAccount\Entity::ACCOUNT_NUMBER];

        $isGloballyWhitelistedPayerAccount = false;

        foreach ($globalWhitelistedPayerAccounts as $globalWhitelistedPayerAccount) {
            if (isset($globalWhitelistedPayerAccount[self::ACCOUNT_NUMBER]) === true) {
                $globalWhitelistedPayerAccountAccountNumber = $globalWhitelistedPayerAccount[self::ACCOUNT_NUMBER];

                if ($globalWhitelistedPayerAccountAccountNumber === $payerAccountNumber) {
                    $isGloballyWhitelistedPayerAccount = true;

                    break;
                }
            }
        }

        return $isGloballyWhitelistedPayerAccount;
    }

    protected function dissociateExpectedRelationsForBankTransfer(Entity &$bankTransfer)
    {
        $bankTransfer->merchant()->dissociate();

        $bankTransfer->virtualAccount()->dissociate();

        $bankTransfer->balance()->dissociate();

        $bankTransfer->load('merchant', 'virtualAccount', 'balance');
    }

    protected function setParamsToEnsurePaymentIsNotCaptured(Entity &$bankTransfer)
    {
        // This ensures payment is not captured in refundOrCapturePayment.
        $bankTransfer->setExpected(false);

        // This is set for recon purpose so that we can know why the payment was refunded.
        $bankTransfer->setUnexpectedReason(self::TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING);
    }

    protected function associateExpectedRelationsForBankTransfer(Entity &$bankTransfer)
    {
        $bankTransfer->merchant()->associate($this->merchant);

        $bankTransfer->virtualAccount()->associate($this->virtualAccount);

        $bankTransfer->balance()->associate($this->virtualAccount->balance);
    }

    protected function sendFundLoadingFailedEmail(string $bankTransferId, string $actualMerchantId)
    {
        $fundLoadingFailedMail = new FundLoadingFailedMail($bankTransferId, $actualMerchantId);

        Mail::queue($fundLoadingFailedMail);
    }

    /**
     * @param Entity $bankTransfer
     * @param string $terminalId
     * @param $terminalAccountType
     * Push to ledger sns when Fund Loading is initiated. This will create this transaction in ledger DB.
     */
    protected function processLedgerFundLoading(Entity $bankTransfer, string $terminalId, $terminalAccountType)
    {
        // In case env variable ledger.enabled is false, return.
        if ($this->app['config']->get('applications.ledger.enabled') === false) {
            return;
        }

        // If the mode is live but the merchant does not have the ledger journal write feature, we return.
        if (($this->isLiveMode()) and
            ($bankTransfer->merchant->isFeatureEnabled(Feature\Constants::LEDGER_JOURNAL_WRITES) === false)) {
            return;
        }

        (new LedgerFundLoading)->pushTransactionToLedger($bankTransfer,
            LedgerFundLoading::FUND_LOADING_PROCESSED,
            $terminalId,
            $terminalAccountType);
    }

    /**
     * fetch terminal from cache if experiment in on.
     * @param Entity $bankTransfer
     *
     * @return mixed|Terminal\Entity
     */
    protected function fetchTerminal(Entity $bankTransfer, bool $isCollectxBankTransferPayment = false)
    {
        $gateway = Payment\Gateway::$bankTransferProviderGateway[$bankTransfer->getGateway()];
        $merchantId = $bankTransfer->getMerchantId();

//        $terminalCaching = $this->app->razorx->getTreatment(
//            $merchantId,
//            Merchant\RazorxTreatment::SMART_COLLECT_TERMINAL_CACHING,
//            $this->mode);

        // disabling terminal caching in bank transfer flow due to an issue - https://razorpay.atlassian.net/browse/EPA-605
        $terminalCaching = 'off';

        $getTerminalCallback = function () use ($bankTransfer, $isCollectxBankTransferPayment) {
            return (new TerminalProcessor())->getTerminalForBankTransfer($bankTransfer, true, $isCollectxBankTransferPayment);
        };

        $terminalFilters = function ($terminalAttributes) use ($gateway) {
            return ($terminalAttributes[Terminal\Entity::GATEWAY] === $gateway);
        };

        if ($terminalCaching === Merchant\RazorxTreatment::RAZORX_VARIANT_ON) {
            $terminal = (new VirtualAccount\Provider())->getTerminals($merchantId, $getTerminalCallback, $terminalFilters);
        } else {
            $terminal = $getTerminalCallback();
        }

        return $terminal;
    }
    protected function isLedgerReverseShadowLatestTxnBalanceExperimentEnabled($merchantId): bool
    {
        $requestPayload = [
            "id" => $merchantId,
            "experiment_name" =>  Merchant\RazorxTreatment::LEDGER_REVERSE_SHADOW_LATEST_TXN_BALANCE,
            'request_data'  => json_encode(['id' => $merchantId])
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($requestPayload, Merchant\RazorxTreatment::VARIANT_ENABLE);

    }

    protected function isLedgerReverseShadowBtAsyncWebhookExperimentEnabled($merchantId): bool
    {
        $mode = $this->isLiveMode() ? 'live' : 'test';
        $requestPayload = [
            "id" => $merchantId,
            "experiment_name" =>  Merchant\RazorxTreatment::LEDGER_TRANSACTION_ASYNC_WEBHOOK_BT,
            'request_data'  => json_encode(['id' => $merchantId, 'mode' => $mode])
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($requestPayload, Merchant\RazorxTreatment::VARIANT_ENABLE);
    }

    public function isWebhookSyncFiringEnabled($merchantId,$experimentName,string $checkVariant)
    {
        try {
            $experimentId = $this->app['config']->get($experimentName);

            $response = $this->app['splitzService']->evaluateRequest([
                'id' => $merchantId,
                'experiment_id' => $experimentId,
            ]);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'merchant_id' => $merchantId,
                'experiment_id' => $experimentId,
                'result' => $response
            ]);
        } catch (\Throwable $e) {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'merchant_id' => $merchantId,
                'experiment_id' => $this->app['config']->get($experimentName) ?? null
            ]);
            return false;

        }
        return $response['response']['variant']['name'] == $checkVariant;


    }
}
