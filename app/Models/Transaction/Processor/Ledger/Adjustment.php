<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Adjustment\Entity;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Adjustment extends Base
{
    // Events
    const POSITIVE_ADJUSTMENT_PROCESSED = 'positive_adjustment_processed';
    const NEGATIVE_ADJUSTMENT_PROCESSED = 'negative_adjustment_processed';

    public function pushTransactionToLedger(Entity $adjustment,
                                            string $transactorType)
    {
        $startTime = millitime();

        try
        {
            /**
             * Check whether the event is default or not. Default event is set when there
             * is no event registered at ledger for that fund loading status.
             * In this case, it is not required to push transaction through sns.
             */
            if ($this->isDefaultEvent($transactorType))
            {
                $this->trace->info(
                    TraceCode::LEDGER_JOURNAL_TRANSACTOR_TYPE_NOT_REGISTERED,
                    [
                        self::TRANSACTOR_TYPE => $transactorType,
                        self::ENTITY          => $adjustment,
                    ]);

                return;
            }

            $notes = [
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($adjustment->getBalanceId()),
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($adjustment->getTransactionId())
            ];

            $payload = [
                self::TRANSACTOR       => self::X,
                self::MODE             => $this->mode,
                self::IDEMPOTENCY_KEY  => gen_uuid(self::UUID_FORMAT),
                self::MERCHANT_ID      => $adjustment->getMerchantId(),
                self::CURRENCY         => $adjustment->getCurrency(),
                self::AMOUNT           => (string) abs($adjustment->getAmount()),
                self::BASE_AMOUNT      => (string) abs($adjustment->getAmount()),
                self::COMMISSION       => (string) $adjustment->transaction->getFee(),
                self::TAX              => (string) $adjustment->transaction->getTax(),
                self::TRANSACTOR_ID    => $adjustment->getPublicId(),
                self::NOTES            => json_encode($notes),
                self::TRANSACTOR_TYPE  => $transactorType,
                self::TRANSACTION_DATE => $adjustment->getCreatedAt(),
            ];

            $this->pushToLedgerSns($payload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_FUND_LOADING_PAYLOAD_ERROR,
                [
                    self::TRANSACTOR_ID   => $adjustment->getPublicId(),
                    self::TRANSACTOR_TYPE => $transactorType,
                ]);
        }
        finally
        {
            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_FUND_LOADING_STREAMING_TIME_TAKEN,
                [
                    self::TIME_TAKEN => millitime() - $startTime,
                ]);
        }
    }
}
