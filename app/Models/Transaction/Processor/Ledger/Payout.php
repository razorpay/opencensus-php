<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Models\Payout\Mode;
use RZP\Models\Payout\Entity;
use RZP\Models\Merchant\Credits;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Payout extends Base
{
    // Events
    const PAYOUT_INITIATED = "payout_initiated";
    const PAYOUT_PROCESSED = "payout_processed";
    const PAYOUT_REVERSED  = "payout_reversed";

    public function pushTransactionToLedger(Entity $payout,
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
                        self::ENTITY          => $payout,
                    ]);

                return;
            }

            $transactorId = $payout->getPublicId();
            $transactionId = $payout->getTransactionId();
            $transactorDate = null;
            $optionalPayload = [];

            switch ($transactorType)
            {
                case self::PAYOUT_INITIATED:
                    $transactorDate = $payout->getInitiatedAt();
                    break;

                case self::PAYOUT_PROCESSED:
                    $transactorDate = $payout->getProcessedAt();

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
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($payout->getBalanceId()),
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($transactionId),
            ];

            $payload = [
                self::TRANSACTOR          => self::X,
                self::MODE                => $this->mode,
                self::IDEMPOTENCY_KEY     => gen_uuid(self::UUID_FORMAT),
                self::MERCHANT_ID         => $payout->getMerchantId(),
                self::CURRENCY            => $payout->getCurrency(),
                self::AMOUNT              => (string) $payout->getAmount(),
                self::BASE_AMOUNT         => (string) $payout->getBaseAmount(),
                self::COMMISSION          => (string) $payout->getFee(),
                self::TAX                 => (string) $payout->getTax(),
                self::NOTES               => json_encode($notes),
                self::TRANSACTOR_ID       => $transactorId,
                self::TRANSACTOR_TYPE     => $transactorType,
                self::TRANSACTION_DATE    => $transactorDate,
            ];

            $payload = array_merge($payload, $optionalPayload);

            $this->updatePayloadForPrePaidSourceAccounts($payload, $payout);

            $this->updatePayloadForFeeCredits($payload, $payout);

            $this->pushToLedgerSns($payload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_PAYOUT_PAYLOAD_ERROR,
                [
                    self::TRANSACTOR_ID   => $payout->getPublicId(),
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

    protected function updatePayloadForPrePaidSourceAccounts(array &$payload,
                                                             Entity $payout)
    {
        // If the FTS fund account Id key does not exist, return.
        // Will happen in the case of `payout_initiated` event
        if (array_key_exists(self::FTS_FUND_ACCOUNT_ID, $payload) === false)
        {
            return;
        }

        if ($payout->getMode() === Mode::AMAZONPAY)
        {
            $payload[self::FTS_FUND_ACCOUNT_ID] = self::DEFAULT_AMAZON_PAY_FTS_FUND_ACCOUNT_ID;
            $payload[self::FTS_ACCOUNT_TYPE]    = self::DEFAULT_AMAZON_PAY_FTS_FUND_ACCOUNT_TYPE;
        }

        if ($payout->getChannel() === Channel::M2P)
        {
            $payload[self::FTS_FUND_ACCOUNT_ID] = self::DEFAULT_M2P_FTS_FUND_ACCOUNT_ID;
            $payload[self::FTS_ACCOUNT_TYPE]    = self::DEFAULT_M2P_FTS_FUND_ACCOUNT_TYPE;

        }
    }

    protected function updatePayloadForFeeCredits(array &$payload,
                                                  Entity $payout)
    {
        if ($payout->getFeeType() === Credits\Balance\Type::REWARD_FEE)
        {
            $payload[self::FEE_ACCOUNTING] = self::REWARD;
        }
    }
}
