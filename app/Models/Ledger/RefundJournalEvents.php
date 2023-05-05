<?php


namespace RZP\Models\Ledger;

use App;
use Carbon\Carbon;
use RZP\Models\Dispute\Entity;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;
use RZP\Models\Reversal\Entity as Reversal;
use RZP\Models\Payment\Refund\Speed as Speed;
use RZP\Models\Payment\Refund\Entity as RefundEntity;


class RefundJournalEvents
{
    //Based on the type of refund (direct settlement refund, auto refund, normal refund),
    //create ledger configs for ledger entries.
    public static function createLedgerEntriesForRefunds(string $mode, RefundEntity $refund, Transaction\Entity $txn)
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        try {
            // If the type of refund is direct settlement, add ledger entries as per the DS refund rules
            if (($refund->isDirectSettlementWithoutRefund() === true) or
                ($refund->isDirectSettlementRefund() === true))
            {
                list($rule, $moneyParams) = self::fetchMoneyParamsAndLedgerRulesForRefundsDirectSettlement($refund, $txn);

                if ((isset($rule) === false) and
                    (isset($moneyParams) === false))
                {
                    $trace->info(
                        TraceCode::LEDGER_DS_REFUND_CASE_NOT_FOUND,
                        [
                            "transaction" => $txn,
                            "refund"      => $refund
                        ]
                    );

                    return;
                }

                $transactionMessage = self::createTransactionMessageForRefund($refund, $txn, $moneyParams);

                $transactionMessage[Constants::ADDITIONAL_PARAMS] = $rule;

                LedgerEntryJob::dispatchNow($mode, $transactionMessage);
            } //Normal autorefund scenarios
            else if ($refund->payment->hasBeenCaptured() === false)
            {
                $amount = abs($txn->getAmount());

                $moneyParams = [
                    Constants::AMOUNT       => strval($amount),
                    Constants::BASE_AMOUNT  => strval($amount)
                ];

                $transactionMessage = self::createTransactionMessageForRefund($refund, $txn, $moneyParams);

                $transactionMessage[Constants::ADDITIONAL_PARAMS] = [
                    Constants::REFUND_ACCOUNTING => Constants::AUTOREFUND
                ];

                LedgerEntryJob::dispatchNow($mode, $transactionMessage);
            } // Normal refund scenario
            else
            {
                list($rule, $moneyParams) = self::fetchLedgerRulesAndMoneyParamsForRefunds($refund, $txn);

                $transactionMessage = self::createTransactionMessageForRefund($refund, $txn, $moneyParams);

                $transactionMessage[Constants::ADDITIONAL_PARAMS] = $rule;

                LedgerEntryJob::dispatchNow($mode, $transactionMessage);
            }
        }
        catch (\Exception $ex)
        {
            $trace->traceException(
                $ex,
                500,
                TraceCode::LEDGER_ENTRY_FAILED,
                [
                    "transaction" => $txn,
                    "refund"      => $refund
                ]
            );
        }
    }

    //Creates a rule object for ledger entry based on refund DS usecases.
    //If DS refund happens via with refund terminal -
    //1. We only need to charge commission and tax, hence checking only for instant refund/optimum refund cases
    //2. Check if refund credits is used.
    //3. Check if the type of refund is autorefund
    //
    //If DS refund happens via without refund terminal -
    //1. Balance is deducted from merchant balance / credits as gateway doesn't take care of refund.
    //2. Check for the speed of refund and credits usage and make appropriate ledger entries.
    //3. Check if autorefund occurred on DS settlement
    public static function fetchMoneyParamsAndLedgerRulesForRefundsDirectSettlement(RefundEntity $refund, Transaction\Entity $transaction)
    {
        if (($refund->isDirectSettlementRefund() === true) and
            ($refund->isRefundSpeedInstant() === true) and
            ($transaction->isRefundCredits() === true))
        {
            return self::fetchDSWithRefundTerminalInstantSpeedRefundCredits($refund, $transaction);
        }

        if (($refund->isDirectSettlementRefund() === true) and
            ($refund->isRefundSpeedInstant() === true) and
            ($transaction->isRefundCredits() === false))
        {
            return self::fetchDSWithRefundTerminalInstantSpeedMerchantBalance($refund, $transaction);
        }

        if (($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === true) and
            ($transaction->isRefundCredits() === true))
        {
            return self::fetchDSWithoutRefundTerminalInstantSpeedRefundCredits($refund, $transaction);
        }

        if (($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === true) and
            ($transaction->isRefundCredits() === false))
        {
            return self::fetchDSWithoutRefundTerminalInstantSpeedMerchantBalance($refund, $transaction);
        }

        if (($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === false) and
            ($transaction->isRefundCredits() === true))
        {
            return self::fetchDSWithoutRefundTerminalNormalSpeedRefundCredits($refund, $transaction);
        }

        if (($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === false) and
            ($transaction->isRefundCredits() === false))
        {
            return self::fetchDSWithoutRefundTerminalNormalSpeedMerchantBalance($refund, $transaction);
        }

        if (($refund->payment->hasBeenCaptured() === false) and
            ($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === true) and
            ($transaction->isRefundCredits() === true))
        {
            return self::fetchAutoDSWithoutRefundTerminalInstantSpeedRefundCredits($refund, $transaction);
        }

        if (($refund->payment->hasBeenCaptured() === false) and
            ($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === true) and
            ($transaction->isRefundCredits() === false))
        {
            return self::fetchAutoDSWithoutRefundTerminalInstantSpeedMerchantBalance($refund, $transaction);
        }

        if (($refund->payment->hasBeenCaptured() === false) and
            ($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === false) and
            ($transaction->isRefundCredits() === true))
        {
            return self::fetchAutoDSWithoutRefundTerminalNormalSpeedRefundCredits($refund, $transaction);
        }

        if (($refund->payment->hasBeenCaptured() === false) and
            ($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === false) and
            ($transaction->isRefundCredits() === false))
        {
            return self::fetchAutoDSWithoutRefundTerminalNormalSpeedMerchantBalance($refund, $transaction);
        }
        return [null, null];
    }

    public static function fetchDSWithRefundTerminalInstantSpeedRefundCredits(RefundEntity $refund, Transaction\Entity $transaction)
    {
        $amount = abs($transaction->getAmount());

        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_INSTANT_REFUND_CREDITS;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITH_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]        = strval($amount);
        $moneyParams[Constants::REFUND_CREDITS]     = strval( $fee + $tax + $amount);
        $moneyParams[Constants::COMMISSION]         = strval($fee);
        $moneyParams[Constants::TAX]                = strval($tax);
        $moneyParams[Constants::REFUND_AMOUNT]      = strval($amount);

        return [$rule, $moneyParams];

    }

    public static function fetchDSWithRefundTerminalInstantSpeedMerchantBalance(RefundEntity $refund, Transaction\Entity $transaction)
    {
        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_INSTANT_REFUND;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITH_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval( $fee + $tax + $amount);
        $moneyParams[Constants::COMMISSION]                 = strval($fee);
        $moneyParams[Constants::TAX]                        = strval($tax);
        $moneyParams[Constants::REFUND_AMOUNT]              = strval($amount);

        return [$rule, $moneyParams];

    }
    public static function fetchDSWithoutRefundTerminalInstantSpeedRefundCredits(RefundEntity $refund, Transaction\Entity $transaction)
    {
        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_INSTANT_REFUND_CREDITS;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]        = strval($amount);
        $moneyParams[Constants::REFUND_CREDITS]     = strval($amount + $fee + $tax);
        $moneyParams[Constants::REFUND_AMOUNT]      = strval($amount);
        $moneyParams[Constants::COMMISSION]         = strval($fee);
        $moneyParams[Constants::TAX]                = strval($tax);

        return [$rule, $moneyParams];

    }
    public static function fetchDSWithoutRefundTerminalInstantSpeedMerchantBalance(RefundEntity $refund, Transaction\Entity $transaction)
    {
        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_INSTANT_REFUND;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $fee + $tax);
        $moneyParams[Constants::REFUND_AMOUNT]              = strval($amount);
        $moneyParams[Constants::COMMISSION]                 = strval($fee);
        $moneyParams[Constants::TAX]                        = strval($tax);

        return [$rule, $moneyParams];

    }
    public static function fetchDSWithoutRefundTerminalNormalSpeedRefundCredits (RefundEntity $refund, Transaction\Entity $transaction)
    {
        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_NORMAL_REFUND_CREDITS;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::REFUND_CREDITS] = strval($amount);
        $moneyParams[Constants::REFUND_AMOUNT]  = strval($amount);

        return [$rule, $moneyParams];
    }
    public static function fetchDSWithoutRefundTerminalNormalSpeedMerchantBalance(RefundEntity $refund, Transaction\Entity $transaction)
    {
        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_NORMAL_REFUND;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::REFUND_AMOUNT]              = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);

        return [$rule, $moneyParams];

    }
    public static function fetchAutoDSWithoutRefundTerminalInstantSpeedRefundCredits(RefundEntity $refund, Transaction\Entity $transaction)
    {
        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::AUTO_REFUND_DIRECT_SETTLEMENT_CREDITS_INSTANT;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::REFUND_CREDITS] = strval($amount + $fee + $tax);
        $moneyParams[Constants::REFUND_AMOUNT] = strval($amount);
        $moneyParams[Constants::COMMISSION] = strval($fee);
        $moneyParams[Constants::TAX] = strval($tax);

        return [$rule, $moneyParams];

    }
    public static function fetchAutoDSWithoutRefundTerminalInstantSpeedMerchantBalance(RefundEntity $refund, Transaction\Entity $transaction)
    {
        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::AUTO_REFUND_DIRECT_SETTLEMENT_INSTANT;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT] = strval($amount + $fee + $tax);
        $moneyParams[Constants::REFUND_AMOUNT] = strval($amount);
        $moneyParams[Constants::COMMISSION] = strval($fee);
        $moneyParams[Constants::TAX] = strval($tax);

        return [$rule, $moneyParams];

    }
    public static function fetchAutoDSWithoutRefundTerminalNormalSpeedRefundCredits(RefundEntity $refund, Transaction\Entity $transaction)
    {
        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::AUTO_REFUND_DIRECT_SETTLEMENT_CREDITS_NORMAL;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::REFUND_CREDITS] = strval($amount);
        $moneyParams[Constants::REFUND_AMOUNT]  = strval($amount);

        return [$rule, $moneyParams];

    }
    public static function fetchAutoDSWithoutRefundTerminalNormalSpeedMerchantBalance(RefundEntity $refund, Transaction\Entity $transaction)
    {
        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::AUTO_REFUND_DIRECT_SETTLEMENT_NORMAL;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::REFUND_AMOUNT]              = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);

        return [$rule, $moneyParams];

    }

    //reversal entity has association with refund entity
    public static function fetchLedgerRulesAndMoneyParamsForReversal(Transaction\Entity $transaction, RefundEntity $refund, bool $feeOnlyReversal)
    {
        $rule = null;
        $moneyParams = [];

        $amount = abs($transaction->getAmount());
        $strAmount = strval($amount);
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);

        //This is a case where optimum refund was initially triggered and then only fee was reversed
        //converting instant refund to normal refund
        if($feeOnlyReversal === true)
        {
            $moneyParams[Constants::COMMISSION]                 = strval($fee);
            $moneyParams[Constants::TAX]                        = strval($tax);

            // No amount is reversed from customer is feeOnlyReversal hence the reversedAmount is 0
            $moneyParams[Constants::REVERSED_AMOUNT]            = "0";
            if ($transaction->isRefundCredits() === true)
            {
                $rule[Constants::REVERSE_REFUND_ACCOUNTING]     = Constants::INSTANT_REFUND_REVERSED_CREDITS;
                $moneyParams[Constants::REFUND_CREDITS]         = strval($fee + $tax);
            }
            else
            {
                $rule[Constants::REVERSE_REFUND_ACCOUNTING]         = Constants::INSTANT_REFUND_REVERSED;
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($fee + $tax);
            }
        }
        else
        {
            if($refund->getSpeedDecisioned() === speed::NORMAL)
            {
                if ($transaction->isRefundCredits() === true)
                {
                    $rule[Constants::REVERSE_REFUND_ACCOUNTING] = Constants::REFUND_REVERSED_CREDITS;

                    $moneyParams[Constants::REVERSED_AMOUNT]     = $strAmount;
                    $moneyParams[Constants::REFUND_CREDITS]      = $strAmount;
                }
                else {
                    $moneyParams[Constants::REVERSED_AMOUNT]            = $strAmount;
                    $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]     = $strAmount;
                }
            }
            else if ($refund->isRefundSpeedInstant() === true)
            {
                $moneyParams[Constants::REVERSED_AMOUNT]    = $strAmount;
                $moneyParams[Constants::COMMISSION]         = strval($fee);
                $moneyParams[Constants::TAX]                = strval($tax);

                if ($transaction->isRefundCredits() === true)
                {
                    $rule[Constants::REVERSE_REFUND_ACCOUNTING]     = Constants::INSTANT_REFUND_REVERSED_CREDITS;
                    $moneyParams[Constants::REFUND_CREDITS]         = strval($amount + $fee + $tax);
                }
                else
                {
                    $rule[Constants::REVERSE_REFUND_ACCOUNTING]         = Constants::INSTANT_REFUND_REVERSED;
                    $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]  = strval($amount + $fee + $tax);
                }
            }
        }
        return [$rule, $moneyParams];
    }

    //Creates a rule object for ledger entry based on refund usecases.
    public static function fetchLedgerRulesAndMoneyParamsForRefunds(RefundEntity $refund, Transaction\Entity $transaction)
    {
        $app = App::getFacadeRoot();
        $trace = $app['trace'];

        $rule = null;
        $moneyParams = [];

        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);

        // case when a normal speed refund occurs
        if(($refund->getSpeedDecisioned() === speed::NORMAL) and
            ($transaction->isRefundCredits()) === true)
        {
            $rule[Constants::REFUND_ACCOUNTING] = Constants::REFUND_PROCESSED_WITH_CREDITS;

            $moneyParams[Constants::REFUND_CREDITS] = strval($amount);
            $moneyParams[Constants::REFUND_AMOUNT]  = strval($amount);
        }
        // case when a instant speed refund occurs
        else if($refund->isRefundSpeedInstant() === true)
        {
            $moneyParams[Constants::REFUND_AMOUNT]      = strval($amount);
            $moneyParams[Constants::COMMISSION]         = strval($fee);
            $moneyParams[Constants::TAX]                = strval($tax);

            if($transaction->isRefundCredits() === true and $transaction->isPostpaid() === false)
            {
                $rule[Constants::REFUND_ACCOUNTING] = Constants::REFUND_PROCESSED_WITH_CREDITS_INSTANT;

                $moneyParams[Constants::REFUND_CREDITS]     = strval($amount + $fee + $tax);
            }
            else if ($transaction->isRefundCredits() === true and $transaction->isPostpaid() === true)
            {
                $rule[Constants::REFUND_ACCOUNTING] = Constants::INSTANT_REFUND_PROCESSED_WITH_CREDITS_POSTPAID_MODEL;
                $moneyParams[Constants::REFUND_CREDITS]     = strval($amount);
                $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
            }
            else if ($transaction->isRefundCredits() === false and $transaction->isPostpaid() === false)
            {
                $rule[Constants::REFUND_ACCOUNTING] = Constants::REFUND_INSTANT_PROCESSED;

                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $fee + $tax);
            }
            else if ($transaction->isRefundCredits() === false and $transaction->isPostpaid() === true)
            {
                $rule[Constants::REFUND_ACCOUNTING] = Constants::INSTANT_REFUND_PROCESSED_POSTPAID_MODEL;
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]     = strval($amount);
                $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
            }
            else
            {
                $trace->debug(TraceCode::INVALID_REFUND_JOURNAL_USECASE, [
                    "refund"        => $refund,
                    "transaction"   => $transaction
                ]);
            }
        }
        else
        {
            // When normal refund occurs (speed = normal, refund_source = merchant_balance)
            $moneyParams[Constants::REFUND_AMOUNT]              = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        return [$rule, $moneyParams];
    }

    public static function createTransactionMessageForRefundReversal(Reversal $reversal, Transaction\Entity $transaction, array $moneyParams): array
    {
        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($transaction);

        $reversalData = array(
            Constants::TRANSACTOR_ID                => $reversal->getPublicId(),
            Constants::TRANSACTOR_EVENT             => Constants::REFUND_REVERSAL,
            Constants::MONEY_PARAMS                 => $moneyParams,
            Constants::IDENTIFIERS                  => [
                Constants::GATEWAY      => $reversal->entity->getGateway(),
            ],
        );
        return array_merge($transactionMessage, $reversalData);
    }

    public static function createTransactionMessageForRefund(RefundEntity $refund, Transaction\Entity $transaction, array $moneyParams): array
    {
        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($transaction);

        $transactorEvent = Constants::REFUND_PROCESSED;

        if (empty($refund->getNotes() === false))
        {
            $notes = $refund->getNotes();

            if ((isset($notes['reason']) === true) and
                (str_starts_with($notes['reason'], Entity::getSign()) === true))
            {
                $transactorEvent = Constants::DISPUTE_REFUND_PROCESSED;
            }
        }
        $refundData = array(
            Constants::TRANSACTOR_ID                 => $refund->getPublicId(),
            Constants::TRANSACTOR_EVENT              => $transactorEvent,
            Constants::MONEY_PARAMS                  => $moneyParams,
            Constants::IDENTIFIERS                   => [
                Constants::GATEWAY           => $refund->getGateway(),
            ]
        );

        return array_merge($transactionMessage, $refundData);
    }
}
