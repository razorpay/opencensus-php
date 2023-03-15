<?php

namespace RZP\Models\Transaction\Statement\Ledger\Statement;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HyperTrace;
use RZP\Models\BankingAccount;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;
use RZP\Models\BankingAccountStatement;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\BankingAccount\Entity as BankingEntity;
use RZP\Models\Transaction\Processor\Ledger as LedgerProcessor;
use RZP\Models\BankingAccountStatement\Details\Entity as BankingAccountStmtDetailsEntity;

class Service extends Base\Service
{
    /** @var LedgerService $ledgerService */
    protected $ledgerService;

    // constants
    const TIME_TAKEN                        = 'time_taken';
    const JOURNAL_ID                        = 'journal_id';
    const MERCHANT_ID                       = 'merchant_id';
    const BANKING_ACCOUNT_ID                = 'banking_account_id';
    const BANKING_ACCOUNT_STMT_DETAILS_ID   = 'banking_account_stmt_detail_id';

    // transaction response constants
    const ID             = 'id';
    const ENTITY         = 'entity';
    const TRANSACTION    = 'transaction';
    const ACCOUNT_NUMBER = 'account_number';
    const SOURCE         = 'source';

    // ledger response constants
    const LEDGER_ENTRY     = 'ledger_entry';
    const TRANSACTOR_ID    = 'transactor_id';
    const TRANSACTOR_EVENT = 'transactor_event';
    const TYPE             = 'type';

    // common response constants
    const AMOUNT       = 'amount';
    const BALANCE      = 'balance';
    const CURRENCY     = 'currency';
    const CREDIT       = 'credit';
    const DEBIT        = 'debit';
    const CREATED_AT   = 'created_at';

    // transaction's balance account type constants
    const BALANCE_ACCOUNT_TYPE  = 'balance_account_type';
    const DIRECT                = 'direct';
    const SHARED                = 'shared';

    const REVERSE_SHADOW_FEATURE = 'reverse_shadow_feature';

    public function __construct()
    {
        parent::__construct();

        $this->ledgerService = $this->app['ledger'];
    }


    // Stores mapping between ledger's transactor_events and transaction's source entity.
    // For each of these transactor_events, a new transaction is created at API monolith.
    private static $ledgerTxnEventToTxnSourceEntityMap = [
        LedgerProcessor\Payout::PAYOUT_INITIATED                  => E::PAYOUT,
        LedgerProcessor\Payout::VA_TO_VA_PAYOUT_INITIATED         => E::PAYOUT,
        LedgerProcessor\Payout::VA_TO_VA_PAYOUT_FAILED            => E::PAYOUT,
        LedgerProcessor\Payout::DA_PAYOUT_PROCESSED               => E::PAYOUT,
        LedgerProcessor\Payout::DA_FEE_PAYOUT_PROCESSED           => E::PAYOUT,
        LedgerProcessor\Payout::DA_EXT_PAYOUT_PROCESSED           => E::PAYOUT,
        LedgerProcessor\Payout::DA_EXT_FEE_PAYOUT_PROCESSED       => E::PAYOUT,
        LedgerProcessor\Payout::PAYOUT_FAILED                     => E::REVERSAL,
        LedgerProcessor\Payout::PAYOUT_REVERSED                   => E::REVERSAL,
        LedgerProcessor\Payout::DA_PAYOUT_REVERSED                => E::REVERSAL,
        LedgerProcessor\Payout::DA_FEE_PAYOUT_REVERSED            => E::REVERSAL,
        LedgerProcessor\Payout::DA_EXT_PAYOUT_REVERSED            => E::REVERSAL,
        LedgerProcessor\Payout::DA_EXT_FEE_PAYOUT_REVERSED        => E::REVERSAL,
        LedgerProcessor\FundLoading::FUND_LOADING_PROCESSED       => E::BANK_TRANSFER,
        LedgerProcessor\FundAccountValidation::FAV_INITIATED      => E::FUND_ACCOUNT_VALIDATION,
        LedgerProcessor\FundAccountValidation::FAV_FAILED         => E::REVERSAL,
        LedgerProcessor\Adjustment::POSITIVE_ADJUSTMENT_PROCESSED => E::ADJUSTMENT,
        LedgerProcessor\Adjustment::NEGATIVE_ADJUSTMENT_PROCESSED => E::ADJUSTMENT,
        LedgerProcessor\CreditTransfer::VA_TO_VA_CREDIT_PROCESSED => E::CREDIT_TRANSFER,
        LedgerProcessor\Payout::DA_EXT_DEBIT                      => E::EXTERNAL,
        LedgerProcessor\Payout::DA_EXT_CREDIT                     => E::EXTERNAL,
    ];

    public static function getTxnSourceEntityFromLedgerTxnEvent(string $transactorEvent) :string
    {
        return static::$ledgerTxnEventToTxnSourceEntityMap[$transactorEvent];
    }

    /**
     * Calling ledger service to fetch transactions. Here, txn_id is used to
     * fetch journal since txn_id is journal_id at ledger.
     * After fetching journal, attaching the source entity fields of payouts, reversal,
     * bank transfer, adjustment etc to the txn array.
     *
     * This method uses ledger's journal fetchById endpoint
     *
     * @param string $id
     * @return array
     */
    public function fetchByIdFromLedger(string $id): array {

        $startTime = millitime();
        $transaction = [];
        $balanceAccountType = null;
        $reverseShadowFeature = null;

        try
        {
            $request = [
                self::ID         => PublicEntity::stripDefaultSign($id),
            ];

            $requestHeaders = [
                LedgerProcessor\Base::LEDGER_TENANT_HEADER => LedgerProcessor\Base::X
            ];

            $response = $this->ledgerService->fetchById($request, $requestHeaders);

            $statusCode = $response[LedgerService::RESPONSE_CODE];
            $body       = $response[LedgerService::RESPONSE_BODY];

            if ($statusCode !== 200)
            {
                throw new ServerErrorException('Received invalid status code',
                    ErrorCode::SERVER_ERROR_LEDGER_JOURNAL_FETCH_TRANSACTION,
                    [
                        LedgerService::RESPONSE_CODE => $statusCode,
                        LedgerService::RESPONSE_BODY => $body,
                    ]
                );
            }

            list($merchantBalanceLedgerEntry , $balanceAccountType) = $this->getBalanceAccountTypeAndLedgerEntryFromJournal($body);

            // use ledger response only if corresponding reverse shadow feature is enabled
            if (($balanceAccountType === self::SHARED) and ($this->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true))
            {
                $reverseShadowFeature = Feature\Constants::LEDGER_REVERSE_SHADOW;
                $bankingAccountId = $merchantBalanceLedgerEntry[LedgerProcessor\Base::ACCOUNT_ENTITIES][self::BANKING_ACCOUNT_ID][0];
                $bankingAccount = (new BankingAccount\Repository)->findByPublicId($bankingAccountId);
                $transaction = $this->constructTransactionForSharedBalanceFromLedgerResponse($id, $bankingAccount, $merchantBalanceLedgerEntry, $body);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_FETCH_TRANSACTION_ERROR, [$id]);

            $this->trace->count(Constants\Metric::LEDGER_JOURNAL_FETCH_TRANSACTION_ERROR_TOTAL);

            Tracer::startSpanWithAttributes(HyperTrace::LEDGER_JOURNAL_FETCH_TRANSACTION_ERROR_TOTAL,
                                            [
                                                'environment' => $this->app['env'],
                                            ]);
        }
        finally
        {
            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_FETCH_TRANSACTION_TIME_TAKEN,
                [
                    self::TIME_TAKEN                => millitime() - $startTime,
                    self::BALANCE_ACCOUNT_TYPE      => $balanceAccountType,
                    self::REVERSE_SHADOW_FEATURE    => $reverseShadowFeature,
                ]);
        }
        return $transaction;
    }

    private function getBalanceAccountTypeAndLedgerEntryFromJournal($journalResponse)
    {
        // check if the transaction is on shared or direct balance from ledger's response
        foreach($journalResponse[self::LEDGER_ENTRY] as $ledgerEntry) {
            if ((empty($ledgerEntry[LedgerProcessor\Base::ACCOUNT_ENTITIES]) === false) and
                (empty($ledgerEntry[LedgerProcessor\Base::ACCOUNT_ENTITIES][LedgerProcessor\Base::ACCOUNT_TYPE]) === false) and
                (empty($ledgerEntry[LedgerProcessor\Base::ACCOUNT_ENTITIES][LedgerProcessor\Base::FUND_ACCOUNT_TYPE]) === false)) {

                if ($ledgerEntry[LedgerProcessor\Base::ACCOUNT_ENTITIES][LedgerProcessor\Base::ACCOUNT_TYPE][0] !== LedgerProcessor\Base::PAYABLE)
                {
                    continue;
                }

                if ($ledgerEntry[LedgerProcessor\Base::ACCOUNT_ENTITIES][LedgerProcessor\Base::FUND_ACCOUNT_TYPE][0] === LedgerProcessor\Base::MERCHANT_VA)
                {
                    return [$ledgerEntry, self::SHARED];
                }
                else if ($ledgerEntry[LedgerProcessor\Base::ACCOUNT_ENTITIES][LedgerProcessor\Base::FUND_ACCOUNT_TYPE][0] === LedgerProcessor\Base::MERCHANT_DA)
                {
                    // for transactor events where multiple ledger entries are created on merchant balance account in DA
                    // pick the appropriate one - the one corresponding to payout/reversal, ignoring the other corresponding to external's reversal
                    if (in_array($journalResponse[self::TRANSACTOR_EVENT], LedgerProcessor\Base::DA_LEDGER_EXT_TO_ENTITY_DEBIT_EVENTS, true) === true)
                    {
                        // In cases where merchant balance is debited for entity, pick the ledger entry which corresponds to debit record
                        if ($ledgerEntry[self::TYPE] === self::DEBIT)
                        {
                            return [$ledgerEntry, self::DIRECT];
                        }
                    }
                    else if (in_array($journalResponse[self::TRANSACTOR_EVENT], LedgerProcessor\Base::DA_LEDGER_EXT_TO_ENTITY_CREDIT_EVENTS, true) === true)
                    {
                        // In cases where merchant balance is credited for entity, pick the ledger entry which corresponds to credit record
                        if ($ledgerEntry[self::TYPE] === self::CREDIT)
                        {
                            return [$ledgerEntry, self::DIRECT];
                        }
                    }
                    else
                    {
                        // for the other transactor events where single entry is made on merchant balance, we can return the same
                        return [$ledgerEntry, self::DIRECT];
                    }
                }
            }
        }

        return [null, null];
    }

    private function constructTransactionForSharedBalanceFromLedgerResponse(string $id, BankingEntity $bankingAccount, array $ledgerEntry, array $journalResponse) :array {
        $transaction = [
            self::ID             => $id,
            self::ENTITY         => self::TRANSACTION,
            self::ACCOUNT_NUMBER => $bankingAccount->getAccountNumber(),
            self::AMOUNT         => (int) $ledgerEntry[self::AMOUNT],
            self::CURRENCY       => $ledgerEntry[self::CURRENCY],
            self::CREDIT         => 0,
            self::DEBIT          => (int) $ledgerEntry[self::AMOUNT],  // "debit" field is non-zero in case of payouts, fav etc.
            self::BALANCE        => (int) $ledgerEntry[self::BALANCE],
            self::CREATED_AT     => $ledgerEntry[self::CREATED_AT],
            self::SOURCE         => [],
        ];

        if ($ledgerEntry[self::TYPE] === self::CREDIT)
        {
            // When credit amount is non zero, "credit" field is set from "amount" field in ledger response.
            // "debit" field is 0 in transaction entity in this case.
            // Happens in case of fund loading.
            $transaction[self::CREDIT] = (int) $ledgerEntry[self::AMOUNT];
            $transaction[self::DEBIT] = 0;
        }

        $sourceId = $journalResponse[self::TRANSACTOR_ID];
        $sourceType = static::getTxnSourceEntityFromLedgerTxnEvent($journalResponse[self::TRANSACTOR_EVENT]);

        $this->repo->ledger_statement->setSourceForTransaction($sourceId, $sourceType, $transaction, $this->merchant);

        return $transaction;
    }

    private function constructTransactionForDirectBalanceFromLedgerResponse(string $id, BankingAccountStmtDetailsEntity $bankingAccountStmtDetails, array $journalResponse) :array {

        $sourceId = $journalResponse[self::TRANSACTOR_ID];
        $sourceType = static::getTxnSourceEntityFromLedgerTxnEvent($journalResponse[self::TRANSACTOR_EVENT]);

        $basEntity = (new BankingAccountStatement\Repository)->fetchBySourceEntityIDAndEntityType(PublicEntity::stripDefaultSign($sourceId), $sourceType);

        $transaction = [
            self::ID             => $id,
            self::ENTITY         => self::TRANSACTION,
            self::ACCOUNT_NUMBER => $bankingAccountStmtDetails->getAccountNumber(),
            self::AMOUNT         => (int) $basEntity[self::AMOUNT],
            self::CURRENCY       => $basEntity[self::CURRENCY],
            self::CREDIT         => 0,
            self::DEBIT          => (int) $basEntity[self::AMOUNT],  // "debit" field is non-zero in case of payouts, fav etc.
            self::BALANCE        => (int) $basEntity[self::BALANCE],
            self::CREATED_AT     => $basEntity[self::CREATED_AT],
            self::SOURCE         => [],
        ];

        if ($basEntity[self::TYPE] === self::CREDIT)
        {
            // When credit amount is non zero, "credit" field is set from "amount" field in ledger response.
            // "debit" field is 0 in transaction entity in this case.
            // Happens in case of fund loading.
            $transaction[self::CREDIT] = (int) $basEntity[self::AMOUNT];
            $transaction[self::DEBIT] = 0;
        }

        $this->repo->ledger_statement->setSourceForTransaction($sourceId, $sourceType, $transaction, $this->merchant);

        return $transaction;
    }
}
