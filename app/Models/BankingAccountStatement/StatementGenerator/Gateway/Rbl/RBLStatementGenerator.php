<?php


namespace RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Rbl;

use Carbon\Carbon;
use mikehaertl\wkhtmlto\Pdf;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Base;
use RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Rbl\RBLBankInformation as RBLBankConstants;
use RZP\Models\BankingAccountStatement\Type;
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
            ->findByAccountNumberWithInPeriod($this->accountNumber, $this->fromDate, $this->toDate);
        $merchant = $this->repo->merchant->find($merchantId);
        $merchantDetails = $merchant->merchantDetail;

        $account_opening_date = Carbon::createFromTimestamp($bankingAccount->account_activation_date, Timezone::IST)
            ->format('d/m/Y');

        $fromDateReadable = Carbon::createFromTimestamp($this->fromDate, Timezone::IST)
            ->format('d/m/Y');
        $toDateReadable = Carbon::createFromTimestamp($this->toDate, Timezone::IST)
            ->format('d/m/Y');
        $statementPeriod = $fromDateReadable . ' - ' . $toDateReadable;
        $accountOwnerInfo = [
            AccountOwnerInfo::ACCOUNT_NAME => $merchant->name,
            AccountOwnerInfo::CUSTOMER_ADDRESS => $merchantDetails->business_operation_address,
            AccountOwnerInfo::CUSTOMER_ADDRESS_L2 => $merchantDetails->business_registered_address_l2,
            AccountOwnerInfo::CUSTOMER_CITY => $merchantDetails->business_operation_city,
            AccountOwnerInfo::CUSTOMER_STATE => $merchantDetails->business_operation_state,
            AccountOwnerInfo::CUSTOMER_ADDRESS_PIN => $merchantDetails->business_operation_pin,
            AccountOwnerInfo::CUSTOMER_MOBILE => $merchantDetails->contact_mobile,
            AccountOwnerInfo::CUSTOMER_EMAIL => $merchantDetails->contact_email,
            AccountOwnerInfo::CUSTOMER_CIF_ID => $bankingAccount->bank_internal_reference_number,
            AccountOwnerInfo::CURRENCY => AccountOwnerInfo::INR,
            AccountOwnerInfo::ACCOUNT_OPENING_DATE => $account_opening_date,
            AccountOwnerInfo::ACCOUNT_TYPE => $bankingAccount->account_type,
            AccountOwnerInfo::ACCOUNT_STATUS => $bankingAccount->status,
            AccountOwnerInfo::ACCOUNT_NUMBER => $bankingAccount->account_number,
            AccountOwnerInfo::STATEMENT_PERIOD => $statementPeriod,
            AccountOwnerInfo::HOME_BRANCH_NAME => RBLBankConstants::BRANCH_NAME,
            AccountOwnerInfo::HOME_BRANCH_ADDRESS => RBLBankConstants::BRANCH_ADDRESS,
            AccountOwnerInfo::IFSC_CODE => RBLBankConstants::IFSC_CODE,
            AccountOwnerInfo::SANCTION_LIMIT => RBLBankConstants::SANCTION_LIMIT,
            AccountOwnerInfo::DRAWING_POWER => RBLBankConstants::DRAWING_POWER,
            AccountOwnerInfo::BRANCH_TIMINGS => RBLBankConstants::BRANCH_TIMINGS,
            AccountOwnerInfo::CALL_CENTER => RBLBankConstants::CALL_CENTER_NUMBER,
            AccountOwnerInfo::BRANCH_PHONE_NUMBER => RBLBankConstants::BRANCH_PHONE_NUMBER,
            AccountOwnerInfo::BRANCH_CITY => RBLBankConstants::BRANCH_CITY,
            AccountOwnerInfo::BRANCH_STATE => RBLBankConstants::BRANCH_STATE,
            AccountOwnerInfo::BRANCH_PINCODE => RBLBankConstants::BRANCH_PINCODE
        ];

        $transactions = $this->serializeTransactions($all_bank_account_transactions);
        $statementSummary = $this->getAccountStatementSummary($all_bank_account_transactions);
        return [
            Response::ACCOUNT_OWNER_INFO => $accountOwnerInfo,
            Response::TRANSACTIONS => $transactions,
            Response::STATEMENT_SUMMARY => $statementSummary
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

        if ($bank_account_statements->count())
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
            StatementSummary::OPENING_BALANCE => (float) $opening_balance / 100,
            StatementSummary::CLOSING_BALANCE => (float) $closing_balance / 100,
            StatementSummary::EFFECTIVE_BALANCE => (float) $effective_balance / 100,
            StatementSummary::LIEN_AMOUNT => (float) $lien_amount / 100,
            StatementSummary::DEBIT_COUNT => (float) $debit_count,
            StatementSummary::CREDIT_COUNT => $credit_count,
            StatementSummary::STATEMENT_GENERATED_DATE => Carbon::createFromTimestamp(time(), Timezone::IST)
                ->format(StatementSummary::STATEMENT_GENERATED_DATE_FORMAT)

        ];
    }

    protected function serializeTransactions($bank_account_statements)
    {
        $transactions = [];
        foreach ($bank_account_statements as $transaction)
        {
            $line_item = [
                TransactionLineItem::TRANSACTION_DATE =>
                    Carbon::createFromTimestamp($transaction->transaction_date, Timezone::IST)
                    ->format(TransactionLineItem::ITEM_DATE_FORMAT),
                TransactionLineItem::TRANSACTION_DETAILS => $transaction->description,
                TransactionLineItem::CHEQUE_ID => $transaction->bank_instrument_id,
                TransactionLineItem::VALUE_DATE =>
                                    Carbon::createFromTimestamp($transaction->transaction_date, Timezone::IST)
                                    ->format(TransactionLineItem::ITEM_DATE_FORMAT),
                TransactionLineItem::BALANCE => (float) $transaction->balance / 100
            ];

            if ($transaction->type == Type::CREDIT)
            {
                $line_item[TransactionLineItem::WITHDRAWAL_AMOUNT] = (float) $transaction->amount / 100;
                $line_item[TransactionLineItem::DEPOSIT_AMOUNT] = null;
            } else if ($transaction->type == Type::DEBIT)
            {
                $line_item[TransactionLineItem::DEPOSIT_AMOUNT] = (float) $transaction->amount / 100;
                $line_item[TransactionLineItem::WITHDRAWAL_AMOUNT] = null;
            }

            array_push($transactions, $line_item);
        }
        return $transactions;
    }


    function pdf()
    {
        $input = $this->accountStatementData();
        $htmlAccountStatement = View::make(self::TEMPLATE_FILE_NAME, $input);
        $pdfAccountStatement = $this->getPdfContent($htmlAccountStatement);
        $fileName = $this->accountNumber;
        $fileStoreHandle = (new FileStore\Creator())
            ->name($fileName)
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
            'footer-font-size' => '6',
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
