<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use Ramsey\Uuid\Uuid;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundAccount\Validation\Status;
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
                    $reversal = $fundAccountValidation->reversal;

                    $ftsSourceAccountData = $this->getFtsSourceAccountData($ftsSourceAccountInformation);

                    if ($reversal !== null)
                    {
                        $apiTransactionId = $reversal->getTransactionId();
                        $transactorId = $reversal->getPublicId();
                        $transactorDate = $reversal->getCreatedAt();
                        $transactionId = $reversal->getTransactionId();
                    }
                    else
                    {
                        $transactorId = $fundAccountValidation->getPublicId();
                    }

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

    /**
     * Use the entity to create request payload for ledger and then call ledger
     *
     * @param Entity     $validation
     *
     * @param array|null $ftsSourceAccountInformation
     *
     * @return array $response
     * @throws BadRequestException
     * @throws \Throwable
     */
    public function processValidationAndCreateJournalEntry(Entity $validation, array $ftsSourceAccountInformation = [], string $status = null, PublicCollection $feeSplit = null): array
    {
        $payload = $this->createLedgerPayloadFromEntity($validation, $ftsSourceAccountInformation, $status);

        return $this->createJournalEntry($payload, self::DEFAULT_MAX_RETRY_COUNT, 0, $feeSplit);
    }

    /**
     * Create payload from the validation
     * @param Entity $validation
     * @param array $ftsSourceAccountInformation
     * @param string|null $favStatus
     * @return array
     */
    public function createLedgerPayloadFromEntity(Entity $validation, array $ftsSourceAccountInformation = [], string $favStatus = null): array
    {
        if ($favStatus === null)
        {
            $favStatus = $validation->getStatus();
        }

        $status = Status::getLedgerEventFromFavStatus($favStatus);

        $notes = [
            self::BALANCE_ID => BalanceEntity::getSignedIdOrNull($validation->getBalanceId()),
        ];

        $identifiers = [
            self::BANKING_ACCOUNT_ID => $validation->balance->bankingAccount->getPublicId(),
        ];

        $ftsSourceAccountData = $this->getFtsSourceAccountData($ftsSourceAccountInformation);

        $identifiers = array_merge($identifiers, $ftsSourceAccountData);

        $payload = [
            self::TENANT           => self::X,
            self::MODE             => $this->mode,
            self::MERCHANT_ID      => $validation->getMerchantId(),
            self::CURRENCY         => $validation->getCurrency(),
            self::AMOUNT           => (string) $validation->getAmount(),
            self::BASE_AMOUNT      => (string) $validation->getBaseAmount(),
            self::COMMISSION       => (string) $validation->getFees(),
            self::TAX              => (string) $validation->getTax(),
            self::TRANSACTOR_ID    => $validation->getPublicId(),
            self::TRANSACTOR_EVENT => $status,
            self::TRANSACTION_DATE => $validation->getCreatedAt(),
            self::NOTES            => $notes,
            self::IDENTIFIERS      => $identifiers,
        ];

        if ($status === self::FAV_FAILED and
            $validation->reversal !== null)
        {
            $payload[self::TRANSACTOR_ID]    = $validation->reversal->getPublicId();
            $payload[self::TRANSACTION_DATE] = $validation->reversal->getCreatedAt();
        }

        if ($validation->merchant->isPostpaid() === true)
        {
            $payload[self::COMMISSION] = '0';
            $payload[self::TAX]        = '0';
        }

        return $payload;
    }

    /**
     * Create Journal function for fund account validations
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
                    Errorcode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
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
