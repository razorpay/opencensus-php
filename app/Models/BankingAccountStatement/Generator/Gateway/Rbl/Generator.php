<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl;

use View;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Bank\BankInfo;
use RZP\Models\Currency\Currency;
use RZP\Models\BankingAccountStatement\Type;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;
use RZP\Models\BankingAccountStatement\Type as StatementType;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Base;

abstract class Generator extends Base
{
    abstract function getStatement();

    # This will be set during class initialization, and will be available to all the Child Classes
    protected $data = null;

    const DATE_FORMAT = 'd/m/Y';

    public function __construct($accountNumber, $channel, $fromDate, $toDate)
    {
        parent::__construct($accountNumber, $channel, $fromDate, $toDate);

        $this->data = $this->accountStatementData();
    }

    protected function accountStatementData()
    {
        $bankingAccount = $this->repo
                               ->banking_account
                               ->findByAccountNumberAndChannel($this->accountNumber, $this->channel);

        $allBankAccountTransactions = $this->repo
                                           ->banking_account_statement
                                           ->findByAccountNumberWithInPeriod($this->accountNumber,
                                                                             $this->fromDate,
                                                                             $this->toDate);

        $accountOpeningDate = Carbon::createFromTimestamp($bankingAccount->getAccountActivationDate(),
                                                         Timezone::IST)
                                                          ->format(self::DATE_FORMAT);

        $fromDate = Carbon::createFromTimestamp($this->fromDate,
                                                       Timezone::IST)
                                                        ->format(self::DATE_FORMAT);

        $toDate = Carbon::createFromTimestamp($this->toDate,
                                                     Timezone::IST)
                                                      ->format(self::DATE_FORMAT);

        $statementPeriod = $fromDate . ' - ' . $toDate;

        $accountOwnerInfo = $this->getAccountOwnerInfo($bankingAccount, $accountOpeningDate, $statementPeriod);

        list($statementSummary, $transactions) = $this->getAccountSummaryAndTransactions($allBankAccountTransactions);

        return [
            AccountStatementData::ACCOUNT_OWNER_INFO => $accountOwnerInfo,

            AccountStatementData::TRANSACTIONS       => $transactions,

            AccountStatementData::STATEMENT_SUMMARY  => $statementSummary
        ];
    }

    protected function getAccountSummaryAndTransactions($bankAccountStatements)
    {
        $transactions = [];

        $openingBalance = 0;

        $closingBalance = 0;

        $effectiveBalance = 0;

        $lienAmount = 0;

        $debitCount = 0;

        $creditCount = 0;

        if ($bankAccountStatements->count() !== 0)
        {
            $openingBalance = $bankAccountStatements[0]->balance;

            $closingBalance = $bankAccountStatements[count($bankAccountStatements) - 1]->balance;

            $effectiveBalance = $closingBalance;

            $lienAmount = 0;

            $debitCount = 0;

            $creditCount = 0;

            foreach ($bankAccountStatements as $transaction)
            {
                $lineItem = $this->convertToLineItem($transaction);

                array_push($transactions, $lineItem);

                if ($transaction->type == StatementType::CREDIT)
                {
                    $creditCount++;
                }
                else if ($transaction->type == StatementType::DEBIT)
                {
                    $debitCount++;
                }
            }
        }

        $statementGeneratedDate = Carbon::createFromTimestamp(time(), Timezone::IST)
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

    protected function convertToLineItem($transaction)
    {
        $formattedTransactionDate = Carbon::createFromTimestamp($transaction->transaction_date, Timezone::IST)
                                            ->format(TransactionLineItem::ITEM_DATE_FORMAT);
        $lineItem = [
            TransactionLineItem::TRANSACTION_DATE    => $formattedTransactionDate,

            TransactionLineItem::TRANSACTION_DETAILS => $transaction->description,

            TransactionLineItem::CHEQUE_ID           => $transaction->bank_instrument_id,

            TransactionLineItem::VALUE_DATE          => $formattedTransactionDate,

            TransactionLineItem::BALANCE             => (float) $transaction->balance / 100
        ];

        if ($transaction->type == Type::CREDIT)
        {
            $lineItem[TransactionLineItem::WITHDRAWAL_AMOUNT] = (float) $transaction->amount / 100;

            $lineItem[TransactionLineItem::DEPOSIT_AMOUNT] = null;
        }
        else if ($transaction->type == Type::DEBIT)
        {
            $lineItem[TransactionLineItem::DEPOSIT_AMOUNT] = (float) $transaction->amount / 100;

            $lineItem[TransactionLineItem::WITHDRAWAL_AMOUNT] = null;
        }

        return $lineItem;
    }

    /**
     * @param BankingAccountEntity $bankingAccount
     * @param string $accountOpeningDate
     * @param string $statementPeriod
     * @return array
     */
    protected function getAccountOwnerInfo(BankingAccountEntity $bankingAccount,
                                           string $accountOpeningDate,
                                           string $statementPeriod): array
    {
        $ifscCode = $bankingAccount->getAccountIfsc();

        $bankInformation = (new BankInfo($ifscCode))->getBankInformation();

        $accountOwnerInfo = [
            AccountOwnerInfo::ACCOUNT_NAME         => $bankingAccount->getBeneficiaryName(),

            AccountOwnerInfo::CUSTOMER_ADDRESS     => $bankingAccount->getBeneficiaryAddress1(),

            AccountOwnerInfo::CUSTOMER_ADDRESS_L2  => $bankingAccount->getBeneficiaryAddress2(),

            AccountOwnerInfo::ACCOUNT_TYPE         => $bankingAccount->getAccountType(),

            AccountOwnerInfo::ACCOUNT_STATUS       => $bankingAccount->getStatus(),

            AccountOwnerInfo::ACCOUNT_NUMBER       => $bankingAccount->getAccountNumber(),

            AccountOwnerInfo::STATEMENT_PERIOD     => $statementPeriod,

            AccountOwnerInfo::SANCTION_LIMIT       => BankConstants::SANCTION_LIMIT,

            AccountOwnerInfo::DRAWING_POWER        => BankConstants::DRAWING_POWER,

            AccountOwnerInfo::BRANCH_TIMINGS       => BankConstants::BRANCH_TIMINGS,

            AccountOwnerInfo::CALL_CENTER          => BankConstants::CALL_CENTER_NUMBER,

            AccountOwnerInfo::CUSTOMER_CITY        => $bankingAccount->getBeneficiaryCity(),

            AccountOwnerInfo::CUSTOMER_STATE       => $bankingAccount->getBeneficiaryState(),

            AccountOwnerInfo::CUSTOMER_ADDRESS_PIN => $bankingAccount->getBeneficiaryPin(),

            AccountOwnerInfo::CUSTOMER_MOBILE      => $bankingAccount->getBeneficiaryMobile(),

            AccountOwnerInfo::CUSTOMER_EMAIL       => $bankingAccount->getBeneficiaryEmail(),

            AccountOwnerInfo::CUSTOMER_CIF_ID      => $bankingAccount->getInternalReferenceNumber(),

            AccountOwnerInfo::CURRENCY             => Currency::INR,

            AccountOwnerInfo::ACCOUNT_OPENING_DATE => $accountOpeningDate,

            AccountOwnerInfo::HOME_BRANCH_NAME     => $bankInformation->getBankName(),

            AccountOwnerInfo::HOME_BRANCH_ADDRESS  => $bankInformation->__get('address'),

            AccountOwnerInfo::IFSC_CODE            => $bankInformation->__get('ifsc'),

            AccountOwnerInfo::BRANCH_PHONE_NUMBER  => $bankInformation->__get('contact'),

            AccountOwnerInfo::BRANCH_CITY          => $bankInformation->__get('city'),

            AccountOwnerInfo::BRANCH_STATE         => $bankInformation->__get('state'),
        ];

        return $accountOwnerInfo;
    }
}
