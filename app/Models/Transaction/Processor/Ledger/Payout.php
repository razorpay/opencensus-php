<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use RZP\Constants;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payout\Metric;
use Ramsey\Uuid\Uuid;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Models\External;
use RZP\Models\Payout\Mode;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\Payout\Purpose;
use RZP\Models\Payout\Service;
use RZP\Models\Merchant\Credits;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Models\BankingAccountStatement\Entity as BASEntity;

class Payout extends Base
{
    /**
     * @var \RZP\Models\Payout\Entity
     */
    public $payout;

    public function __construct($payout = null)
    {
        parent::__construct();

        $this->payout = $payout;
    }

    // Events
    const PAYOUT_INITIATED = "payout_initiated";
    const PAYOUT_PROCESSED = "payout_processed";
    const PAYOUT_REVERSED  = "payout_reversed";
    const PAYOUT_FAILED    = "payout_failed";

    const INTER_ACCOUNT_PAYOUT_INITIATED = "inter_account_payout_initiated";
    const INTER_ACCOUNT_PAYOUT_PROCESSED = "inter_account_payout_processed";
    const INTER_ACCOUNT_PAYOUT_REVERSED  = "inter_account_payout_reversed";
    const INTER_ACCOUNT_PAYOUT_FAILED    = "inter_account_payout_failed";

    const VA_TO_VA_PAYOUT_INITIATED = "va_to_va_payout_initiated";
    const VA_TO_VA_PAYOUT_FAILED    = "va_to_va_payout_failed";
    const NODAL_FUND_LOADING = 'nodal_fund_loading';
    const NODAL_FUND_LOADING_REVERSE = 'nodal_fund_loading_reverse';

    // Ledger Events for Direct Accounting
    const DA_PAYOUT_PROCESSED           = "da_payout_processed";
    const DA_PAYOUT_REVERSED            = "da_payout_reversed";
    const DA_EXT_PAYOUT_PROCESSED       = "da_ext_payout_processed";
    const DA_EXT_PAYOUT_REVERSED        = "da_ext_payout_reversed";
    const DA_PAYOUT_PROCESSED_RECON     = "da_payout_processed_recon";
    const DA_PAYOUT_REVERSED_RECON      = "da_payout_reversed_recon";
    const DA_EXT_DEBIT                  = "da_ext_debit";
    const DA_EXT_CREDIT                 = "da_ext_credit";
    const DA_FEE_PAYOUT_PROCESSED       = "da_fee_payout_processed";
    const DA_FEE_PAYOUT_REVERSED        = "da_fee_payout_reversed";
    const DA_EXT_FEE_PAYOUT_PROCESSED   = "da_ext_fee_payout_processed";
    const DA_EXT_FEE_PAYOUT_REVERSED    = "da_ext_fee_payout_reversed";

    // Charge collections event
    const CHARGE_COLLECTIONS_DEBIT_INITIATED  = "cc_debit_initiated";
    const CHARGE_COLLECTIONS_DEBIT_PROCESSED  = "cc_debit_processed";
    const CHARGE_COLLECTIONS_DEBIT_FAILED     = "cc_debit_failed";
    const CHARGE_COLLECTIONS_DEBIT_REVERSED   = "cc_debit_reversed";

    const MAX_IDEM_KEY_LENGTH = 36;

    // This is used to maintain the idempotency key for ledger.
    // Do not add events in between or repeat any value.
    const TRANSACTOR_EVENT_TO_ENUM = [
        self::PAYOUT_INITIATED => 1,
        self::PAYOUT_PROCESSED => 2,
        self::PAYOUT_REVERSED => 3,
        self::PAYOUT_FAILED => 4,
        self::INTER_ACCOUNT_PAYOUT_INITIATED => 5,
        self::INTER_ACCOUNT_PAYOUT_PROCESSED => 6,
        self::INTER_ACCOUNT_PAYOUT_REVERSED => 7,
        self::INTER_ACCOUNT_PAYOUT_FAILED => 8,
        self::VA_TO_VA_PAYOUT_INITIATED => 9,
        self::VA_TO_VA_PAYOUT_FAILED => 10,
    ];

    protected $eventsWithoutFtsInfo = [self::PAYOUT_FAILED,
                                       self::PAYOUT_INITIATED,
                                       self::CHARGE_COLLECTIONS_DEBIT_FAILED,
                                       self::CHARGE_COLLECTIONS_DEBIT_INITIATED,
                                       self::VA_TO_VA_PAYOUT_INITIATED,
                                       self::VA_TO_VA_PAYOUT_FAILED,
                                       self::INTER_ACCOUNT_PAYOUT_INITIATED];

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
                case self::VA_TO_VA_PAYOUT_INITIATED:
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
                case self::VA_TO_VA_PAYOUT_FAILED:
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

    public function pushTransactionToLedgerForDirect(string $transactorEvent,
                                                     Entity $payout = null,
                                                     Reversal\Entity $reversal = null,
                                                     External\Entity $external = null,
                                                     BASEntity $bas = null)
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

            $payload = [];
            $transactorId = null;
            $balanceId = null;
            $transactionId = null;

            if ($payout !== null)
            {
                $payload = $this->getDefaultPayloadForDirectPayout($payout);
                $transactorId = $payout->getPublicId();
                $transactionId = $payout->getTransactionId();
                $balanceId = BalanceEntity::getSignedIdOrNull($payout->getBalanceId());
            }
            else if ($external != null)
            {
                $payload = $this->getDefaultPayloadForDirectExternal($external);
                $transactorId = $external->getPublicId();
                $transactionId = $external->getTransactionId();
                $balanceId = BalanceEntity::getSignedIdOrNull($external->getBalanceId());
            }

            $transactorDate = null;
            $apiTransactionId = null;

            switch ($transactorEvent)
            {
                case self::CHARGE_COLLECTIONS_DEBIT_PROCESSED:
                case self::DA_FEE_PAYOUT_PROCESSED:
                case self::DA_PAYOUT_PROCESSED:
                    $apiTransactionId = $payout->getTransactionId();
                    $transactorDate = $payout->getProcessedAt();
                    // add fee accounting as reward if reward payout
                    $this->updatePayloadForFeeCredits($payload, $payout);

                    break;

                case self::DA_EXT_FEE_PAYOUT_PROCESSED:
                case self::DA_EXT_PAYOUT_PROCESSED:
                    $transactorDate = $payout->getProcessedAt();
                    // add fee accounting as reward if reward payout
                    $this->updatePayloadForFeeCredits($payload, $payout);

                    break;

                case self::CHARGE_COLLECTIONS_DEBIT_REVERSED:
                case self::DA_FEE_PAYOUT_REVERSED:
                case self::DA_PAYOUT_REVERSED:
                    if ($reversal !== null){
                        $transactorDate = $reversal->getCreatedAt();
                        $transactorId = $reversal->getPublicId();
                        $transactionId = $reversal->getTransactionId();
                        $apiTransactionId = $reversal->getTransactionId();
                    }
                    // add fee accounting as reward for reward payouts
                    $this->updatePayloadForFeeCredits($payload, $payout);

                    break;

                case self::DA_EXT_FEE_PAYOUT_REVERSED:
                case self::DA_EXT_PAYOUT_REVERSED:
                    if ($reversal !== null){
                        $transactorDate = $reversal->getCreatedAt();
                        $transactorId = $reversal->getPublicId();
                        $transactionId = $reversal->getTransactionId();
                    }
                    // add fee accounting as reward for reward payouts
                    $this->updatePayloadForFeeCredits($payload, $payout);

                    break;

                case self::DA_PAYOUT_PROCESSED_RECON:
                    $transactorId = $payout->getPublicId();
                    $transactionId = $payout->getTransactionId();
                    if ($bas !== null){
                        $transactorDate = $bas->getTransactionDate();
                    }

                    break;

                case self::DA_PAYOUT_REVERSED_RECON:
                    if ($reversal !== null){
                        $transactorId = $reversal->getPublicId();
                        $transactionId = $reversal->getTransactionId();
                    }
                    if ($bas !== null){
                        $transactorDate = $bas->getTransactionDate();
                    }

                    break;

                case self::DA_EXT_DEBIT:
                case self::DA_EXT_CREDIT:
                    if ($bas !== null){
                        $transactorDate = $bas->getPostedDate() ?? $bas->getTransactionDate();
                    }
                    if ($external != null){
                        $transactorId = $external->getPublicId();
                        $apiTransactionId = $external->getTransactionId();
                    }

                    break;

                default:
                    throw new LogicException(self::TRANSACTOR_EVENT . ' not implemented at ledger : ' . $transactorEvent);
            }

            $notes = [
                self::BALANCE_ID     => $balanceId,
                self::TRANSACTION_ID => TransactionEntity::getSignedIdOrNull($transactionId),
            ];

            $payload[self::NOTES]              = json_encode($notes);
            $payload[self::TRANSACTOR_ID]      = $transactorId;
            $payload[self::TRANSACTOR_EVENT]   = $transactorEvent;
            $payload[self::TRANSACTION_DATE]   = $transactorDate;

            // Only sending api_transaction ID in case of processed and reversed (when txn is created on api)
            // This will not be set for ext to payout/reversal events, recon events and reverse shadow case
            if (empty($apiTransactionId) === false)
            {
                $payload[self::API_TRANSACTION_ID] = $apiTransactionId;
            }

            $payload[self::IDENTIFIERS] = json_encode($payload[self::IDENTIFIERS]);
            $payload[self::ADDITIONAL_PARAMS] = json_encode($payload[self::ADDITIONAL_PARAMS]);

            $this->pushToLedgerSns($payload);
        }
        catch (\Throwable $e)
        {
            $entityForLog = $payout;
            if ($payout === null)
            {
                $entityForLog = $external;
            }
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_PAYOUT_PAYLOAD_ERROR,
                [
                    self::TRANSACTOR_ID    => $entityForLog->getPublicId(),
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

        $payload = $this->createLedgerPayloadFromEntity($payout, null, $reversal, $ftsSourceAccountInformation);

        if ((($payout->isVaToVaPayout() === true) or
             ($payout->isSubAccountPayout() === true)) and
            ($payload['transactor_event'] === self::DEFAULT_EVENT))
        {
            return [];
        }

        return $this->createJournalEntry($payload);
    }

    public function createLedgerPayloadFromEntity(Entity $payout, string $status = null, Reversal\Entity $reversal = null, array $ftsSourceAccountInformation = [])
    {
        if ($status === null)
        {
            $status = $payout->getStatus();
        }
        $transactorEvent = Status::getLedgerEventForPayout($payout, $status);

        $notes = [
            self::BALANCE_ID => BalanceEntity::getSignedIdOrNull($payout->getBalanceId()),
        ];

        $identifiers = [
            self::BANKING_ACCOUNT_ID => (new \RZP\Models\BankingAccount\Service())->fetchBankingAccountIdByBalanceId($payout->balance->getId()),
        ];

        $ftsSourceAccountData = $this->getFtsSourceAccountData($ftsSourceAccountInformation);


        // Ledger doesn't need fts information in case of payout initiated or payout failed events
        if (in_array($transactorEvent, $this->eventsWithoutFtsInfo, true) === false)
        {
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
            self::TRANSACTOR_EVENT => $transactorEvent,
            self::TRANSACTION_DATE => $payout->getCreatedAt(),
            self::NOTES            => $notes,
            self::IDENTIFIERS      => $identifiers,
        ];

        if (($transactorEvent === self::PAYOUT_REVERSED) or
            ($transactorEvent === self::PAYOUT_FAILED) or
            ($transactorEvent === self::CHARGE_COLLECTIONS_DEBIT_FAILED) or
            ($transactorEvent === self::CHARGE_COLLECTIONS_DEBIT_REVERSED) or
            ($transactorEvent === self::INTER_ACCOUNT_PAYOUT_REVERSED) or
            ($transactorEvent === self::INTER_ACCOUNT_PAYOUT_FAILED) or
            ($transactorEvent === self::VA_TO_VA_PAYOUT_FAILED))
        {
            if ($reversal !== null)
            {
                $payload[self::TRANSACTOR_ID]    = $reversal->getPublicId();
                $payload[self::TRANSACTION_DATE] = $reversal->getCreatedAt();
            }
        }

        if (($transactorEvent === self::PAYOUT_INITIATED) or
            ($transactorEvent === self::INTER_ACCOUNT_PAYOUT_INITIATED) or
            ($transactorEvent === self::VA_TO_VA_PAYOUT_INITIATED))
        {
            $payload[self::TRANSACTION_DATE] = $payout->getInitiatedAt();
        }

        if (($transactorEvent === self::PAYOUT_PROCESSED) or
            ($transactorEvent === self::CHARGE_COLLECTIONS_DEBIT_PROCESSED) or
            ($transactorEvent === self::INTER_ACCOUNT_PAYOUT_PROCESSED))
        {
            $payload[self::TRANSACTION_DATE] = $payout->getProcessedAt();
        }

        if ($payout->getPurpose() === Purpose::RZP_CHARGE_COLLECTIONS) {
            $notes = $payout->getNotes();
            $payload[self::IDENTIFIERS] += [
                PayoutConstants::PRODUCT_ID => $notes[PayoutConstants::PRODUCT_ID],
            ];

            $payload[self::ADDITIONAL_PARAMS] = [
              self::ACCOUNT_TYPE => $notes[PayoutConstants::ACCOUNT_TYPE],
            ];
        }

        $this->updatePayloadForPrePaidSourceAccounts($payload, $payout);

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
    public function createJournalEntry(array $payload, int $maxRetryCount = self::DEFAULT_MAX_RETRY_COUNT, int $retryCount = 0, PublicCollection $feeSplit = null, string $iKey = null)
    {
        try
        {
            if ($iKey === null)
            {
                $iKey = $this->getIdempotencyKeyFromPayload($payload);
            }

            $response = parent::createJournalEntry($payload, $maxRetryCount, $retryCount, $feeSplit, $iKey);
        }
        catch (BadRequestException $e)
        {
            // If it's an insufficient balance case, throw a new exception with a new error code
            if ($e->getCode() === ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE)
            {
                // Check if this payout was a reward fee based payout.
                // If yes, it can mean 2 things
                // 1. There actually was not enough real (non-reward) merchant balance to do the payout.
                // 2. There's not enough reward balance on ledger. Which means API and ledger are not in sync.
                // We shall trace this for case 2. And retry without reward fee for now
                // TODO: Ask ledger microservice team if there's a way to distinguish between case 1 and 2
                // TODO: Ask if this case will be handled in async retries
                if (isset($payload[self::ADDITIONAL_PARAMS][self::FEE_ACCOUNTING]) === true and
                    $payload[self::ADDITIONAL_PARAMS][self::FEE_ACCOUNTING] === self::REWARD)
                {
                    unset($payload[self::ADDITIONAL_PARAMS][self::FEE_ACCOUNTING]);

                    // unset the key if its empty
                    if (empty($payload[self::ADDITIONAL_PARAMS]))
                    {
                        unset($payload[self::ADDITIONAL_PARAMS]);
                    }

                    $this->trace->info(TraceCode::CREDITS_REVERSE_FOR_LEDGER_PAYOUT_FOR_INSUFFICIENT_BALANCE,
                                       [
                                           'payout_id'      => $this->payout->getId(),
                                           'exception_data' => $e->getData(),
                                       ]);

                    (new Credits\Transaction\Core)->reverseCreditsForSource(
                        $this->payout->getId(),
                        Constants\Entity::PAYOUT,
                        $this->payout);

                    $this->trace->count(Metric::CREDITS_REVERSE_FOR_LEDGER_PAYOUT_FOR_INSUFFICIENT_BALANCE_COUNT,
                                        [
                                            'environment'    => $this->app['env']
                                        ]);

                    unset($this->payout[Entity::FEE_TYPE]);

                    $this->repo->saveOrFail($this->payout);

                    return $this->createJournalEntry($payload);
                }

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

    protected function getIdempotencyKeyFromPayload(array $payload): ?string
    {
        $transactorId = $payload[self::TRANSACTOR_ID];
        $transactorEvent = $payload[self::TRANSACTOR_EVENT];
        $transactionDate = $payload[self::TRANSACTION_DATE];
        $merchantId = $payload[self::MERCHANT_ID];

        if (!array_key_exists($transactorEvent, self::TRANSACTOR_EVENT_TO_ENUM))
        {
            throw new LogicException(
                'Transactor enum not available',
                null,
                $payload);
        }

        $idemKey = $transactorId . $transactionDate . "_" . self::TRANSACTOR_EVENT_TO_ENUM[$transactorEvent];

        if (strlen($idemKey) > self::MAX_IDEM_KEY_LENGTH)
        {
            throw new LogicException(
                'Idempotency key is longer than expected',
                null,
                [
                    'ikey' => $idemKey,
                    'payload' => $payload
                ]);
        }

        return $idemKey;
    }

    protected function updatePayloadForPrePaidSourceAccounts(array &$payload,
                                                             Entity $payout)
    {
        // We are not supposed to send and fts_fund_account_id or account_type for payout initiated

        if ($payload[self::TRANSACTOR_EVENT] === self::INTER_ACCOUNT_PAYOUT_INITIATED or
            $payload[self::TRANSACTOR_EVENT] === self::PAYOUT_INITIATED or
            $payload[self::TRANSACTOR_EVENT] === self::VA_TO_VA_PAYOUT_INITIATED)
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

    protected function getDefaultPayloadForDirectPayout(Entity $payout)
    {
        $additional_params = [];
        $channel = $payout->getChannel();
        $notes = $payout->getNotes();
        $accountNumber = $payout->balance->getAccountNumber();
        $basDetails = $this->repo->banking_account_statement_details->fetchByAccountNumberAndChannel($accountNumber, $channel);

        $identifiers = [
            self::BANKING_ACCOUNT_STMT_DETAIL_ID  => (string) $basDetails->getPublicId(),
        ];

        if ((isset($notes) === true) and
            (isset($notes[PayoutConstants::PRODUCT_ID]) === true)) {
            $identifiers += [
                PayoutConstants::PRODUCT_ID => $notes[PayoutConstants::PRODUCT_ID],
            ];
            }

        if ((isset($notes) === true) and
            (isset($notes[PayoutConstants::ACCOUNT_TYPE]) === true)) {
            $additional_params += [
                PayoutConstants::ACCOUNT_TYPE => $notes[PayoutConstants::ACCOUNT_TYPE],
            ];
        }

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

    protected function getDefaultPayloadForDirectExternal(External\Entity $external)
    {
        $additional_params = [];
        $channel = $external->getChannel();
        $accountNumber = $external->balance->getAccountNumber();
        $basDetails = $this->repo->banking_account_statement_details->fetchByAccountNumberAndChannel($accountNumber, $channel);

        $identifiers = [
            self::BANKING_ACCOUNT_STMT_DETAIL_ID  => (string) $basDetails->getPublicId(),
        ];

        return [
            self::TENANT              => self::X,
            self::MODE                => $this->mode,
            self::IDEMPOTENCY_KEY     => Uuid::uuid1()->toString(),
            self::MERCHANT_ID         => $external->getMerchantId(),
            self::CURRENCY            => $external->getCurrency(),
            self::AMOUNT              => (string) $external->getAmount(),
            self::BASE_AMOUNT         => (string) $external->getBaseAmount(),
            self::COMMISSION          => "", // rzp fees and tax to be 0 for external transactions
            self::TAX                 => "",
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
