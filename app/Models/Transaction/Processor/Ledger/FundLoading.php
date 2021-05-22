<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer\Entity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Models\Transaction\Entity as TransactionEntity;

class FundLoading extends Base
{
    // Events
    const FUND_LOADING_PROCESSED = "fund_loading_processed";

    public function pushTransactionToLedger(Entity $entity,
                                            string $mode,
                                            string $transactorType,
                                            string $terminalId,
                                            $terminalAccountType)
    {

        $startTime = millitime();

        try {

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
                        self::ENTITY          => $entity,
                    ]);

                return;
            }

            $notes = [
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($entity->getBalanceId()),
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($entity->getTransactionId())
            ];

            $terminalAccountType = $terminalAccountType ?? self::DEFAULT_TERMINAL_ACCOUNT_TYPE;

            $payload = [
                self::TRANSACTOR            => self::X,
                self::MODE                  => $mode,
                self::MERCHANT_ID           => $entity->getMerchantId(),
                self::CURRENCY              => $entity->getTransactionCurrency(),
                self::AMOUNT                => (string) $entity->getAmount(),
                self::BASE_AMOUNT           => (string) $entity->getAmount(),
                self::COMMISSION            => (string) $entity->getTransactionFee(),
                self::TAX                   => (string) $entity->getTransactionTax(),
                self::NOTES                 => json_encode($notes),
                self::TERMINAL_ID           => $terminalId,
                self::TERMINAL_ACCOUNT_TYPE => $terminalAccountType,
                self::TRANSACTOR_ID         => $entity->getPublicId(),
                self::TRANSACTOR_TYPE       => $transactorType,
                self::TRANSACTION_DATE      => $entity->getCreatedAt(),
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
                    self::TRANSACTOR_ID    => $entity->getPublicId(),
                    self::TRANSACTOR_TYPE  => $transactorType,
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
