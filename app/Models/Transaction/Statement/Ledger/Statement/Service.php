<?php

namespace RZP\Models\Transaction\Statement\Ledger\Statement;

use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\BankingAccount\Entity as BankingEntity;
use RZP\Models\Transaction\Processor\Ledger as LedgerProcessor;

class Service extends Base\Service
{
    /** @var LedgerService $ledgerService */
    protected $ledgerService;

    // constants
    const TIME_TAKEN         = 'time_taken';
    const JOURNAL_ID         = 'journal_id';
    const MERCHANT_ID        = 'merchant_id';
    const BANKING_ACCOUNT_ID = 'banking_account_id';

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


    public function __construct()
    {
        parent::__construct();

        $this->ledgerService = $this->app['ledger'];
    }


    // Stores mapping between ledger's transactor_events and transaction's source entity.
    // For each of these transactor_events, a new transaction is created at API monolith.
    private static $ledgerTxnEventToTxnSourceEntityMap = [
        LedgerProcessor\Payout::PAYOUT_INITIATED                  => E::PAYOUT,
        LedgerProcessor\Payout::PAYOUT_FAILED                     => E::REVERSAL,
        LedgerProcessor\Payout::PAYOUT_REVERSED                   => E::REVERSAL,
        LedgerProcessor\FundLoading::FUND_LOADING_PROCESSED       => E::BANK_TRANSFER,
        LedgerProcessor\FundAccountValidation::FAV_INITIATED      => E::FUND_ACCOUNT_VALIDATION,
        LedgerProcessor\FundAccountValidation::FAV_REVERSED       => E::REVERSAL,
        LedgerProcessor\Adjustment::POSITIVE_ADJUSTMENT_PROCESSED => E::ADJUSTMENT,
        LedgerProcessor\Adjustment::NEGATIVE_ADJUSTMENT_PROCESSED => E::ADJUSTMENT,
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
     * @param string $id
     * @return array
     */
    public function fetchFromLedger(string $id): array {

        $startTime = millitime();
        $transaction = [];

        try
        {
            $bankingAccount = $this->merchant->sharedBankingBalance->bankingAccount;
            $request = [
                self::JOURNAL_ID         => PublicEntity::stripDefaultSign($id),
                self::MERCHANT_ID        => $this->merchant->getId(),
                self::BANKING_ACCOUNT_ID => $bankingAccount->getPublicId(),
            ];

            $response = $this->ledgerService->fetchMerchantLedgerEntryByID($request);

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

            $ledgerEntry = $body[self::LEDGER_ENTRY];
            $transaction = $this->constructTransactionFromLedger($id, $bankingAccount, $ledgerEntry);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_FETCH_TRANSACTION_ERROR, [$id]);
        }
        finally
        {
            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_FETCH_TRANSACTION_TIME_TAKEN,
                [
                    self::TIME_TAKEN => millitime() - $startTime,
                ]);
        }
        return $transaction;
    }

    private function constructTransactionFromLedger(string $id, BankingEntity $bankingAccount, array $ledgerEntry) :array {
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

        $sourceId = $ledgerEntry[self::TRANSACTOR_ID];
        $sourceType = static::getTxnSourceEntityFromLedgerTxnEvent($ledgerEntry[self::TRANSACTOR_EVENT]);

        $this->repo->ledger_statement->setSourceForTransaction($sourceId, $sourceType, $transaction, $this->merchant);

        return $transaction;
    }
}
