<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use Ramsey\Uuid\Uuid;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicCollection;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankTransfer\Entity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Transaction\Processor\Ledger\FundLoading as LedgerFundLoading;

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
                self::IDEMPOTENCY_KEY       => Uuid::uuid1()->toString(),
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

    /**
     * Create Journal function for fundloading
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
                    Errorcode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
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
     * Create payload for Journal function for fundloading
     *
     * @param Entity $bankTransfer
     * @param string $terminalId
     * @param $terminalAccountType
     * @return array
     * @throws \Exception
     */
    public function createPayloadForJournalEntry(Entity $bankTransfer,
                                                 string $terminalId,
                                                 $terminalAccountType)
    {

        $notes = [
            self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($bankTransfer->getBalanceId()),
        ];

        $terminalAccountType = $terminalAccountType ?? self::DEFAULT_TERMINAL_ACCOUNT_TYPE;

        $identifiers = [
            self::TERMINAL_ID           => $terminalId,
            self::TERMINAL_ACCOUNT_TYPE => $terminalAccountType,
            self::BANKING_ACCOUNT_ID    => $bankTransfer->balance->bankingAccount->getPublicId(),
        ];

        $payload = [
            self::TENANT                => self::X,
            self::MODE                  => $this->mode,
            self::IDEMPOTENCY_KEY       => Uuid::uuid1()->toString(),
            self::MERCHANT_ID           => $bankTransfer->getMerchantId(),
            self::CURRENCY              => Currency::INR,
            self::AMOUNT                => (string) $bankTransfer->getAmount(),
            self::BASE_AMOUNT           => (string) $bankTransfer->getAmount(),
            self::COMMISSION            => (string) $bankTransfer->getTransactionFee(),
            self::TAX                   => (string) $bankTransfer->getTransactionTax(),
            self::NOTES                 => $notes,
            self::TRANSACTOR_ID         => $bankTransfer->getPublicId(),
            self::TRANSACTOR_EVENT      => LedgerFundLoading::FUND_LOADING_PROCESSED,
            self::TRANSACTION_DATE      => $bankTransfer->getCreatedAt(),
            self::IDENTIFIERS           => $identifiers,
        ];

        return $payload;
    }

    /**
     * Create payload for Journal function for fundloading
     *
     * @param string $entityId
     *
     */
    public function createPayloadForTransactionEntry(string $entityId, string $entityName, array $ledgerResponse)
    {
        return parent::createPayloadForTransactionEntry($entityId, $entityName, $ledgerResponse);
    }
}
