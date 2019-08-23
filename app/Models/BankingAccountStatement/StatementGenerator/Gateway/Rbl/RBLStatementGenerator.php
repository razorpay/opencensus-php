<?php


namespace RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Rbl;

use RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Base;
use RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Rbl\Constants as RBLBankConstants;
use RZP\Models\BankingAccountStatement\Type as StatementType;
use View;

class RBLStatementGenerator extends Base
{
    protected const TEMPLATE_FILE_NAME = 'bank_account_statement.RBL.statement';

    protected function accountStatementData()
    {
        $bankingAccount = $this->repo
            ->banking_account
            ->findByAccountNumberAndChannel($this->accountNumber, $this->channel);
        $merchantId = $bankingAccount->merchant_id;
        $all_bank_account_transactions = $this->repo
            ->banking_account_statement
            ->findByAccountNumberWithInPeriod($this->accountNumber);
        $merchant = $this->repo->merchant->find($merchantId);
        $merchantDetails = $merchant->merchantDetail;

        $accountOwnerInfo = [
            'account_name' => $merchant->name,
            'customer_address' => $merchantDetails->business_operation_address,
            'customer_address_l2' => $merchantDetails->business_registered_address_l2,
            'customer_city' => $merchantDetails->business_operation_city,
            'customer_state' => $merchantDetails->business_operation_state,
            'customer_address_pin' => $merchantDetails->business_operation_pin,
            'customer_mobile' => $merchantDetails->contact_mobile,
            'customer_email' => $merchantDetails->contact_email,
            'customer_cif_id' => $bankingAccount->bank_internal_reference_number,
            'currency' => 'INR',
            'account_opening_date' => $bankingAccount->account_activation_date,
            'account_type' => $bankingAccount->account_type,
            'account_status' => $bankingAccount->status,
            'account_number' => $bankingAccount->account_number,
            'statement_period' => 'To be done',
            'home_branch_name' => RBLBankConstants::BRANCH_NAME,
            'home_branch_address' => RBLBankConstants::BRANCH_ADDRESS,
            'ifsc_code' => RBLBankConstants::IFSC_CODE,
            'sanction_limit' => RBLBankConstants::SANCTION_LIMIT,
            'drawing_power' => RBLBankConstants::DRAWING_POWER,
            'branch_timings' => RBLBankConstants::BRANCH_TIMINGS,
            'call_center' => RBLBankConstants::CALL_CENTER_NUMBER,
            'branch_phone_number' => RBLBankConstants::BRANCH_PHONE_NUMBER,
            'branch_city' => RBLBankConstants::BRANCH_CITY,
            'branch_state' => RBLBankConstants::BRANCH_STATE,
            'branch_pincode' => RBLBankConstants::BRANCH_PINCODE
        ];

        $transactions = $this->serializeTransactions($all_bank_account_transactions);
        $statementSummary = $this->getAccountStatementSummary($all_bank_account_transactions);
        return [
            'account_owner_info' => $accountOwnerInfo,
            'transactions' => $transactions,
            'statement_summary' => $statementSummary
        ];
    }

    protected function getAccountStatementSummary($bank_account_statements)
    {
        $opening_balance = 0;
        $closing_balance = 0;
        $effective_balance = 0;
        $lien_amount = 0;
        $debit_count = 0;
        $credit_count = 0;

        if (!empty($bank_account_statements))
        {

            $opening_balance = $bank_account_statements[0]->balance;
            $closing_balance = $bank_account_statements[count($bank_account_statements) - 1]->balance;
            $effective_balance = $closing_balance;
            $lien_amount = 0;
            $debit_count = 0;
            $credit_count = 0;
            foreach ($bank_account_statements as $transaction)
            {
                if ($transaction->type == StatementType::CREDIT)
                {
                    $credit_count++;
                } else if ($transaction->type == StatementType::DEBIT)
                {
                    $debit_count++;
                }
            }
        }

        return [
            'opening_balance' => $opening_balance,
            'closing_balance' => $closing_balance,
            'effective_balance' => $effective_balance,
            'lien_amount' => $lien_amount,
            'debit_count' => $debit_count,
            'credit_count' => $credit_count,
            'statement_generated_date' => '23/05/2019 2:14 PM'
        ];
    }

    protected function serializeTransactions($bank_account_statements)
    {
        $transactions = [];
        foreach ($bank_account_statements as $transaction)
        {
            array_push(
                $transactions,
                [
                    'transaction_date' => $transaction->transaction_date,
                    'transaction_details' => $transaction->description,
                    'cheque_id' => $transaction->bank_instrument_id,
                    'value_date' => $transaction->transaction_date,
                    'balance' => $transaction->balance,
                ]
            );
        }
        return $transactions;
    }


    function pdf()
    {
        $input = $this->accountStatementData();

        return View::make(self::TEMPLATE_FILE_NAME, $input);

        // get the template
        // get the CSS
        // create the HTML
        // convert to PDF
        // upload to S3
        // send back the file handle

    }

    function csv()
    {
        // TODO: Implement csv() method.
    }

    function xlsx()
    {
        // TODO: Implement xlsx() method.
    }
}
