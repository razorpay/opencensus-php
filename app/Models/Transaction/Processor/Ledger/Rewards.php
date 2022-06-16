<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use Ramsey\Uuid\Uuid;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Credits\Entity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Rewards extends Base
{
    // The event remains `fund_loading_processed` similar to normal fund loading
    // but here, we are loading funds into the rewards balance
    const FUND_LOADING_PROCESSED = "fund_loading_processed";

    public function pushTransactionToLedger(Entity $credits,
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
                    TraceCode::LEDGER_JOURNAL_TRANSACTOR_EVENT_NOT_REGISTERED,//
                    [
                        self::TRANSACTOR_EVENT => $transactorEvent,
                        self::ENTITY           => $credits,
                    ]);

                return;
            }

            $notes = [
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($credits->getBalanceId()),
            ];

            $additional_params = [
                self::FEE_ACCOUNTING => self::REWARD,
            ];

            $identifiers = [];

            $payload = [
                self::TENANT                => self::X,
                self::MODE                  => $this->mode,
                self::IDEMPOTENCY_KEY       => Uuid::uuid1()->toString(),
                self::MERCHANT_ID           => $credits->getMerchantId(),
                self::CURRENCY              => Currency::INR,
                self::AMOUNT                => (string) $credits->getValue(),
                self::BASE_AMOUNT           => (string) $credits->getValue(),
                // Fees and tax is always zero when loading reward credits to the merchant's balance.
                self::COMMISSION            => '0',
                self::TAX                   => '0',
                self::NOTES                 => json_encode($notes),
                self::TRANSACTOR_ID         => $credits->getPublicId(),
                self::TRANSACTOR_EVENT      => $transactorEvent,
                self::ADDITIONAL_PARAMS     => json_encode($additional_params),
                self::TRANSACTION_DATE      => $credits->getCreatedAt(),
                self::IDENTIFIERS           => json_encode($identifiers),
            ];

            $this->pushToLedgerSns($payload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_REWARD_LOADING_PAYLOAD_ERROR,
                [
                    self::TRANSACTOR_ID    => $credits->getPublicId(),
                    self::TRANSACTOR_EVENT => $transactorEvent,
                ]);
        }
        finally
        {
            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_REWARD_LOADING_STREAMING_TIME_TAKEN,
                [
                    self::TIME_TAKEN => millitime() - $startTime,
                ]);
        }
    }

    /**
     * Create payload for Journal function for credits fundloading
     *
     * @param \RZP\Models\BankTransfer\Entity $credits
     *
     * @throws BadRequestException
     * @throws \Throwable
     */
    public function createPayloadForJournalEntry(Entity $credits,
                                                 string $transactorEvent)
    {

        $notes = [
            self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($credits->getBalanceId()),
        ];

        $additional_params = [
            self::FEE_ACCOUNTING => self::REWARD,
        ];

        $payload = [
            self::TENANT                => self::X,
            self::MODE                  => $this->mode,
            self::IDEMPOTENCY_KEY       => Uuid::uuid1()->toString(),
            self::MERCHANT_ID           => $credits->getMerchantId(),
            self::CURRENCY              => Currency::INR,
            self::AMOUNT                => (string) $credits->getValue(),
            self::BASE_AMOUNT           => (string) $credits->getValue(),
            // Fees and tax is always zero when loading reward credits to the merchant's balance.
            self::COMMISSION            => '0',
            self::TAX                   => '0',
            self::NOTES                 => $notes,
            self::TRANSACTOR_ID         => $credits->getPublicId(),
            self::TRANSACTOR_EVENT      => $transactorEvent,
            self::ADDITIONAL_PARAMS     => $additional_params,
            self::TRANSACTION_DATE      => $credits->getCreatedAt(),
        ];

        return $payload;
    }
}
