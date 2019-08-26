<?php


namespace RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Rbl;

use Carbon\Carbon;
use mikehaertl\wkhtmlto\Pdf;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Base;
use RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Rbl\RBLBankInformation as RBLBankConstants;
use RZP\Models\BankingAccountStatement\Type as StatementType;
use RZP\Models\FileStore;
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

        $account_opening_date = Carbon::createFromTimestamp($bankingAccount->account_activation_date, Timezone::IST)
            ->format('d/m/Y');

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
            'account_opening_date' => $account_opening_date,
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
            'opening_balance' => (float) $opening_balance / 100,
            'closing_balance' => (float) $closing_balance / 100,
            'effective_balance' => (float) $effective_balance / 100,
            'lien_amount' => (float) $lien_amount / 100,
            'debit_count' => (float) $debit_count,
            'credit_count' => $credit_count,
            'statement_generated_date' => Carbon::createFromTimestamp(time(), Timezone::IST)->format('d/m/Y H:i')

        ];
    }

    protected function serializeTransactions($bank_account_statements)
    {
        $transactions = [];
        foreach ($bank_account_statements as $transaction)
        {
            $line_item = [
                'transaction_date' => Carbon::createFromTimestamp($transaction->transaction_date, Timezone::IST)
                    ->format('d/m/Y'),
                'transaction_details' => $transaction->description,
                'cheque_id' => $transaction->bank_instrument_id,
                'value_date' => Carbon::createFromTimestamp($transaction->transaction_date, Timezone::IST)
                    ->format('d/m/Y'),
                'balance' => (float) $transaction->balance / 100,
            ];

            if ($transaction->type == 'credit')
            {
                $line_item['withdrawal_amount'] = (float) $transaction->amount / 100;
                $line_item['deposit_amount'] = null;
            } else if ($transaction->type == 'debit')
            {
                $line_item['deposit_amount'] = (float) $transaction->amount / 100;
                $line_item['withdrawal_amount'] = null;
            }

            array_push($transactions, $line_item);
        }
        return $transactions;
    }


    function pdf()
    {
        $input = $this->accountStatementData();
        $htmlAccountStatement = View::make(self::TEMPLATE_FILE_NAME, $input);
//        return $htmlAccountStatement;
        $pdfAccountStatement = $this->getPdfContent($htmlAccountStatement);
        $fileStoreHandle = (new FileStore\Creator())
            ->name('TestPDFAccountStatement')
            ->content($pdfAccountStatement)
            ->extension(FileStore\Format::PDF)
            ->mime('application/pdf')
            ->store(FileStore\Store::S3)
            ->type(FileStore\Type::RBL_NETBANKING_CLAIM)
            ->save()
            ->getFileInstance();
        return $fileStoreHandle;

    }

    protected function getPdfContent(string $html): string
    {
        $options = [
            'print-media-type',
            'footer-font-size' => '9',
            'footer-right' => 'Page [page] of [topage]',
            'footer-left' => 'Date and Time: ' . Carbon::createFromTimestamp(time(), Timezone::IST)
                    ->format('d/m/Y h:i A'),
            'dpi' => 290,
            'zoom' => 1,
            'ignoreWarnings' => false,
            'encoding' => 'UTF-8',
            'binary' => '/usr/local/bin/wkhtmltopdf',
        ];

        $pdf = (new Pdf($options))->addPage($html);

        $pdfContent = $pdf->toString();

        if ($pdfContent === false)
        {
            throw new Exception\LogicException('Pdf generation failed: ' . $pdf->getError());
        }

        return $pdfContent;
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
