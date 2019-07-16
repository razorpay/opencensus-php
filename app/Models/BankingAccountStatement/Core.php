<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\External;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as C;
use RZP\Models\BankingAccount;

class Core extends Base\Core
{
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
            $bankTxnId = $bankTransaction[Entity::BANK_TRANSACTION_ID];

            $txnExists = $this->repo->banking_account_statement->bankTransactionExists($bankTxnId, $accountNumber);

            if ($txnExists === true)
            {
                $skippedCount++;

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

            $type = $this->getBankTransactionType($basEntity);

            $sourceEntity = $this->createSourceEntity($basEntity, $type);

            $basEntity->source()->associate($sourceEntity);

            $basEntity->transaction()->associate($sourceEntity->transaction);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_SAVE, $basEntity->toArray());

            $this->repo->saveOrFail($basEntity);
        });
    }

    protected function getBankTransactionType(Entity $basEntity)
    {
        return C::EXTERNAL;
    }

    protected function createSourceEntity(Entity $basEntity, string $type)
    {
        switch ($type) {
            case C::EXTERNAL:
                $sourceEntity = (new External\Core)->create($basEntity);
                break;
            case C::PAYOUT:
            case C::REVERSAL:
            default:
                throw new Exception\LogicException(
                    "Invalid txn type found as $type",
                    ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_STATEMENT_INVALID_TYPE_FOUND,
                    [
                        'type'   => $type,
                        'bas_id' => $basEntity->getId(),
                    ]);
        }

        $this->validateBalance($basEntity, $sourceEntity);

        return $sourceEntity;
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
