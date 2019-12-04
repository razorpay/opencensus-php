<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl;

use View;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Bank\BankInfo;
use RZP\Models\Currency\Currency;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Base;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl\Constants\{Statement,
                                                                        AccountOwnerInfo,
                                                                        StatementSummary,
                                                                        TransactionLineItem,
                                                                        AccountStatementData};

abstract class Generator extends Base
{
    const TEMP_STORAGE_DIR = '/tmp/';

    /**
     * This will be set during class initialization, and will be available to all the Child Classes
     *
     * @var array|null
     */
    protected $data = null;

    const DATE_FORMAT = 'd/m/Y';

    public function __construct(string $accountNumber, string $channel, int $fromDate, int $toDate)
    {
        parent::__construct($accountNumber, $channel, $fromDate, $toDate);

        $this->data = $this->accountStatementData();
    }

    protected function generateFileName(string $format)
    {
        return $this->accountNumber . '_' . $this->fromDate . '_' . $this->toDate . '.' . $format;
    }

    protected function accountStatementData()
    {
        $bankingAccount = $this->repo
                               ->banking_account
                               ->findByAccountNumberAndChannelPublic($this->accountNumber, $this->channel);

        $accountOwnerInfo = $this->getAccountOwnerInfo($bankingAccount);

        list($statementSummary, $transactions) = $this->getAccountSummaryAndTransactions($bankingAccount);

        return [
            AccountStatementData::ACCOUNT_OWNER_INFO => $accountOwnerInfo,

            AccountStatementData::TRANSACTIONS       => $transactions,

            AccountStatementData::STATEMENT_SUMMARY  => $statementSummary
        ];
    }

    protected function getAccountSummaryAndTransactions(BankingAccountEntity $bankingAccount)
    {
        $balanceId = $this->repo
                          ->balance
                          ->getBalanceIdByAccountNumberOrFail($bankingAccount->getAccountNumber());

        $bankAccountStatements = $this->repo
                                      ->statement
                                      ->getStatementsInRange($bankingAccount->getMerchantId(),
                                                             $balanceId,
                                                             $this->fromDate,
                                                             $this->toDate);

        $transactions = [];

        $openingBalance = 0;

        $closingBalance = 0;

        $effectiveBalance = 0;

        $lienAmount = 0;

        $debitCount = 0;

        $creditCount = 0;

        if ($bankAccountStatements->count() !== 0)
        {
            $openingBalance = $bankAccountStatements->last()->getBalance();

            $closingBalance = $bankAccountStatements->first()->getBalance();

            $effectiveBalance = $closingBalance;

            $lienAmount = 0;

            $debitCount = 0;

            $creditCount = 0;

            foreach ($bankAccountStatements as $transaction)
            {
                $lineItem = $this->convertToLineItem($transaction);

                array_push($transactions, $lineItem);

                if ($transaction->isCredit() === true)
                {
                    $creditCount++;
                }
                else if ($transaction->isDebit() === true)
                {
                    $debitCount++;
                }
            }
        }

        $statementGeneratedDate = Carbon::createFromTimestamp(Carbon::now()->getTimestamp(), Timezone::IST)
                                        ->format(StatementSummary::STATEMENT_GENERATED_DATE_FORMAT);

        $statementSummary = [
            StatementSummary::OPENING_BALANCE          => (float) $openingBalance / 100,

            StatementSummary::CLOSING_BALANCE          => (float) $closingBalance / 100,

            StatementSummary::EFFECTIVE_BALANCE        => (float) $effectiveBalance / 100,

            StatementSummary::LIEN_AMOUNT              => (float) $lienAmount / 100,

            StatementSummary::DEBIT_COUNT              => (float) $debitCount,

            StatementSummary::CREDIT_COUNT             => $creditCount,

            StatementSummary::STATEMENT_GENERATED_DATE => $statementGeneratedDate
        ];

        $response = [$statementSummary, $transactions];

        return $response;
    }

    protected function convertToLineItem(TransactionEntity $transaction)
    {
        $formattedTransactionDate = Carbon::createFromTimestamp($transaction->getCreatedAt(), Timezone::IST)
                                          ->format(TransactionLineItem::ITEM_DATE_FORMAT);

        $lineItem = [
            TransactionLineItem::TRANSACTION_DATE    => $formattedTransactionDate,

            TransactionLineItem::TRANSACTION_DETAILS => $transaction->getAttribute('description'),

            TransactionLineItem::CHEQUE_ID           => '',

            TransactionLineItem::VALUE_DATE          => $formattedTransactionDate,

            TransactionLineItem::BALANCE             => (float) $transaction->getBalance() / 100
        ];

        $transactionAmount = (float) $transaction->getAmount() / 100;

        if ($transaction->isDebit() === true)
        {
            $lineItem[TransactionLineItem::WITHDRAWAL_AMOUNT] = $transactionAmount;

            $lineItem[TransactionLineItem::DEPOSIT_AMOUNT] = null;
        }
        else if ($transaction->isCredit() === true)
        {
            $lineItem[TransactionLineItem::DEPOSIT_AMOUNT] = $transactionAmount;

            $lineItem[TransactionLineItem::WITHDRAWAL_AMOUNT] = null;
        }

        return $lineItem;
    }

    /**
     * @param BankingAccountEntity $bankingAccount
     * @return array
     */
    protected function getAccountOwnerInfo(BankingAccountEntity $bankingAccount): array
    {
        $accountOpeningDate = Carbon::createFromTimestamp($bankingAccount->getAccountActivationDate(), Timezone::IST)
                                    ->format(self::DATE_FORMAT);

        $fromDate = Carbon::createFromTimestamp($this->fromDate, Timezone::IST)
                          ->format(self::DATE_FORMAT);

        $toDate = Carbon::createFromTimestamp($this->toDate, Timezone::IST)
                        ->format(self::DATE_FORMAT);

        $statementPeriod = $fromDate . ' - ' . $toDate;

        $ifscCode = $bankingAccount->getAccountIfsc();

        $bankInformation = (new BankInfo)->getBankInformation($ifscCode);

        $customerAddressL2 =  $bankingAccount->getBeneficiaryAddress2() .
                              ', ' .
                              $bankingAccount->getBeneficiaryAddress3();

        $accountOwnerInfo = [
            AccountOwnerInfo::ACCOUNT_NAME         => $bankingAccount->getBeneficiaryName(),

            AccountOwnerInfo::CUSTOMER_ADDRESS     => $bankingAccount->getBeneficiaryAddress1(),

            AccountOwnerInfo::CUSTOMER_ADDRESS_L2  => $customerAddressL2,

            AccountOwnerInfo::ACCOUNT_TYPE         => $bankingAccount->getAccountType(),

            AccountOwnerInfo::ACCOUNT_STATUS       => $bankingAccount->getStatus(),

            AccountOwnerInfo::ACCOUNT_NUMBER       => $bankingAccount->getAccountNumber(),

            AccountOwnerInfo::STATEMENT_PERIOD     => $statementPeriod,

            AccountOwnerInfo::SANCTION_LIMIT       => Statement::SANCTION_LIMIT,

            AccountOwnerInfo::DRAWING_POWER        => Statement::DRAWING_POWER,

            AccountOwnerInfo::BRANCH_TIMINGS       => Statement::BRANCH_TIMINGS,

            AccountOwnerInfo::CALL_CENTER          => Statement::CALL_CENTER_NUMBER,

            AccountOwnerInfo::CUSTOMER_CITY        => $bankingAccount->getBeneficiaryCity(),

            AccountOwnerInfo::CUSTOMER_STATE       => $bankingAccount->getBeneficiaryState(),

            AccountOwnerInfo::CUSTOMER_ADDRESS_PIN => $bankingAccount->getBeneficiaryPin(),

            AccountOwnerInfo::CUSTOMER_MOBILE      => $bankingAccount->getBeneficiaryMobile(),

            AccountOwnerInfo::CUSTOMER_EMAIL       => $bankingAccount->getBeneficiaryEmail(),

            AccountOwnerInfo::CUSTOMER_CIF_ID      => $bankingAccount->getInternalReferenceNumber(),

            AccountOwnerInfo::CURRENCY             => Currency::INR,

            AccountOwnerInfo::ACCOUNT_OPENING_DATE => $accountOpeningDate,

            AccountOwnerInfo::HOME_BRANCH_NAME     => $bankInformation->getBankName(),

            AccountOwnerInfo::HOME_BRANCH_ADDRESS  => $bankInformation->address,

            AccountOwnerInfo::IFSC_CODE            => $bankInformation->ifsc,

            AccountOwnerInfo::BRANCH_PHONE_NUMBER  => $bankInformation->contact,

            AccountOwnerInfo::BRANCH_CITY          => $bankInformation->city,

            AccountOwnerInfo::BRANCH_STATE         => $bankInformation->state,
        ];

        return $accountOwnerInfo;
    }
}
