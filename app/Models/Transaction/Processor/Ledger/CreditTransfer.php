<?php


namespace RZP\Models\Transaction\Processor\Ledger;

use Ramsey\Uuid\Uuid;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\CreditTransfer\Entity;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class CreditTransfer extends Base
{
    // Events
    const VA_TO_VA_CREDIT_PROCESSED = "va_to_va_credit_processed";

    public function pushTransactionToLedger(Entity $creditTransfer, string $transactorEvent)
    {
        $startTime = millitime();

        try
        {
            /**
             * Check whether the event is default or not. Default event is set when there
             * is no event registered at ledger for that payout status.
             * In this case, it is not required to push transaction through sns.
             */
            if ($this->isDefaultEvent($transactorEvent))
            {
                $this->trace->info(
                    TraceCode::LEDGER_JOURNAL_TRANSACTOR_EVENT_NOT_REGISTERED,
                    [
                        self::TRANSACTOR_EVENT => $transactorEvent,
                        self::ENTITY           => $creditTransfer,
                    ]);

                return;
            }


            $notes = [
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($creditTransfer->getBalanceId()),
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($creditTransfer->getTransactionId())
            ];

            $identifiers = [
                self::BANKING_ACCOUNT_ID => $creditTransfer->balance->bankingAccount->getPublicId(),
            ];

            $additional_params = [];

            $payload = [
                self::TENANT             => self::X,
                self::MODE               => $this->mode,
                self::IDEMPOTENCY_KEY    => Uuid::uuid1()->toString(),
                self::MERCHANT_ID        => $creditTransfer->getMerchantId(),
                self::CURRENCY           => $creditTransfer->getCurrency(),
                self::AMOUNT             => (string) $creditTransfer->getAmount(),
                self::BASE_AMOUNT        => (string) $creditTransfer->getAmount(),
                self::COMMISSION         => (string) $creditTransfer->transaction->getFee(),
                self::TAX                => (string) $creditTransfer->transaction->getTax(),
                self::TRANSACTOR_ID      => $creditTransfer->getPublicId(),
                self::NOTES              => json_encode($notes),
                self::TRANSACTOR_EVENT   => $transactorEvent,
                self::TRANSACTION_DATE   => $creditTransfer->getProcessedAt(),
                self::API_TRANSACTION_ID => $creditTransfer->getTransactionId(),
                self::ADDITIONAL_PARAMS  => json_encode($additional_params),
                self::IDENTIFIERS        => json_encode($identifiers),
            ];

            $this->pushToLedgerSns($payload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_PAYOUT_PAYLOAD_ERROR,
                [
                    self::TRANSACTOR_ID    => $creditTransfer->getPublicId(),
                    self::TRANSACTOR_EVENT => $transactorEvent,
                ]);
        }
        finally
        {
            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_PAYOUT_STREAMING_TIME_TAKEN,
                [
                    self::TIME_TAKEN => millitime() - $startTime,
                ]);
        }
    }

    public function processCreditTransferAndCreateJournalEntry(Entity $creditTransfer, string $transactorEvent)
    {
        $payload = $this->createLedgerPayloadFromEntity($creditTransfer, $transactorEvent);

        $response = parent::createJournalEntry($payload);

        return $response;
    }

    public function createLedgerPayloadFromEntity(Entity $creditTransfer, string $transactorEvent)
    {
        $notes = [
            self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($creditTransfer->getBalanceId()),
            self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($creditTransfer->getTransactionId())
        ];

        $identifiers = [
            self::BANKING_ACCOUNT_ID => $creditTransfer->balance->bankingAccount->getPublicId(),
        ];

        $payload = [
            self::TENANT             => self::X,
            self::MODE               => $this->mode,
            self::IDEMPOTENCY_KEY    => Uuid::uuid1()->toString(),
            self::MERCHANT_ID        => $creditTransfer->getMerchantId(),
            self::CURRENCY           => $creditTransfer->getCurrency(),
            self::AMOUNT             => (string) $creditTransfer->getAmount(),
            self::BASE_AMOUNT        => (string) $creditTransfer->getAmount(),
            self::COMMISSION         => (string) 0,
            self::TAX                => (string) 0,
            self::TRANSACTOR_ID      => $creditTransfer->getPublicId(),
            self::NOTES              => $notes,
            self::TRANSACTOR_EVENT   => $transactorEvent,
            self::TRANSACTION_DATE   => $creditTransfer->getCreatedAt(),
            self::IDENTIFIERS        => $identifiers,
        ];

        return $payload;
    }
}
