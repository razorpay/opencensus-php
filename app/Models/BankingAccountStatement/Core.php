<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\External;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Models\BankingAccount;
use RZP\Services\FTS\FundTransfer;
Use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;

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
        $channel        = array_pull($input, Entity::CHANNEL);
        $accountNumber  = array_pull($input, Entity::ACCOUNT_NUMBER);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST,
            [
                'channel'           => $channel,
                'account_number'    => $accountNumber,
            ]);

        $bankingAccount = (new BankingAccount\Repository)->findByAccountNumberAndChannel($accountNumber, $channel);

        $merchant = $bankingAccount->merchant;

        $processor = $this->getProcessor($channel, $accountNumber);

        $accountStatementDetails = $processor->fetchAccountStatementDetails($input);

        $this->processAccountStatement($accountStatementDetails, $accountNumber, $merchant);

        return ['processed' => true];
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

            //
            // This should be done after build since `setUtr` fetches things from the entity.
            // Can be refactored if required, as long as properly tested.
            //
            if (empty($basEntity->getUtr()) === true)
            {
                $basEntity->setUtr();
            }

            // currently will be setting this field for only NEFT.
            if (empty($basEntity->getPonum()) === true)
            {
                $basEntity->setPonum();
            }

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

        if ($sourceEntity === null)
        {
            $sourceEntity = $this->processExternal($basEntity);
        }

        $this->validateBalance($basEntity, $sourceEntity);

        return $sourceEntity;
    }

    protected function processReversal(Entity $basEntity)
    {
        $reversal = $this->fetchExistingReversalIfPresent($basEntity);

        if ($reversal === null)
        {
            // checking if we have any existing debit entry for this UTR
            $existingPayout = $this->fetchExistingPayoutIfPresent($basEntity);

            if ($existingPayout === null)
            {
                // For all modes except NEFT, the logic to map a credit row to payout is
                // shared. However for NEFT, PONUM is the only field out of all present
                // in the statement that matches with the payout. But the PONUM field returned
                // by FTS response is not being persisted anywhere as of now and adding columns in
                // FTA table is not feasible as we are trying to remove complete dependency from FTA
                // and move to FTS. Also persisting this information in Payout level is also not the
                // right way as this info is bank specific. So for NEFT in order to link a credit
                // txn to a debit txn we are matching its PONUM to any existing txn with same PONUM
                // The value of PONUM might not be unique and so we are explicitly checking for date
                // and amount also while finding the payout.

                // Once we have other CA Integration in picture, this code will need to be structured in a
                // manner that the based on a statement, logic to figure out a payout will be present
                // in the respective bank processors. Or this logic will completely moved out to FTS.

                $bankPonum = $basEntity->getPonum();

                $existingDebitTxn = $this->repo->banking_account_statement->findDebitTxnWithPonum(
                    $bankPonum,
                    $basEntity->getAmount(),
                    $basEntity->getTransactionDate(),
                    $basEntity->getChannel());

                if ($existingDebitTxn !== null)
                {
                    // getting the payout for this BAS entry
                    $payoutId = $existingDebitTxn->getEntityId();

                    $existingPayout = $this->repo->payout->findByIdAndMerchant($payoutId, $basEntity->merchant);
                }
            }

            if ($existingPayout !== null)
            {
                // since we are able to figure the payout linked to the credit txn
                // we want to update the payout to reversed state. However we will
                // not be updating the payout status here as we want to propogate
                // the changes to FTS first and keep only one entry point for
                // payout status updates.
                $fundTransferAttempt = $this->repo->fund_transfer_attempt->getFTSAttemptBySourceId(
                                                                                               $existingPayout->getId(),
                                                                                    Constants\Entity::PAYOUT,
                                                                                         true);

                if ($fundTransferAttempt !== null)
                {
                    $request = [
                        Attempt\Constants::GATEWAY_REF_NO   => $fundTransferAttempt->getFTSTransferId(),
                        Attempt\Constants::BANK_STATUS_CODE => Attempt\Status::FAILURE,
                        Payout\Entity::REMARKS              => 'Update through bank account statement',
                        Payout\Entity::RETURN_UTR           => $basEntity->getUtr(),
                        Attempt\Entity::CMS_REF_NO          => $basEntity->getBankTransactionId(),
                    ];

                    (new Payout\Core)->updateFtsWithSource($existingPayout, $request);
                }
            }

            return null;
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

        if ($payout === null)
        {
            return null;
        }

        // We are checking for $payout->isStatusFailed(), because its possible that due to a code-miss,
        // a 'reversed' payout is marked as a 'failed' payout, in which case we will get a 'failed' payout
        // matching a BAS, which should be impossible ideally, because a Failed payout, means no debit
        // ever happened. So to ensure that error case is handled we are checking for 'failed' payouts too
        if ($payout->isStatusFailed() === true)
        {
            $this->trace->error(
                TraceCode::BAS_ENTRY_FOR_A_FAILED_PAYOUT,
                 [
                     'bas_id'    => $basEntity->getId(),
                     'payout_id' => $payout->getId()
                 ]);

            return null;
        }

        (new DownstreamProcessor('fund_account_payout', $payout))->processTransaction();

        $this->repo->saveOrFail($payout);

        $fundTransferAttempt = $this->repo->fund_transfer_attempt->getFTSAttemptBySourceId($payout->getId(),
                                                                                Constants\Entity::PAYOUT,
                                                                                    true);

        if ($fundTransferAttempt !== null)
        {
            $request = [
                Attempt\Constants::GATEWAY_REF_NO   => $fundTransferAttempt->getFTSTransferId(),
                Attempt\Constants::BANK_STATUS_CODE => Attempt\Status::PROCESSED,
                Payout\Entity::REMARKS              => 'Update through bank account statement',
                Payout\Entity::UTR                  => $basEntity->getUtr(),
                Attempt\Entity::CMS_REF_NO          => $basEntity->getBankTransactionId(),
            ];

            (new Payout\Core)->updateFtsWithSource($payout, $request);
        }

        return $payout;
    }

    protected function processExternal(Entity $basEntity)
    {
        $external = (new External\Core)->create($basEntity);

        return $external;
    }

    protected function fetchExistingReversalIfPresent(Entity $basEntity)
    {
        $utr = $basEntity->getUtr();

        if (empty($utr) === true)
        {
            return null;
        }

        $balance = $this->getBalance($basEntity);

        $reversal = $this->repo
                         ->reversal
                         ->fetchFromUtr($utr, $basEntity->getAmount(), $balance->getId())
                         ->first();

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

        $utr = $basEntity->getUtr();

        //
        // We first try to retrieve the payout from UTR, present in the description.
        //
        // In case of payouts
        // - IMPS is the most common mode
        // - UTR retrieval is supported only for IMPS.
        // - CMS Ref no is used for RTGS and IFT
        // - PONUM is used for NEFT.
        // - We do not know the mode via BAS entity. If we did, we could
        //   fetch using UTR or bank_transaction_id depending on the mode.
        // Due to the above two reasons, we try to fetch a payout using UTR first.
        //
        if (empty($utr) === false)
        {
            $payouts = $this->repo->payout->fetchFromUtr($utr, $basEntity->getAmount(), $balance->getId());
        }

        //
        // If either the UTR is not present in the description or if we were not able
        // to retrieve any payouts using the UTR, we try with bank_transaction_id
        //
        if ($payouts->count() === 0)
        {
            $bankTxnId = $basEntity->getBankTransactionId();

            $payouts = $this->repo->payout->fetchFromCmsRefNumber($bankTxnId,
                                                                  $basEntity->getAmount(),
                                                                  $balance->getId());
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
