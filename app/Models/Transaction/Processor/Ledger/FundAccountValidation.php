<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class FundAccountValidation extends Base
{
    // Events
    const FAV_INITIATED = "fav_initiated";
    const FAV_PROCESSED = "fav_processed";
    const FAV_FAILED    = "fav_failed";
    const FAV_REVERSED  = "fav_reversed";

    public function pushTransactionToLedger(Entity $entity, string $mode, string $transactorType, int $transactorDate)
    {
        $startTime = millitime();

        try {

            /**
             * Check whether the event is default or not. Default event is set when there
             * is no event registered at ledger for that fav status.
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

            switch ($transactorType) {
                case self::FAV_INITIATED:
                    $transactorDate = $entity->getCreatedAt();
                    break;

                case self::FAV_FAILED:
                case self::FAV_REVERSED:
                case self::FAV_PROCESSED:
                    break;

                default:
                    throw new LogicException(self::TRANSACTOR_TYPE . ' not implemented at ledger : ' . $transactorType);
            }

            $payload = [
                self::TRANSACTOR          => self::X,
                self::MODE                => $mode,
                self::MERCHANT_ID         => $entity->getMerchantId(),
                self::CURRENCY            => $entity->getCurrency(),
                self::AMOUNT              => (string) $entity->getAmount(),
                self::BASE_AMOUNT         => (string) $entity->getBaseAmount(),
                self::COMMISSION          => (string) $entity->getFee(),
                self::TAX                 => (string) $entity->getTax(),
                self::NOTES               => json_encode($notes),
                self::FTS_FUND_ACCOUNT_ID => self::DEFAULT_FTS_FUND_ACCOUNT_ID,
                self::FTS_ACCOUNT_TYPE    => self::DEFAULT_FTS_FUND_ACCOUNT_TYPE,
                self::TRANSACTOR_ID       => $entity->getPublicId(),
                self::TRANSACTOR_TYPE     => $transactorType,
                self::TRANSACTION_DATE    => $transactorDate,
            ];

            $this->pushToLedgerSns($payload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_FAV_PAYLOAD_ERROR,
                [
                    self::TRANSACTOR_ID   => $entity->getPublicId(),
                    self::TRANSACTOR_TYPE => $transactorType,
                ]);
        }
        finally
        {
            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_FAV_STREAMING_TIME_TAKEN,
                [
                    self::TIME_TAKEN => millitime() - $startTime,
                ]);
        }
    }
}
