<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\External;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Models\BankingAccount;
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
        $skippedCount = 0;

        foreach ($bankTransactions as $bankTransaction)
        {
            $bankTxnId = $bankTransaction[Entity::BANK_TRANSACTION_ID];

            $txnExists = $this->repo->banking_account_statement->bankTransactionExists($bankTxnId, $accountNumber);

            if ($txnExists === true)
            {
                $skippedCount++;

                continue;
            }

            $this->saveAccountStatement($bankTransaction, $merchant);
        }

        $processedCount = count($bankTransactions) - $skippedCount;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_SAVE_SUMMARY,
            [
                'total'     => count($bankTransactions),
                'skipped'   => $skippedCount,
                'processed' => $processedCount,
            ]);
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

        if ($payout === null)
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

        $reversal = $this->repo->reversal->fetchFromUtr($utr, $balance->getId())->first();

        return $reversal;
    }

    protected function fetchExistingPayoutIfPresent(Entity $basEntity)
    {
        $utr = $basEntity->getUtrFromDescription();

        $balance = $this->getBalance($basEntity);

        // TODO: Fix this logic to fetch payouts.
        $payout = $this->repo->payout->fetchFromUtr($utr, $balance->getId())->first();

        return $payout;
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
}
