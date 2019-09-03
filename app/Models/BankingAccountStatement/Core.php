<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;
use RZP\Models\FileStore\Accessor;
use RZP\Models\External;

class Core extends Base\Core
{
    /**
     * Temporary hack. Should not set balance at a class level.
     * This restricts us from processing transactions from
     * multiple account statements at once.
     *
     * @var Merchant\Balance\Entity
     */
    protected $balance;

    /**
     * NOTE: This function should be used for a specific account number only. We cannot
     * call this function for processing transactions of multiple account numbers.
     * This is because we are setting the balance entity at a class level.
     * If you want to process transactions of multiple account numbers
     * in a single shot, the logic for fetching balance should be fixed.
     *
     * @param array $input
     *
     * @return array
     */
    public function processStatementForAccount(array $input)
    {
        $channel       = array_pull($input, Entity::CHANNEL);

        $accountNumber = array_pull($input, Entity::ACCOUNT_NUMBER);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST,
            [
                'channel'        => $channel,
                'account_number' => $accountNumber,
            ]);

        $bankingAccount = (new BankingAccount\Repository)->findByAccountNumberAndChannel($accountNumber, $channel);

        $merchant = $bankingAccount->merchant;

        $processor = $this->getProcessor($channel, $accountNumber);

        $accountStatementDetails = $processor->fetchAccountStatementDetails($input);

        $this->processAccountStatement($accountStatementDetails, $accountNumber, $merchant);

        return ['processed' => true];
    }

    /***
     * This would take in the following parameters
     * @param channel channel name
     * @param account_number
     * @param format
     * @return mixed
     *
     * Creates either a PDF/Excel File and returns the file handle to the calling function
     */
    public function generateBankAccountStatement($input)
    {
        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        $channel = $input[Entity::CHANNEL];

        $fromDate = $input[Entity::FROM_DATE];

        $toDate = $input[Entity::TO_DATE];

        $format = $input[Entity::FORMAT];

        $sendEmail = $input[Entity::SEND_EMAIL];

        $sendEmail = filter_var($sendEmail, FILTER_VALIDATE_BOOLEAN);

        $statementGenerator = $this->getGenerator($accountNumber, $channel, $format, $fromDate, $toDate);

        $statementFile      = $statementGenerator->getStatement();

        $fileURL            = (new Accessor())->getSignedUrlOfFile($statementFile);

        if ($sendEmail)
        {
            // code for sending this file via an email
            return ['message' => 'Email Sent'];
        }
        else
        {
            return ['message' => 'File Generated', 'file_path' => $fileURL];
        }
    }

    protected function getGenerator($accountNUmber, $channel, $format, $fromDate, $toDate)
    {
        $statementGeneratorNamespace = __NAMESPACE__ . '\\' . 'Generator\\Gateway\\' . studly_case($channel);

        $statementGenerator          = $statementGeneratorNamespace . '\\' . studly_case($format);

        return new $statementGenerator($accountNUmber, $channel, $fromDate, $toDate);
    }

    protected function getProcessor(string $channel, string $accountNumber): Processor\Base
    {
        $processor = __NAMESPACE__ . '\\' . 'Processor';

        $processor .= '\\' . studly_case($channel) . '\\' . 'Gateway';

        return new $processor($channel, $accountNumber);
    }

    protected function processAccountStatement(
        array $bankTransactions,
        string $accountNumber,
        Merchant\Entity $merchant)
    {
        $bankTxnCount = count($bankTransactions);
        $skippedCount = 0;

        foreach ($bankTransactions as $bankTransaction)
        {
            $bankTxnId      = $bankTransaction[Entity::BANK_TRANSACTION_ID];
            $bankTxnSrlNo   = $bankTransaction[Entity::BANK_SERIAL_NUMBER];
            $bankTxnDate    = $bankTransaction[Entity::TRANSACTION_DATE];
            $bankTxnChannel = $bankTransaction[Entity::CHANNEL];

            $txnExists = $this->repo->banking_account_statement->bankTransactionExists(
                $bankTxnId,
                $accountNumber,
                $bankTxnDate,
                $bankTxnChannel,
                $bankTxnSrlNo);

            if ($txnExists === true)
            {
                $skippedCount++;

                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_STATEMENT_INSERT_SKIP,
                    [
                        'bank_transaction_id'               => $bankTxnId,
                        'bank_transaction_serial_number'    => $bankTxnSrlNo,
                        'bank_transaction_date'             => $bankTxnDate,
                        'bank_transaction_channel'          => $bankTxnChannel,
                        'bank_account_number'               => $accountNumber,
                    ]);

                continue;
            }

            $this->saveAccountStatement($bankTransaction, $merchant);
        }

        $processedCount = $bankTxnCount - $skippedCount;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_SAVE_SUMMARY,
            [
                'total'     => $bankTxnCount,
                'skipped'   => $skippedCount,
                'processed' => $processedCount,
            ]);

        $this->checkAndTraceForStaleResponse($bankTxnCount, $processedCount, $accountNumber);
    }

    protected function saveAccountStatement(array $bankTransaction, Merchant\Entity $merchant)
    {
        $this->repo->transaction(function () use ($bankTransaction, $merchant) {

            $basEntity = (new Entity)->build($bankTransaction);

            $basEntity->merchant()->associate($merchant);

            $sourceEntity = $this->processSourceEntity($basEntity);

            $basEntity->source()->associate($sourceEntity);

            $basEntity->transaction()->associate($sourceEntity->transaction);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_SAVE, $basEntity->toArray());

            $this->repo->saveOrFail($basEntity);
        });
    }

    protected function getBalance(Entity $basEntity)
    {
        if (empty($this->balance) === true)
        {
            $this->balance = $this->repo
                                  ->balance
                                  ->getBalanceByMerchantIdAccountNumberAndChannelOrFail($basEntity->getMerchantId(),
                                                                                        $basEntity->getAccountNumber(),
                                                                                        $basEntity->getChannel());
        }

        return $this->balance;
    }

    protected function processSourceEntity(Entity $basEntity)
    {
        if ($basEntity->isTypeCredit() === true)
        {
            $sourceEntity = $this->processReversal($basEntity);
        }
        else
        {
            $sourceEntity = $this->processPayout($basEntity);
        }

        $this->validateBalance($basEntity, $sourceEntity);

        return $sourceEntity;
    }

    protected function processReversal(Entity $basEntity)
    {
        $reversal = $this->fetchExistingReversalIfPresent($basEntity);

        if ($reversal === null)
        {
            return $this->processExternal($basEntity);
        }

        //
        // TODO: Explore creating a reversal entity and its transaction here
        // if we are able to map the reversal BAS to a payout entity in the system.
        // Earlier, we had decided that we will not make any changes to any entity
        // as part of transaction flow. Also, this should be an edge case where we
        // haven't fetched the status yet, but we fetched the account statement.
        // But, this can also happen: when we fetched the status, it wasn't
        // reversed yet, but when we fetched the transaction, it was reversed.
        // But, for this reason, we decided to run the status check for 7 days
        // for processed payouts.
        // We can probably optimize for this later.
        //

        // TODO: Add a test case for this.
        $reversal = (new Reversal\Core)->createTransactionFromPayoutReversal($reversal);

        return $reversal;
    }

    protected function processPayout(Entity $basEntity)
    {
        $payout = $this->fetchExistingPayoutIfPresent($basEntity);

        if (($payout === null) or
            ($payout->isStatusFailed() === true))
        {
            return $this->processExternal($basEntity);
        }

        // TODO: Add a test case for this.
        (new DownstreamProcessor('fund_account_payout', $payout))->processTransaction();

        return $payout;
    }

    protected function processExternal(Entity $basEntity)
    {
        $external = (new External\Core)->create($basEntity);

        return $external;
    }

    protected function fetchExistingReversalIfPresent(Entity $basEntity)
    {
        $utr = $basEntity->getUtrFromDescription();

        $balance = $this->getBalance($basEntity);

        // TODO: Start storing UTR in reversals
        $reversal = $this->repo->reversal->fetchFromUtr($utr, $balance->getId())->first();

        return $reversal;
    }

    /**
     * TODO: The logic would be different based on the channel.
     * Refactor this when adding more banks here.
     *
     * @param Entity $basEntity
     *
     * @return mixed
     * @throws Exception\LogicException
     */
    protected function fetchExistingPayoutIfPresent(Entity $basEntity)
    {
        $payouts = new Base\Collection;

        $balance = $this->getBalance($basEntity);

        $utr = $basEntity->getUtrFromDescription();

        //
        // We first try to retrieve the payout from UTR, present in the description.
        //
        // In case of payouts
        // - IMPS is the most common mode
        // - UTR retrieval is supported only for IMPS.
        // - We do not know the mode via BAS entity. If we did, we could
        //   fetch using UTR or bank_transaction_id depending on the mode.
        // Due to the above two reasons, we try to fetch a payout using UTR first.
        //
        if (empty($utr) === false)
        {
            $payouts = $this->repo->payout->fetchFromUtr($utr, $balance->getId());
        }

        //
        // If either the UTR is not present in the description or if we were not able
        // to retrieve any payouts using the UTR, we try with bank_transaction_id
        //
        if ($payouts->count() === 0)
        {
            $bankTxnId = $basEntity->getBankTransactionId();

            $payouts = $this->repo->payout->fetchFromCmsRefNumber($bankTxnId, $balance->getId());
        }

        //
        // Finally, if the search with either UTR or with bank_transaction_id gave more
        // results than 1, it means our logic is wrong and needs to be re-looked at.
        //
        if ($payouts->count() > 1)
        {
            throw new Exception\LogicException(
                'Too many payouts found for the given criteria',
                ErrorCode::SERVER_ERROR_TOO_MANY_PAYOUTS_FOUND,
                [
                    'bas_id'        => $basEntity->getId(),
                    'balance_id'    => $balance->getId(),
                    'utr'           => $utr,
                    'count'         => $payouts->count()
                ]);
        }

        return $payouts->first();
    }

    protected function validateBalance(Entity $basEntity, Base\PublicEntity $sourceEntity)
    {
        $balanceCalculated = $sourceEntity->transaction->getBalance();

        $balanceAtBankSide = $basEntity->getBalance();

        if ($balanceCalculated !== $balanceAtBankSide)
        {
            throw new Exception\LogicException(
                'Balance at channel does not match with our balance',
                ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_STATEMENT_BALANCES_DO_NOT_MATCH,
                [
                    'rzp_balance'     => $balanceCalculated,
                    'channel_balance' => $balanceAtBankSide,
                    'account_number'  => $basEntity->getAccountNumber(),
                    'bank_txn_id'     => $basEntity->getBankTransactionId(),
                ]
            );
        }
    }

    protected function checkAndTraceForStaleResponse(int $bankTxnCount, int $processedCount, string $accountNumber)
    {
        //
        // Check for stale response from the channel,
        // This happens when we have stored the txns already,
        // but the bank is still sending us the same txns again
        // Since we are using the last txn for pagination, we
        // should never be receiving the same txns again
        //
        if (($bankTxnCount > 0) and ($processedCount === 0))
        {
            $this->trace->error(
                TraceCode::BANKING_ACCOUNT_STATEMENT_STALE_RESPONSE,
                [
                    'account_number'    => $accountNumber,
                    'total'             => $bankTxnCount,
                    'processed'         => $processedCount,
                ]);
        }
    }

}
