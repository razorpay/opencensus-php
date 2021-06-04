<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Icici;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;
use RZP\Services\BankingAccountService;
use RZP\Models\BankingAccountStatement\Entity as basEntity;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Base;
use RZP\Models\Transaction\Statement\Entity as StatementEntity;
use RZP\Models\BankingAccountStatement\Details\Entity as basDetailsEntity;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants\StatementSummary;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants\AccountOwnerInfo;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants\TransactionLineItem;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants\BusinessDetailsInfo;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants\AccountStatementData;

abstract class Generator extends Base
{
    const TEMP_STORAGE_DIR = '/tmp/';

    /**
     * This will be set during class initialization, and will be available to all the Child Classes
     *
     * @var array|null
     */
    protected $data = null;

    const DATE_FORMAT = 'Y/m/d';

    const CR = 'CR';

    const DR = 'DR';

    public function __construct(string $accountNumber, string $channel, int $fromDate, int $toDate)
    {
        parent::__construct($accountNumber, $channel, $fromDate, $toDate);

        $this->data = $this->accountStatementData();
    }

    protected function getAccountOwnerInfo(basDetailsEntity $basDetails): array
    {
        /** @var BankingAccountService|\RZP\Services\Mock\BankingAccountService $bas */
        $bas = app('banking_account_service');

        $fromDate = Carbon::createFromTimestamp($this->fromDate, Timezone::IST)
                          ->format(self::DATE_FORMAT);

        $toDate = Carbon::createFromTimestamp($this->toDate, Timezone::IST)
                        ->format(self::DATE_FORMAT);

        $fromDate =  str_replace('/', '-', $fromDate);

        $toDate =  str_replace('/', '-', $toDate);

        $statementPeriod = $fromDate . ' to ' . $toDate;

        $businessDetails = $bas->getBusinessDetails($basDetails->getMerchantId());

        $accountOwnerInfo = [
            AccountOwnerInfo::ACCOUNT_NAME         => $this->getAccountName($businessDetails),

            AccountOwnerInfo::ACCOUNT_NUMBER       => $basDetails->getAccountNumber(),

            AccountOwnerInfo::STATEMENT_PERIOD     => $statementPeriod,

            AccountOwnerInfo::CURRENCY             => Currency::INR,
        ];

        return $accountOwnerInfo;
    }

    public function getAccountName($businessDetails)
    {
        return $businessDetails[BusinessDetailsInfo::NAME];
    }

    protected function accountStatementData()
    {
        $basDetails = $this->repo->banking_account_statement_details->fetchByAccountNumberAndChannel($this->accountNumber, $this->channel);

        $accountOwnerInfo = $this->getAccountOwnerInfo($basDetails);

        list($statementSummary, $transactions) = $this->getAccountSummaryAndTransactions($basDetails);

        return [
            AccountStatementData::ACCOUNT_OWNER_INFO => $accountOwnerInfo,

            AccountStatementData::TRANSACTIONS       => $transactions,

            AccountStatementData::STATEMENT_SUMMARY  => $statementSummary
        ];
    }

    protected function getAccountSummaryAndTransactions(basDetailsEntity $basDetails)
    {
        $balanceId = $this->repo->balance
                          ->getBalanceIdByAccountNumberOrFail($basDetails->getAccountNumber());

        $bankAccountStatements = $this->repo
                                      ->statement
                                      ->getStatementsInRange($basDetails->getMerchantId(),
                                                             $balanceId,
                                                             $this->fromDate,
                                                             $this->toDate);

        $transactions = [];

        $openingBalance = 0;

        $closingBalance = 0;

        $effectiveBalance = 0;

        $debitCount = 0;

        $creditCount = 0;

        if ($bankAccountStatements->count() !== 0)
        {
            $openingBalance = $bankAccountStatements->last()->getBalance();

            $closingBalance = $bankAccountStatements->first()->getBalance();

            $effectiveBalance = $closingBalance;

            $debitCount = 0;

            $creditCount = 0;

            $index = 1;

            foreach ($bankAccountStatements as $statement)
            {
                $lineItem = $this->convertToLineItem($statement, $index);

                array_push($transactions, $lineItem);

                if ($statement->isCredit() === true)
                {
                    $creditCount++;
                }
                else if ($statement->isDebit() === true)
                {
                    $debitCount++;
                }

                $index++;
            }
        }

        $statementGeneratedDate = Carbon::createFromTimestamp($this->getLastStatementAttemptAt($basDetails), Timezone::IST)
                                        ->format(StatementSummary::STATEMENT_GENERATED_DATE_FORMAT);

        $statementSummary = [
            StatementSummary::OPENING_BALANCE          => $this->getFormattedAmount($openingBalance),
            StatementSummary::CLOSING_BALANCE          => $this->getFormattedAmount($closingBalance),
            StatementSummary::EFFECTIVE_BALANCE        => $this->getFormattedAmount($effectiveBalance),
            StatementSummary::DEBIT_COUNT              => $debitCount,
            StatementSummary::CREDIT_COUNT             => $creditCount,
            StatementSummary::STATEMENT_GENERATED_DATE => $statementGeneratedDate
        ];

        $response = [$statementSummary, $transactions];

        return $response;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2);
    }

    protected function convertToLineItem(StatementEntity $statement, int $index)
    {
        /**
         * @var basEntity $bas;
         */
        $bas = $statement->bankingAccountStatement;

        $description = $this->extractDescription($bas);

        $transactionId = $this->getTransactionId($bas);

        $valueDate = $this->getValueDate($statement, $bas);

        $transactionPostedDate = $this->getTransactionPostedDate($statement, $bas);
        // Banking Account statement transaction date is source of truth in case of current Account
        // Current Account Statement fetch has delay in the system so transaction created in X can have
        // delay in comparision to transaction on RBL.
        $formattedValueDate = Carbon::createFromTimestamp($valueDate, Timezone::IST)
                                          ->format(TransactionLineItem::ITEM_DATE_FORMAT);

        $formattedPostedDate = Carbon::createFromTimestamp($transactionPostedDate, Timezone::IST)
                                          ->format(TransactionLineItem::POSTED_DATE_FORMAT);

        $lineItem = [
            TransactionLineItem::NO                      => $index,
            TransactionLineItem::TRANSACTION_ID          => $transactionId,
            TransactionLineItem::VALUE_DATE              => $formattedValueDate,
            TransactionLineItem::TRANSACTION_POSTED_DATE => $formattedPostedDate,
            TransactionLineItem::CHEQUE_NO               => '',
            TransactionLineItem::DESCRIPTION             => $description,
            TransactionLineItem::AVAILABLE_BALANCE       => $this->getFormattedAmount($statement->getBalance()),
        ];

        $transactionAmount = $this->getFormattedAmount($statement->getAmount());

        if ($statement->isDebit() === true)
        {
            $lineItem[TransactionLineItem::CR_DR] = self::DR;

            $lineItem[TransactionLineItem::TRANSACTION_AMOUNT] = $transactionAmount;
        }
        else if ($statement->isCredit() === true)
        {
            $lineItem[TransactionLineItem::CR_DR] = self::CR;

            $lineItem[TransactionLineItem::TRANSACTION_AMOUNT] = $transactionAmount;
        }

        return $lineItem;
    }

    protected function extractDescription($bas)
    {
        if ($bas !== null)
        {
            return $bas->getDescription();
        }

        return null;
    }

    protected function getTransactionId($bas)
    {
        if ($bas !== null)
        {
            return $bas->getBankTransactionId();
        }

        return null;
    }

    protected function getValueDate(StatementEntity $statement, $bas)
    {
        if ($bas !== null)
        {
            return $bas->getTransactionDate();
        }

        return $statement->getPostedDate() ? $statement->getPostedDate() : $statement->getCreatedAt();
    }

    protected function getTransactionPostedDate(StatementEntity $statement, $bas)
    {
        if ($bas !== null)
        {
            return $bas->getPostedDate();
        }

        return $statement->getPostedDate() ? $statement->getPostedDate() : $statement->getCreatedAt();
    }

    public function getLastStatementAttemptAt($basDetails)
    {
        $lastUpdatedAt = $basDetails->getLastStatementAttemptAt();

        $lastUpdatedAt = $lastUpdatedAt ? $lastUpdatedAt : Carbon::now()->getTimestamp();

        return $lastUpdatedAt;
    }

    protected function generateFileName(string $format)
    {
        return $this->accountNumber . '_' . $this->fromDate . '_' . $this->toDate . '.' . $format;
    }
}
