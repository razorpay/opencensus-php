<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use Ramsey\Uuid\Uuid;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Currency\Currency;
use RZP\Models\Adjustment\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Adjustment extends Base
{
    // Events
    const POSITIVE_ADJUSTMENT_PROCESSED = 'positive_adjustment_processed';
    const NEGATIVE_ADJUSTMENT_PROCESSED = 'negative_adjustment_processed';

    public function pushTransactionToLedger(Entity $adjustment,
                                            string $transactorEvent)
    {
        $startTime = millitime();

        try
        {
            /**
             * Check whether the event is default or not. Default event is set when there
             * is no event registered at ledger for that fund loading status.
             * In this case, it is not required to push transaction through sns.
             */
            if ($this->isDefaultEvent($transactorEvent))
            {
                $this->trace->info(
                    TraceCode::LEDGER_JOURNAL_TRANSACTOR_EVENT_NOT_REGISTERED,
                    [
                        self::TRANSACTOR_EVENT => $transactorEvent,
                        self::ENTITY           => $adjustment,
                    ]);

                return;
            }

            $notes = [
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($adjustment->getBalanceId()),
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($adjustment->getTransactionId())
            ];

            $identifiers = [
                self::BANKING_ACCOUNT_ID => $adjustment->balance->bankingAccount->getPublicId(),
            ];

            $additional_params = [];

            $payload = [
                self::TENANT             => self::X,
                self::MODE               => $this->mode,
                self::IDEMPOTENCY_KEY    => Uuid::uuid1()->toString(),
                self::MERCHANT_ID        => $adjustment->getMerchantId(),
                self::CURRENCY           => $adjustment->getCurrency(),
                self::AMOUNT             => (string) abs($adjustment->getAmount()),
                self::BASE_AMOUNT        => (string) abs($adjustment->getAmount()),
                self::COMMISSION         => (string) $adjustment->transaction->getFee(),
                self::TAX                => (string) $adjustment->transaction->getTax(),
                self::TRANSACTOR_ID      => $adjustment->getPublicId(),
                self::NOTES              => json_encode($notes),
                self::TRANSACTOR_EVENT   => $transactorEvent,
                self::TRANSACTION_DATE   => $adjustment->getCreatedAt(),
                self::API_TRANSACTION_ID => $adjustment->getTransactionId(),
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
                TraceCode::LEDGER_JOURNAL_ADJUSTMENT_PAYLOAD_ERROR,
                [
                    self::TRANSACTOR_ID    => $adjustment->getPublicId(),
                    self::TRANSACTOR_EVENT => $transactorEvent,
                ]);
        }
        finally
        {
            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_ADJUSTMENT_STREAMING_TIME_TAKEN,
                [
                    self::TIME_TAKEN => millitime() - $startTime,
                ]);
        }
    }

    /**
     * Create Journal function for adjustments
     *
     * @param array $payload
     *
     * @throws BadRequestException
     * @throws \Throwable
     */
    public function createJournalEntry(array $payload, int $maxRetryCount = self::DEFAULT_MAX_RETRY_COUNT, int $retryCount = 0, PublicCollection $feeSplit = null, string $iKey = null)
    {
        try
        {
            $response = parent::createJournalEntry($payload, $maxRetryCount, $retryCount, $feeSplit, $iKey);
        }
        catch (BadRequestException $e)
        {
            // If it's an insufficient balance case, throw a new exception with a new error code
            if ($e->getCode() === ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE)
            {
                throw new BadRequestException(
                    Errorcode::BAD_REQUEST_INSUFFICIENT_BALANCE_FOR_ADJUSTMENT,
                    null,
                    $e->getData()
                );
            }
            else
            {
                // We don't want to miss any other form of BadRequestException, just that their error code
                // will be unchanged.
                throw $e;
            }
        }

        return $response;
    }

    /**
     * Create payload for Journal function for adjustment
     * @param Entity $adjustment
     * @param string $transactorEvent
     * @return array
     * @throws \Exception
     */
    public function createPayloadForJournalEntry(Entity $adjustment,
                                                 string $transactorEvent)
    {

        $notes = [
            self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($adjustment->getBalanceId()),
            self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($adjustment->getTransactionId())
        ];

        $identifiers = [
            self::BANKING_ACCOUNT_ID => $adjustment->balance->bankingAccount->getPublicId(),
        ];

        return [
            self::TENANT             => self::X,
            self::MODE               => $this->mode,
            self::IDEMPOTENCY_KEY    => Uuid::uuid1()->toString(),
            self::MERCHANT_ID        => $adjustment->getMerchantId(),
            self::CURRENCY           => $adjustment->getCurrency(),
            self::AMOUNT             => (string) abs($adjustment->getAmount()),
            self::BASE_AMOUNT        => (string) abs($adjustment->getAmount()),
//            self::COMMISSION         => (string) $adjustment->transaction->getFee(), // TODO: check if we can send empty as no txn present
//            self::TAX                => (string) $adjustment->transaction->getTax(),
            self::TRANSACTOR_ID      => $adjustment->getPublicId(),
            self::NOTES              => $notes,
            self::TRANSACTOR_EVENT   => $transactorEvent,
            self::TRANSACTION_DATE   => $adjustment->getCreatedAt(),
            self::IDENTIFIERS        => $identifiers,
        ];
    }

    /**
     * Create payload for Journal function for adjustment
     *
     * @param string $entityId
     *
     */
    public function createPayloadForTransactionEntry(string $entityId, string $entityName, array $ledgerResponse)
    {
        // Todo: check if can be removed so called goes directly to base method
        return parent::createPayloadForTransactionEntry($entityId, $entityName, $ledgerResponse);
    }
}
