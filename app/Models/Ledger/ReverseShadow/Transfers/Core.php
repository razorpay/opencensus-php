<?php

namespace RZP\Models\Ledger\ReverseShadow\Transfers;

use RZP\Models\Base;
use Ramsey\Uuid\Uuid;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Transfer;
use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;

class Core extends Base\Core
{

    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createBulkTransactionMessageForOrderAndPaymentTransfer($transfer, $transferPayment, $merchantAccountBalances, $fee, $tax): array
    {

        $transferDebitJournal = $this->createTransactionMessageForDebitJournal($transfer, $merchantAccountBalances, $fee, $tax);

        $transferCreditJournal = $this->createTransactionMessageForCreditJournal($transfer, $transferPayment);

        $bulkJournals = [$transferDebitJournal, $transferCreditJournal];

        return [
            LedgerConstants::TRANSACTOR_EVENT             => LedgerConstants::TRANSFER,
            LedgerConstants::TRANSACTOR_ID                => $transfer->getPublicId(),
            LedgerConstants::TRANSACTION_DATE             => $transfer->getUpdatedAt(),
            LedgerConstants::CURRENCY                     => LedgerConstants::INR_CURRENCY,
            LedgerConstants::JOURNALS                     => $bulkJournals,
            LedgerConstants::IDEMPOTENCY_KEY              => Uuid::uuid1(),
            LedgerConstants::LEDGER_INTEGRATION_MODE      => LedgerConstants::REVERSE_SHADOW,
            LedgerConstants::TENANT                       => LedgerConstants::TENANT_PG
        ];

    }

    public function createTransactionMessageForDebitJournal($transfer, $merchantAccountBalances, $fee, $tax): array
    {
        $moneyParams = $this->generateMoneyParamsForTransferDebit($transfer, $merchantAccountBalances, $fee, $tax);

        $additionalParams = $this->fetchRulesForTransferDebit($transfer, $merchantAccountBalances, $fee, $tax);

        $transactionMessage = $this->generateBaseForJournalEntry($transfer);

        $transactionMessage[LedgerConstants::MONEY_PARAMS]           = $moneyParams;
        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS]      = array_merge(
            $additionalParams, [LedgerConstants::ENTRY_TYPE => LedgerConstants::ENTRY_TYPE_DEBIT]
        );

        return $transactionMessage;
    }

    public function generateMoneyParamsForTransferDebit(Transfer\Entity $transfer, $merchantAccountBalances, $fee, $tax): array
    {
        $moneyParams = [];

        $feeCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_FEE_CREDITS];
        $amountCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_AMOUNT_CREDITS];

        $fee = $fee - $tax;

        $amount = $transfer->getAmount();

        $moneyParams[LedgerConstants::AMOUNT]                         = strval($amount);
        $moneyParams[LedgerConstants::BASE_AMOUNT]                    = strval($amount);

        if ($amountCredits > 0)
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::RAZORPAY_REWARDS]           = strval($amount);
            $moneyParams[LedgerConstants::AMOUNT_CREDITS]             = strval($amount);
        }
        else if($this->isFeeCredits($feeCredits, $fee + $tax) === true)
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::TAX]                        = strval($tax);
            $moneyParams[LedgerConstants::TRANSFER_COMMISSION]        = strval($fee);
            $moneyParams[LedgerConstants::FEE_CREDITS]                = strval($tax + $fee);
        }
        // if merchant is on postpaid model
        else if($this->isTransferPostpaid($transfer) === true)
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::TAX]                        = strval($tax);
            $moneyParams[LedgerConstants::TRANSFER_COMMISSION]        = strval($fee);
            $moneyParams[LedgerConstants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
        }
        // Normal transfer debit scenario (commissions considered)
        else
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $fee + $tax);
            $moneyParams[LedgerConstants::TAX]                        = strval($tax);
            $moneyParams[LedgerConstants::TRANSFER_COMMISSION]        = strval($fee);
        }

        $maxNegativeLimit = $this->getMaxNegativeLimitForTransfer($transfer);

        if ($maxNegativeLimit !== 0)
        {
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_LIMIT] = strval($maxNegativeLimit);
        }

        return $moneyParams;
    }

    public function fetchRulesForTransferDebit(Transfer\Entity $transfer, $merchantAccountBalances, $fee, $tax) : array
    {
        $feeCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_FEE_CREDITS];
        $amountCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_AMOUNT_CREDITS];

        $rule = [];

        if ($amountCredits > 0)
        {
            $rule[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::AMOUNT_CREDITS_REDEMPTION;
        }
        else if($this->isFeeCredits($feeCredits, $fee) === true)
        {
            $rule[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::FEE_CREDITS;
        }
        else if($this->isTransferPostpaid($transfer))
        {
            $rule[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::POSTPAID;
        }

        return $rule;
    }

    public function createTransactionMessageForCreditJournal(Transfer\Entity $transfer, Payment\Entity $transferPayment): array
    {
        $moneyParams = $this->generateMoneyParamsForTransferCredit($transfer);

        $transactionMessage = $this->generateBaseForJournalEntry($transferPayment);

        $transactionMessage[LedgerConstants::MONEY_PARAMS]           = $moneyParams;
        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS]      = [ LedgerConstants::ENTRY_TYPE => LedgerConstants::ENTRY_TYPE_CREDIT ];

        return  $transactionMessage;
    }

    public function generateMoneyParamsForTransferCredit(Transfer\Entity $transfer): array
    {
        $amount = $transfer->getAmount();

        return [
            LedgerConstants::AMOUNT                     => strval($amount),
            LedgerConstants::BASE_AMOUNT                => strval($amount),
            LedgerConstants::MERCHANT_PAYABLE_AMOUNT    => strval($amount),
            LedgerConstants::MERCHANT_BALANCE_AMOUNT    => strval($amount),
        ];
    }

    public function saveOrderAndPaymentTransferReverseShadowLedgerEntriesToOutbox($transfer, $transferPayment)
    {
        $ledgerService = $this->app['ledger'];

        $merchantAccountBalances = $this->getMerchantAccountBalances($ledgerService, $transfer->getMerchantId());

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($transfer);

        $transactionMessage = $this->createBulkTransactionMessageForOrderAndPaymentTransfer($transfer, $transferPayment, $merchantAccountBalances, $fee, $tax);

        $transactorId = $transactionMessage[LedgerConstants::TRANSACTOR_ID];
        $transactorEvent = $transactionMessage[LedgerConstants::TRANSACTOR_EVENT];

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $transactionMessage);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);

        return [$fee, $tax];
    }

    protected function isTransferPostpaid(Transfer\Entity $transfer): bool
    {
        return ($transfer->merchant->getFeeModel() === Merchant\FeeModel::POSTPAID);
    }

    public function isNegativeBalanceEnabledForTxnTypeAndMerchant(string $txnType, string $balanceType = Balance\Type::PRIMARY) : bool
    {
        if ((array_key_exists($balanceType, Balance\Core::NEGATIVE_FLOWS) === false) or
            (in_array($txnType, Balance\Core::NEGATIVE_FLOWS[$balanceType]) === false))
        {
            return false;
        }

        return true;
    }

    private function getMaxNegativeLimitForTransfer(Transfer\Entity $transfer): int
    {
        $balanceType = Balance\Type::PRIMARY;
        $txnType = Transaction\Type::TRANSFER;

        $isNegativeBalanceEnabled = $this->isNegativeBalanceEnabledForTxnTypeAndMerchant($txnType, $balanceType);

        if ($isNegativeBalanceEnabled === false)
        {
            return 0;
        }

        $balance = $transfer->merchant->getBalanceByTypeOrFail($balanceType);

        $negativeAllowedFlows = (new BalanceConfig\Core())->getNegativeFlowsForBalance($balance->getId());

        if (in_array($txnType, $negativeAllowedFlows) === false)
        {
            return 0;
        }

        $maxNegative = (new BalanceConfig\Core())->getMaxNegativeAmountManualForBalanceId($balance->getId());

        return $maxNegative;

    }

}
