<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use Ramsey\Uuid\Uuid;
use RZP\Error\ErrorCode;
use RZP\Models\Payout\Status;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Models\Payout\Mode;
use RZP\Models\Payout\Entity;
use RZP\Models\Merchant\Credits;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Payout extends Base
{
    // Events
    const PAYOUT_INITIATED = "payout_initiated";
    const PAYOUT_PROCESSED = "payout_processed";
    const PAYOUT_REVERSED  = "payout_reversed";
    const PAYOUT_FAILED    = "payout_failed";

    const INTER_ACCOUNT_PAYOUT_INITIATED = "inter_account_payout_initiated";
    const INTER_ACCOUNT_PAYOUT_PROCESSED = "inter_account_payout_processed";
    const INTER_ACCOUNT_PAYOUT_REVERSED  = "inter_account_payout_reversed";
    const INTER_ACCOUNT_PAYOUT_FAILED    = "inter_account_payout_failed";

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
                case self::INTER_ACCOUNT_PAYOUT_INITIATED:
                case self::PAYOUT_INITIATED:
                    $transactorDate = $payout->getInitiatedAt();
                    $apiTransactionId = $payout->getTransactionId();
                    break;

                case self::INTER_ACCOUNT_PAYOUT_PROCESSED:
                case self::PAYOUT_PROCESSED:
                    $transactorDate = $payout->getProcessedAt();
                    $ftsSourceAccountData = $this->getFtsSourceAccountData($ftsSourceAccountInformation);

                    break;

                case self::INTER_ACCOUNT_PAYOUT_REVERSED:
                case self::PAYOUT_REVERSED:
                    if ($reversal !== null){
                        $transactorDate = $reversal->getCreatedAt();
                        $transactorId = $reversal->getPublicId();
                        $transactionId = $reversal->getTransactionId();
                        $apiTransactionId = $reversal->getTransactionId();
                    }

                    $ftsSourceAccountData = $this->getFtsSourceAccountData($ftsSourceAccountInformation);

                    break;

                case self::INTER_ACCOUNT_PAYOUT_FAILED:
                case self::PAYOUT_FAILED:
                    if ($reversal !== null) {
                        $transactorDate = $reversal->getCreatedAt();
                        $transactorId = $reversal->getPublicId();
                        $transactionId = $reversal->getTransactionId();
                        $apiTransactionId = $reversal->getTransactionId();
                    }

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

            $payload[self::IDENTIFIERS] = array_merge($payload[self::IDENTIFIERS], $ftsSourceAccountData);

            $this->updatePayloadForPrePaidSourceAccounts($payload, $payout);

            $this->updatePayloadForFeeCredits($payload, $payout);

            $payload[self::IDENTIFIERS] = json_encode($payload[self::IDENTIFIERS]);
            $payload[self::ADDITIONAL_PARAMS] = json_encode($payload[self::ADDITIONAL_PARAMS]);

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

    /**
     * Use the entity to create request payload for ledger and then call ledger
     *
     * @param Entity               $payout
     * @param Reversal\Entity|null $reversal
     * @param array                $ftsSourceAccountInformation
     *
     * @return array
     * @throws BadRequestException
     * @throws \Throwable
     */
    public function processPayoutAndCreateJournalEntry(Entity $payout, Reversal\Entity $reversal = null, array $ftsSourceAccountInformation = [])
    {
        $this->trace->info(
            TraceCode::PROCESS_PAYOUT_AND_CREATE_JOURNAL_ENTRY_INIT,
            [
                'payout_id' => $payout->getPublicId(),
                'reversal_id' => optional($reversal)->getPublicId() ?? null,
                'fts_source_account_information' => $ftsSourceAccountInformation,
            ]
        );

        $payload = $this->createLedgerPayloadFromEntity($payout, $reversal, $ftsSourceAccountInformation);

        return $this->createJournalEntry($payload);
    }

    public function createLedgerPayloadFromEntity(Entity $payout, Reversal\Entity $reversal = null, array $ftsSourceAccountInformation = [])
    {
        $status = Status::getLedgerEventFromPayoutStatus($payout->getStatus(), $payout->getPurpose());

        $notes = [
            self::BALANCE_ID => BalanceEntity::getSignedIdOrNull($payout->getBalanceId()),
        ];

        $identifiers = [
            self::BANKING_ACCOUNT_ID => $payout->balance->bankingAccount->getPublicId(),
        ];

        $ftsSourceAccountData = $this->getFtsSourceAccountData($ftsSourceAccountInformation);

        if ($status !== self::PAYOUT_FAILED) {
            $identifiers = array_merge($identifiers, $ftsSourceAccountData);
        }

        $payload = [
            self::TENANT           => self::X,
            self::MODE             => $this->mode,
            self::MERCHANT_ID      => $payout->getMerchantId(),
            self::CURRENCY         => $payout->getCurrency(),
            self::AMOUNT           => (string) $payout->getAmount(),
            self::BASE_AMOUNT      => (string) $payout->getBaseAmount(),
            self::COMMISSION       => (string) $payout->getFees(),
            self::TAX              => (string) $payout->getTax(),
            self::TRANSACTOR_ID    => $payout->getPublicId(),
            self::TRANSACTOR_EVENT => $status,
            self::TRANSACTION_DATE => $payout->getCreatedAt(),
            self::NOTES            => $notes,
            self::IDENTIFIERS      => $identifiers,
        ];

        if (($status === self::PAYOUT_REVERSED) || ($status === self::PAYOUT_FAILED))
        {
            if ($reversal !== null)
            {
                $payload[self::TRANSACTOR_ID]    = $reversal->getPublicId();
                $payload[self::TRANSACTION_DATE] = $reversal->getCreatedAt();
            }
        }

        $this->updatePayloadForPrePaidSourceAccounts($payload, $payout);

        // Keeping this here for future safety
        // Ideally, this won't execute in partial reverse shadow
        $this->updatePayloadForFeeCredits($payload, $payout);

        $this->trace->info(
            TraceCode::LEDGER_REQUEST_PAYLOAD_CREATED,
            [
                'payload' => $payload,
            ]
        );

        return $payload;
    }

    /**
     * Create Journal function for payouts
     *
     * @param array $payload
     *
     * @throws BadRequestException
     * @throws \Throwable
     */
    public function createJournalEntry(array $payload, int $maxRetryCount = self::DEFAULT_MAX_RETRY_COUNT, int $retryCount = 0)
    {
        try
        {
            $response = parent::createJournalEntry($payload, $maxRetryCount, $retryCount);
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

    protected function updatePayloadForPrePaidSourceAccounts(array &$payload,
                                                             Entity $payout)
    {
        // We are not supposed to send and fts_fund_account_id or account_type for payout initiated

        if ($payload[self::TRANSACTOR_EVENT] === self::INTER_ACCOUNT_PAYOUT_INITIATED or
            $payload[self::TRANSACTOR_EVENT] === self::PAYOUT_INITIATED)
        {
            return;
        }

        if ($payout->getMode() === Mode::AMAZONPAY)
        {
            $payload[self::IDENTIFIERS][self::FTS_ACCOUNT_TYPE] = self::DEFAULT_AMAZON_PAY_FTS_FUND_ACCOUNT_TYPE;

            if ($this->mode === \RZP\Constants\Mode::TEST)
            {
                $payload[self::IDENTIFIERS][self::FTS_FUND_ACCOUNT_ID] = self::DEFAULT_AMAZON_PAY_FTS_FUND_ACCOUNT_ID;
            }
        }

        if ($payout->getChannel() === Channel::M2P)
        {
            $payload[self::IDENTIFIERS][self::FTS_ACCOUNT_TYPE] = self::DEFAULT_M2P_FTS_FUND_ACCOUNT_TYPE;

            if ($this->mode === \RZP\Constants\Mode::TEST)
            {
                $payload[self::IDENTIFIERS][self::FTS_FUND_ACCOUNT_ID] = self::DEFAULT_M2P_FTS_FUND_ACCOUNT_ID;
            }
        }
    }

    protected function getDefaultPayload(Entity $payout)
    {
        $identifiers = [
            self::BANKING_ACCOUNT_ID  => (string) $payout->bankingAccount->getPublicId(),
        ];
        $additional_params = [];
        return [
            self::TENANT              => self::X,
            self::MODE                => $this->mode,
            self::IDEMPOTENCY_KEY     => Uuid::uuid1()->toString(),
            self::MERCHANT_ID         => $payout->getMerchantId(),
            self::CURRENCY            => $payout->getCurrency(),
            self::AMOUNT              => (string) $payout->getAmount(),
            self::BASE_AMOUNT         => (string) $payout->getBaseAmount(),
            self::COMMISSION          => (string) $payout->getFee(),
            self::TAX                 => (string) $payout->getTax(),
            self::IDENTIFIERS         => $identifiers,
            self::ADDITIONAL_PARAMS   => $additional_params,
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
            $payload[self::ADDITIONAL_PARAMS][self::FEE_ACCOUNTING] = self::REWARD;
        }
    }
}
