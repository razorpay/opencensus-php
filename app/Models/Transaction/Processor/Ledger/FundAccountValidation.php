<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use Ramsey\Uuid\Uuid;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
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

    /***
     * @param Entity $fundAccountValidation
     * @param string $transactorEvent
     * @param int $transactorDate
     * @param array $ftsSourceAccountInformation
     */
    public function pushTransactionToLedger(Entity $fundAccountValidation,
                                            string $transactorEvent,
                                            int    $transactorDate,
                                            array  $ftsSourceAccountInformation = [])
    {
        $startTime = millitime();

        try
        {

            /**
             * Check whether the event is default or not. Default event is set when there
             * is no event registered at ledger for that fav status.
             * In this case, it is not required to push transaction through sns.
             */
            if ($this->isDefaultEvent($transactorEvent))
            {
                $this->trace->info(
                    TraceCode::LEDGER_JOURNAL_TRANSACTOR_EVENT_NOT_REGISTERED,
                    [
                        self::TRANSACTOR_EVENT => $transactorEvent,
                        self::ENTITY           => $fundAccountValidation,
                    ]);

                return;
            }

            $transactionId = $fundAccountValidation->getTransactionId();
            $ftsSourceAccountData = [];
            $apiTransactionId = null;
            $transactorId = null;

            $commission = (string)$fundAccountValidation->getFee();
            $tax = (string)$fundAccountValidation->getTax();

            // For postpaid merchant, commission and tax will be zero, as they get collected later not during these flows.
            if ($fundAccountValidation->merchant->isPostpaid() === true){
                $commission = '0';
                $tax = '0';
            }

            switch ($transactorEvent)
            {
                case self::FAV_INITIATED:
                    $transactorDate = $fundAccountValidation->getCreatedAt();
                    $apiTransactionId = $fundAccountValidation->getTransactionId();
                    $transactorId = $fundAccountValidation->getPublicId();
                    break;

                case self::FAV_REVERSED:
                case self::FAV_PROCESSED:
                    $ftsSourceAccountData = $this->getFtsSourceAccountData($ftsSourceAccountInformation);
                    $transactorId = $fundAccountValidation->getPublicId();

                    break;

                case self::FAV_FAILED:
                    $ftsSourceAccountData = $this->getFtsSourceAccountData($ftsSourceAccountInformation);
                    $apiTransactionId = $fundAccountValidation->reversal->getTransactionId();
                    $transactorId = $fundAccountValidation->reversal->getPublicId();
                    $transactorDate = $fundAccountValidation->reversal->getCreatedAt();
                    $transactionId = $fundAccountValidation->reversal->getTransactionId();

                    break;

                default:
                    throw new LogicException(self::TRANSACTOR_EVENT . ' not implemented at ledger : ' . $transactorEvent);
            }

            $notes = [
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($fundAccountValidation->getBalanceId()),
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($transactionId)
            ];

            $identifiers = [
                self::BANKING_ACCOUNT_ID => $fundAccountValidation->balance->bankingAccount->getPublicId(),
            ];

            $additional_params = [];

            $payload = [
                self::TENANT             => self::X,
                self::MODE               => $this->mode,
                self::IDEMPOTENCY_KEY    => Uuid::uuid1()->toString(),
                self::MERCHANT_ID        => $fundAccountValidation->getMerchantId(),
                self::CURRENCY           => $fundAccountValidation->getCurrency(),
                self::AMOUNT             => (string) $fundAccountValidation->getAmount(),
                self::BASE_AMOUNT        => (string) $fundAccountValidation->getBaseAmount(),
                self::COMMISSION         => $commission,
                self::TAX                => $tax,
                self::NOTES              => json_encode($notes),
                self::TRANSACTOR_ID      => $transactorId,
                self::TRANSACTOR_EVENT   => $transactorEvent,
                self::TRANSACTION_DATE   => $transactorDate,
                self::ADDITIONAL_PARAMS  => json_encode($additional_params),
                self::IDENTIFIERS        => $identifiers,
            ];

            if (empty($apiTransactionId) === false)
            {
                $payload[self::API_TRANSACTION_ID] = $apiTransactionId;
            }

            $payload[self::IDENTIFIERS] = array_merge($payload[self::IDENTIFIERS], $ftsSourceAccountData);
            $payload[self::IDENTIFIERS] = json_encode($payload[self::IDENTIFIERS]);

            $this->pushToLedgerSns($payload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_FAV_PAYLOAD_ERROR,
                [
                    self::TRANSACTOR_ID    => $fundAccountValidation->getPublicId(),
                    self::TRANSACTOR_EVENT => $transactorEvent,
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
}
