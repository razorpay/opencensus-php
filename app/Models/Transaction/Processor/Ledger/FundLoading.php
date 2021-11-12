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

    public function pushTransactionToLedger(Entity $bankTransfer,
                                            string $transactorEvent,
                                            string $terminalId,
                                            $terminalAccountType)
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
                        self::ENTITY           => $bankTransfer,
                    ]);

                return;
            }

            $notes = [
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($bankTransfer->getBalanceId()),
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($bankTransfer->getTransactionId())
            ];

            $terminalAccountType = $terminalAccountType ?? self::DEFAULT_TERMINAL_ACCOUNT_TYPE;

            $additional_params = [];

            $identifiers = [
                self::TERMINAL_ID           => $terminalId,
                self::TERMINAL_ACCOUNT_TYPE => $terminalAccountType,
                self::BANKING_ACCOUNT_ID    => $bankTransfer->balance->bankingAccount->getPublicId(),
            ];

            $payload = [
                self::TENANT                => self::X,
                self::MODE                  => $this->mode,
                self::IDEMPOTENCY_KEY       => gen_uuid(self::UUID_FORMAT),
                self::MERCHANT_ID           => $bankTransfer->getMerchantId(),
                self::CURRENCY              => $bankTransfer->getTransactionCurrency(),
                self::AMOUNT                => (string) $bankTransfer->getAmount(),
                self::BASE_AMOUNT           => (string) $bankTransfer->getAmount(),
                self::COMMISSION            => (string) $bankTransfer->getTransactionFee(),
                self::TAX                   => (string) $bankTransfer->getTransactionTax(),
                self::NOTES                 => json_encode($notes),
                self::TRANSACTOR_ID         => $bankTransfer->getPublicId(),
                self::TRANSACTOR_EVENT      => $transactorEvent,
                self::TRANSACTION_DATE      => $bankTransfer->getCreatedAt(),
                self::API_TRANSACTION_ID    => $bankTransfer->getTransactionId(),
                self::ADDITIONAL_PARAMS     => json_encode($additional_params),
                self::IDENTIFIERS           => json_encode($identifiers),
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
                    self::TRANSACTOR_ID    => $bankTransfer->getPublicId(),
                    self::TRANSACTOR_EVENT => $transactorEvent,
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
