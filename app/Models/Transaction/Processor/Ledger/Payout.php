<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Models\Payout\Entity;
use RZP\Exception\LogicException;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Payout extends Base
{
    // Events
    const PAYOUT_INITIATED = "payout_initiated";
    const PAYOUT_PROCESSED = "payout_processed";
    const PAYOUT_REVERSED  = "payout_reversed";

    public function pushTransactionToLedger(Entity $entity,
                                            string $transactorType,
                                            Reversal\Entity $reversal = null)
    {
        $startTime = millitime();

        try
        {
            /**
             * Check whether the event is default or not. Default event is set when there
             * is no event registered at ledger for that payout status.
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

            $transactorId = $entity->getPublicId();
            $transactionId = $entity->getTransactionId();
            $transactorDate = null;
            $optionalPayload = [];

            switch ($transactorType)
            {
                case self::PAYOUT_INITIATED:
                    $transactorDate = $entity->getCreatedAt();
                    break;

                case self::PAYOUT_PROCESSED:
                    $transactorDate = $entity->getProcessedAt();

                    $optionalPayload = [
                        self::FTS_FUND_ACCOUNT_ID => self::DEFAULT_FTS_FUND_ACCOUNT_ID,
                        self::FTS_ACCOUNT_TYPE    => self::DEFAULT_FTS_FUND_ACCOUNT_TYPE,
                    ];
                    break;

                case self::PAYOUT_REVERSED:
                    if ($reversal !== null) {
                        $transactorDate = $reversal->getCreatedAt();
                        $transactorId = $reversal->getPublicId();
                        $transactionId = $reversal->getTransactionId();
                    }

                    $optionalPayload = [
                        self::FTS_FUND_ACCOUNT_ID => self::DEFAULT_FTS_FUND_ACCOUNT_ID,
                        self::FTS_ACCOUNT_TYPE    => self::DEFAULT_FTS_FUND_ACCOUNT_TYPE,
                    ];
                    break;

                default:
                    throw new LogicException(self::TRANSACTOR_TYPE . ' not implemented at ledger : ' . $transactorType);
            }

            $notes = [
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($entity->getBalanceId()),
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($transactionId),
            ];

            $payload = [
                self::TRANSACTOR          => self::X,
                self::MODE                => $this->mode,
                self::IDEMPOTENCY_KEY     => gen_uuid(self::UUID_FORMAT),
                self::MERCHANT_ID         => $entity->getMerchantId(),
                self::CURRENCY            => $entity->getCurrency(),
                self::AMOUNT              => (string) $entity->getAmount(),
                self::BASE_AMOUNT         => (string) $entity->getBaseAmount(),
                self::COMMISSION          => (string) $entity->getFee(),
                self::TAX                 => (string) $entity->getTax(),
                self::NOTES               => json_encode($notes),
                self::TRANSACTOR_ID       => $transactorId,
                self::TRANSACTOR_TYPE     => $transactorType,
                self::TRANSACTION_DATE    => $transactorDate,
            ];

            $payload = array_merge($payload, $optionalPayload);

            $this->pushToLedgerSns($payload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_PAYOUT_PAYLOAD_ERROR,
                [
                    self::TRANSACTOR_ID   => $entity->getPublicId(),
                    self::TRANSACTOR_TYPE => $transactorType,
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
}
