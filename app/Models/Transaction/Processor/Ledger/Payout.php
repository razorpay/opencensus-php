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
                                            string $transactorEvent,
                                            Reversal\Entity $reversal = null,
                                            array $ftsSourceAccountInformation = [])
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
                        self::ENTITY           => $payout,
                    ]);

                return;
            }

            $payload = $this->getDefaultPayload($payout);

            $transactorId = $payout->getPublicId();
            $transactionId = $payout->getTransactionId();
            $transactorDate = null;
            $ftsSourceAccountData = [];
            $apiTransactionId = null;

            switch ($transactorEvent)
            {
                case self::PAYOUT_INITIATED:
                    $transactorDate = $payout->getInitiatedAt();
                    $apiTransactionId = $payout->getTransactionId();
                    break;

                case self::PAYOUT_PROCESSED:
                    $transactorDate = $payout->getProcessedAt();
                    $ftsSourceAccountData = $this->getFtsSourceAccountData($ftsSourceAccountInformation);

                    break;

                case self::PAYOUT_REVERSED:
                    if ($reversal !== null){
                        $transactorDate = $reversal->getCreatedAt();
                        $transactorId = $reversal->getPublicId();
                        $transactionId = $reversal->getTransactionId();
                        $apiTransactionId = $reversal->getTransactionId();
                    }

                    $ftsSourceAccountData = $this->getFtsSourceAccountData($ftsSourceAccountInformation);

                    break;

                default:
                    throw new LogicException(self::TRANSACTOR_EVENT . ' not implemented at ledger : ' . $transactorEvent);
            }

            $notes = [
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($payout->getBalanceId()),
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($transactionId),
            ];

            $payload[self::NOTES]              = json_encode($notes);
            $payload[self::TRANSACTOR_ID]      = $transactorId;
            $payload[self::TRANSACTOR_EVENT]   = $transactorEvent;
            $payload[self::TRANSACTION_DATE]   = $transactorDate;

            // Only sending api_transaction ID in case of initiated and reversed
            // This remains null for payout_processed event
            if (empty($apiTransactionId) === false)
            {
                $payload[self::API_TRANSACTION_ID] = $apiTransactionId;
            }

            $payload = array_merge($payload, $ftsSourceAccountData);

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
                    self::TRANSACTOR_ID    => $payout->getPublicId(),
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

    protected function updatePayloadForPrePaidSourceAccounts(array &$payload,
                                                             Entity $payout)
    {
        // We are not supposed to send and fts_fund_account_id or account_type for payout initiated
        if ($payload[self::TRANSACTOR_EVENT] === self::PAYOUT_INITIATED)
        {
            return;
        }

        if ($payout->getMode() === Mode::AMAZONPAY)
        {
            $payload[self::FTS_ACCOUNT_TYPE] = self::DEFAULT_AMAZON_PAY_FTS_FUND_ACCOUNT_TYPE;

            if ($this->mode === \RZP\Constants\Mode::TEST)
            {
                $payload[self::FTS_FUND_ACCOUNT_ID] = self::DEFAULT_AMAZON_PAY_FTS_FUND_ACCOUNT_ID;
            }
        }

        if ($payout->getChannel() === Channel::M2P)
        {
            $payload[self::FTS_ACCOUNT_TYPE] = self::DEFAULT_M2P_FTS_FUND_ACCOUNT_TYPE;

            if ($this->mode === \RZP\Constants\Mode::TEST)
            {
                $payload[self::FTS_FUND_ACCOUNT_ID] = self::DEFAULT_M2P_FTS_FUND_ACCOUNT_ID;
            }
        }
    }

    protected function getDefaultPayload(Entity $payout)
    {
        return [
            self::TENANT              => self::X,
            self::MODE                => $this->mode,
            self::IDEMPOTENCY_KEY     => gen_uuid(self::UUID_FORMAT),
            self::MERCHANT_ID         => $payout->getMerchantId(),
            self::CURRENCY            => $payout->getCurrency(),
            self::AMOUNT              => (string) $payout->getAmount(),
            self::BASE_AMOUNT         => (string) $payout->getBaseAmount(),
            self::COMMISSION          => (string) $payout->getFee(),
            self::TAX                 => (string) $payout->getTax(),
            self::BANKING_ACCOUNT_ID  => (string) $payout->bankingAccount->getPublicId(),
        ];
    }

    protected function getFtsSourceAccountData(array $ftsSourceAccountInformation = [])
    {
        // For Test Mode, we shall send default hardcoded data
        if ($this->mode === \RZP\Constants\Mode::TEST)
        {
            return [
                self::FTS_FUND_ACCOUNT_ID => self::DEFAULT_FTS_FUND_ACCOUNT_ID,
                self::FTS_ACCOUNT_TYPE    => self::DEFAULT_FTS_FUND_ACCOUNT_TYPE,
            ];
        }

        if (empty($ftsSourceAccountInformation) === true)
        {
            return $ftsSourceAccountInformation;
        }

        // Specifically converting the values to string as FTS sometimes passes this info as integers
        return [
            self::FTS_FUND_ACCOUNT_ID => (string) $ftsSourceAccountInformation[self::FTS_FUND_ACCOUNT_ID] ?? null,
            self::FTS_ACCOUNT_TYPE    => strtolower((string) $ftsSourceAccountInformation[self::FTS_ACCOUNT_TYPE] )?? null,
        ];
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
