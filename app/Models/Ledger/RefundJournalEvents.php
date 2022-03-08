<?php


namespace RZP\Models\Ledger;

use App;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Jobs\Ledger\CreateLedgerJournal;
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
                $transactionMessage = self::createTransactionMessageForRefund($refund, $txn);

                unset($transactionMessage[Constants::TRANSACTOR_EVENT]);

                $transactionMessage[Constants::TRANSACTOR_EVENT] = Constants::REFUND_PROCESSED_DIRECT_SETTLEMENT;

                $rule = (object) self::fetchLedgerRulesForRefundsDirectSettlement($refund, $txn);

                //add ledger entries only if direct_settlement_accounting rule is set.
                if (isset($rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING]) === true)
                {
                    $transactionMessage[Constants::ADDITIONAL_PARAMS] = $rule;

                    CreateLedgerJournal::dispatch($mode, $transactionMessage, $txn->merchant)->onConnection('sync');
                }
            } //Normal autorefund scenarios
            else if (($refund->payment->getStatus() === "authorized") and
                ($refund->payment->getRefundAt() < Carbon::now()->getTimestamp()))
            {
                $transactionMessage = self::createTransactionMessageForRefund($refund, $txn);

                $transactionMessage[Constants::ADDITIONAL_PARAMS] = [
                    Constants::REFUND_ACCOUNTING => Constants::AUTOREFUND
                ];

                CreateLedgerJournal::dispatch($mode, $transactionMessage, $txn->merchant)->onConnection('sync');
            } // Normal refund scenario
            else
            {
                $transactionMessage = self::createTransactionMessageForRefund($refund, $txn);

                $transactionMessage[Constants::ADDITIONAL_PARAMS] = (object) self::fetchLedgerRulesForRefunds($refund, $txn);

                CreateLedgerJournal::dispatch($mode, $transactionMessage, $txn->merchant)->onConnection('sync');
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
    public static function fetchLedgerRulesForRefundsDirectSettlement(RefundEntity $refund, Transaction\Entity $transaction): array
    {
        $rule = [
        ];

        // In this case, the gateway itself handles the refund hence, money is not deducted from merchant balance account
        if($refund->isDirectSettlementRefund() === true)
        {
            //Check if speed is instant
            if ($refund->isRefundSpeedInstant() === true)
            {
                //The below condition is verified as speedProcessed is set to normal in case optimum refund fails.
                //Fee and tax would've already been refunded.
                if ($refund->getSpeedProcessed() != speed::NORMAL)
                {
                    if($transaction->isRefundCredits() === true)
                    {
                        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_INSTANT_REFUND_CREDITS;
                    }
                    else
                    {
                        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_INSTANT_REFUND;
                    }
                }

                // Auto refund condition in direct settlement
                if (($refund->payment->hasBeenCaptured() === false) and
                    ($refund->payment->getRefundAt() < Carbon::now()->getTimestamp()))
                {
                    $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::AUTOREFUND_DS_WITH_REFUND;
                }
            }
        }
        //Gateway doesn't handle the refund and money needs to be deducted from merchant
        else if ($refund->isDirectSettlementWithoutRefund())
        {
            if(($refund->getSpeedDecisioned() === speed::NORMAL) or
                ($refund->getSpeedProcessed() === speed::NORMAL))
            {
                if($transaction->isRefundCredits() === true)
                {
                    $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_NORMAL_REFUND_CREDITS;
                }
                else
                {
                    $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_NORMAL_REFUND;
                }

                // Auto refund condition in direct settlement without refund
                if (($refund->payment->hasBeenCaptured() === false) and
                    ($refund->payment->getRefundAt() < Carbon::now()->getTimestamp()))
                {
                    if($transaction->isRefundCredits() === true)
                    {
                        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::AUTOREFUND_DS_WITHOUT_REFUND_WITH_CREDITS;
                    }
                    else
                    {
                        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::AUTOREFUND_DS_WITHOUT_REFUND;
                    }
                }
            }
            else
            {
                if($transaction->isRefundCredits() === true)
                {
                    $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_RZP_REFUND_INSTANT_CREDITS;
                }
                else
                {
                    $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_RZP_REFUND_INSTANT;
                }
            }
        }

        return $rule;
    }

    //reversal entity has association with refund entity
    public static function fetchLedgerRulesForReversal(Reversal $reversal, Transaction\Entity $transaction, RefundEntity $refund, bool $feeOnlyReversal): array
    {
        $rule = [
        ];

        //This is a case where optimum refund was initially triggered and then only fee was reversed
        //converting instant refund to normal refund
        if($feeOnlyReversal === true)
        {
            if ($transaction->isRefundCredits() === true)
            {
                $rule[Constants::REVERSE_REFUND_ACCOUNTING] = Constants::INSTANT_REFUND_REVERSED_CREDITS;
            }
            else
            {
                $rule[Constants::REVERSE_REFUND_ACCOUNTING] = Constants::INSTANT_REFUND_REVERSED;
            }
        }
        else
        {
            if($refund->getSpeedProcessed() === speed::NORMAL)
            {
                if ($transaction->isRefundCredits() === true)
                {
                    $rule[Constants::REVERSE_REFUND_ACCOUNTING] = Constants::REFUND_REVERSED_CREDITS;
                }
            }
            else if ($refund->isRefundSpeedInstant() === true)
            {
                if ($transaction->isRefundCredits() === true)
                {
                    $rule[Constants::REVERSE_REFUND_ACCOUNTING] = Constants::INSTANT_REFUND_REVERSED_CREDITS;
                } else
                {
                    $rule[Constants::REVERSE_REFUND_ACCOUNTING] = Constants::INSTANT_REFUND_REVERSED;
                }
            }
        }
        return $rule;
    }

    //Creates a rule object for ledger entry based on refund usecases.
    public static function fetchLedgerRulesForRefunds(RefundEntity $refund, Transaction\Entity $transaction): array
    {
        $rule = [
        ];

        if(($refund->getSpeedDecisioned() === speed::NORMAL) and
            ($transaction->isRefundCredits()) === true)
        {
            $rule[Constants::REFUND_ACCOUNTING] = Constants::REFUND_PROCESSED_WITH_CREDITS;
        }

        else if($refund->isRefundSpeedInstant() === true)
        {
            if($transaction->isRefundCredits() === true)
            {
                $rule[Constants::REFUND_ACCOUNTING] = Constants::REFUND_PROCESSED_WITH_CREDITS_INSTANT;
            }
            else
            {
                $rule[Constants::REFUND_ACCOUNTING] = Constants::REFUND_INSTANT_PROCESSED;
            }
        }
        return $rule;
    }

    public static function createTransactionMessageForRefundReversal(Reversal $reversal, Transaction\Entity $transaction): array
    {
        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($transaction);

        $reversalData = array(
            Constants::TRANSACTOR_ID                 => $reversal->getId(),
            Constants::TRANSACTOR_EVENT              => Constants::REFUND_REVERSAL,
            Constants::IDENTIFIERS                   => (object) [
                Constants::GATEWAY         => $reversal->entity->getGateway(),
            ],
        );
        return array_merge($transactionMessage, $reversalData);
    }

    public static function createTransactionMessageForRefund(RefundEntity $refund, Transaction\Entity $transaction): array
    {
        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($transaction);

        $refundData = array(
            Constants::TRANSACTOR_ID                => $refund->getId(),
            Constants::TRANSACTOR_EVENT              => Constants::REFUND_PROCESSED,
            Constants::IDENTIFIERS                   => (object) [
                Constants::GATEWAY           => $refund->getGateway(),
            ],
        );

        return array_merge($transactionMessage, $refundData);
    }
}
