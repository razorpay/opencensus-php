<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl;

use View;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Bank\BankInfo;
use RZP\Models\Currency\Currency;
use RZP\Models\BankingAccountStatement\Type;
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

        $all_bank_account_transactions = $this->repo
                                               ->banking_account_statement
                                               ->findByAccountNumberWithInPeriod($this->accountNumber,
                                                                                 $this->fromDate,
                                                                                 $this->toDate);

        $merchant = $bankingAccount->merchant;

        $merchantDetails = $merchant->merchantDetail;

        $account_opening_date = Carbon::createFromTimestamp($bankingAccount->account_activation_date, Timezone::IST)
                                      ->format(self::DATE_FORMAT);

        $fromDateReadable = Carbon::createFromTimestamp($this->fromDate, Timezone::IST)
                                  ->format(self::DATE_FORMAT);

        $toDateReadable   = Carbon::createFromTimestamp($this->toDate, Timezone::IST)
                                  ->format(self::DATE_FORMAT);

        $statementPeriod  = $fromDateReadable . ' - ' . $toDateReadable;

        $accountOwnerInfo = $this->getAccountOwnerInfo($merchant,
                                                       $merchantDetails,
                                                       $bankingAccount,
                                                       $account_opening_date,
                                                       $statementPeriod);

        $transactions     = $this->serializeTransactions($all_bank_account_transactions);

        $statementSummary = $this->getAccountStatementSummary($all_bank_account_transactions);

        return [
            AccountStatementData::ACCOUNT_OWNER_INFO => $accountOwnerInfo,

            AccountStatementData::TRANSACTIONS       => $transactions,

            AccountStatementData::STATEMENT_SUMMARY  => $statementSummary
        ];
    }

    protected function getAccountStatementSummary($bank_account_statements)
    {
        $opening_balance   = 0;

        $closing_balance   = 0;

        $effective_balance = 0;

        $lien_amount       = 0;

        $debit_count       = 0;

        $credit_count      = 0;

        if ($bank_account_statements->count())
        {
            $opening_balance   = $bank_account_statements[0]->balance;

            $closing_balance   = $bank_account_statements[count($bank_account_statements) - 1]->balance;

            $effective_balance = $closing_balance;

            $lien_amount       = 0;

            $debit_count       = 0;

            $credit_count      = 0;

            foreach ($bank_account_statements as $transaction)
            {
                if ($transaction->type == StatementType::CREDIT)
                {
                    $credit_count++;
                }
                else if ($transaction->type == StatementType::DEBIT)
                {
                    $debit_count++;
                }
            }
        }

        $statementGeneratedDate = Carbon::createFromTimestamp(time(), Timezone::IST)
                                          ->format(StatementSummary::STATEMENT_GENERATED_DATE_FORMAT);

        return [
            StatementSummary::OPENING_BALANCE          => (float) $opening_balance / 100,

            StatementSummary::CLOSING_BALANCE          => (float) $closing_balance / 100,

            StatementSummary::EFFECTIVE_BALANCE        => (float) $effective_balance / 100,

            StatementSummary::LIEN_AMOUNT              => (float) $lien_amount / 100,

            StatementSummary::DEBIT_COUNT              => (float) $debit_count,

            StatementSummary::CREDIT_COUNT             => $credit_count,

            StatementSummary::STATEMENT_GENERATED_DATE => $statementGeneratedDate
        ];
    }

    protected function serializeTransactions($bank_account_statements)
    {
        $transactions = [];

        foreach ($bank_account_statements as $transaction)
        {
            $lineItem = [
                TransactionLineItem::TRANSACTION_DATE    => Carbon::createFromTimestamp($transaction->transaction_date,
                                                                                        Timezone::IST)
                                                                    ->format(TransactionLineItem::ITEM_DATE_FORMAT),

                TransactionLineItem::TRANSACTION_DETAILS => $transaction->description,

                TransactionLineItem::CHEQUE_ID           => $transaction->bank_instrument_id,

                TransactionLineItem::VALUE_DATE          => Carbon::createFromTimestamp($transaction->transaction_date,
                                                                                        Timezone::IST)
                                                                    ->format(TransactionLineItem::ITEM_DATE_FORMAT),

                TransactionLineItem::BALANCE             => (float) $transaction->balance / 100
            ];

            if ($transaction->type == Type::CREDIT)
            {
                $lineItem[TransactionLineItem::WITHDRAWAL_AMOUNT] = (float) $transaction->amount / 100;

                $lineItem[TransactionLineItem::DEPOSIT_AMOUNT]    = null;
            }
            else if ($transaction->type == Type::DEBIT)
            {
                $lineItem[TransactionLineItem::DEPOSIT_AMOUNT]    = (float) $transaction->amount / 100;

                $lineItem[TransactionLineItem::WITHDRAWAL_AMOUNT] = null;
            }

            array_push($transactions, $lineItem);
        }

        return $transactions;
    }

    /**
     * @param $merchant
     * @param $merchantDetails
     * @param $bankingAccount
     * @param string $account_opening_date
     * @param string $statementPeriod
     * @return array
     */
    protected function getAccountOwnerInfo($merchant,
                                           $merchantDetails,
                                           $bankingAccount,
                                           string $account_opening_date,
                                           string $statementPeriod): array
    {
        $ifscCode = $bankingAccount->getAccountIfsc();

        $bankInformation = (new BankInfo($ifscCode))->getBankInformation();

        $accountOwnerInfo = [
            AccountOwnerInfo::ACCOUNT_NAME         => $merchant->name,

            AccountOwnerInfo::CUSTOMER_ADDRESS     => $merchantDetails->business_operation_address,

            AccountOwnerInfo::CUSTOMER_ADDRESS_L2  => $merchantDetails->business_registered_address_l2,

            AccountOwnerInfo::ACCOUNT_TYPE         => $bankingAccount->account_type,

            AccountOwnerInfo::ACCOUNT_STATUS       => $bankingAccount->status,

            AccountOwnerInfo::ACCOUNT_NUMBER       => $bankingAccount->account_number,

            AccountOwnerInfo::STATEMENT_PERIOD     => $statementPeriod,

            AccountOwnerInfo::SANCTION_LIMIT       => BankConstants::SANCTION_LIMIT,

            AccountOwnerInfo::DRAWING_POWER        => BankConstants::DRAWING_POWER,

            AccountOwnerInfo::BRANCH_TIMINGS       => BankConstants::BRANCH_TIMINGS,

            AccountOwnerInfo::CALL_CENTER          => BankConstants::CALL_CENTER_NUMBER,

            AccountOwnerInfo::CUSTOMER_CITY        => $merchantDetails->business_operation_city,

            AccountOwnerInfo::CUSTOMER_STATE       => $merchantDetails->business_operation_state,

            AccountOwnerInfo::CUSTOMER_ADDRESS_PIN => $merchantDetails->business_operation_pin,

            AccountOwnerInfo::CUSTOMER_MOBILE      => $merchantDetails->contact_mobile,

            AccountOwnerInfo::CUSTOMER_EMAIL       => $merchantDetails->contact_email,

            AccountOwnerInfo::CUSTOMER_CIF_ID      => $bankingAccount->bank_internal_reference_number,

            AccountOwnerInfo::CURRENCY             => Currency::INR,

            AccountOwnerInfo::ACCOUNT_OPENING_DATE => $account_opening_date,

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
