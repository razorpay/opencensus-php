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

    /***
     * @param Entity $fundAccountValidation
     * @param string $transactorType
     * @param int $transactorDate
     * @param array $ftsSourceAccountInformation
     */
    public function pushTransactionToLedger(Entity $fundAccountValidation,
                                            string $transactorType,
                                            int $transactorDate,
                                            array $ftsSourceAccountInformation = [])
    {
        $startTime = millitime();

        try
        {

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
                        self::ENTITY          => $fundAccountValidation,
                    ]);

                return;
            }

            $notes = [
                self::BALANCE_ID     => BalanceEntity::getSignedIdOrNull($fundAccountValidation->getBalanceId()),
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($fundAccountValidation->getTransactionId())
            ];

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

            switch ($transactorType)
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

                    break;

                default:
                    throw new LogicException(self::TRANSACTOR_TYPE . ' not implemented at ledger : ' . $transactorType);
            }

            $payload = [
                self::TRANSACTOR         => self::X,
                self::TENANT             => self::X,
                self::MODE               => $this->mode,
                self::IDEMPOTENCY_KEY    => gen_uuid(self::UUID_FORMAT),
                self::MERCHANT_ID        => $fundAccountValidation->getMerchantId(),
                self::CURRENCY           => $fundAccountValidation->getCurrency(),
                self::AMOUNT             => (string) $fundAccountValidation->getAmount(),
                self::BASE_AMOUNT        => (string) $fundAccountValidation->getBaseAmount(),
                self::COMMISSION         => $commission,
                self::TAX                => $tax,
                self::NOTES              => json_encode($notes),
                self::TRANSACTOR_ID      => $transactorId,
                self::TRANSACTOR_TYPE    => $transactorType,
                self::TRANSACTOR_EVENT   => $transactorType,
                self::TRANSACTION_DATE   => $transactorDate,
                self::BANKING_ACCOUNT_ID => $fundAccountValidation->balance->bankingAccount->getPublicId(),
            ];

            if (empty($apiTransactionId) === false)
            {
                $payload[self::API_TRANSACTION_ID] = $apiTransactionId;
            }

            $payload = array_merge($payload, $ftsSourceAccountData);

            $this->pushToLedgerSns($payload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_FAV_PAYLOAD_ERROR,
                [
                    self::TRANSACTOR_ID   => $fundAccountValidation->getPublicId(),
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
