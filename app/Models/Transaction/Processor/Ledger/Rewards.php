<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use RZP\Trace\TraceCode;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Merchant\Credits\Entity;
use RZP\Models\Transaction\Entity as TransactionEntity;
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

            $payload = [
                self::TENANT                => self::X,
                self::MODE                  => $this->mode,
                self::IDEMPOTENCY_KEY       => gen_uuid(self::UUID_FORMAT),
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
                self::FEE_ACCOUNTING        => self::REWARD,
                self::TRANSACTION_DATE      => $credits->getCreatedAt(),
                self::BANKING_ACCOUNT_ID    => $credits->merchant->sharedBankingBalance->bankingAccount->getPublicId(),
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
}
