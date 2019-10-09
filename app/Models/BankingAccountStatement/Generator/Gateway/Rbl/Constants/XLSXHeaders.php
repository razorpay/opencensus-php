<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl\Constants;

class XLSXHeaders
{
    const SHEET_TITLE              = 'ACCOUNT DETAILS';
    const SHEET_SUB_TITLE          = 'General Details';
    const ACCOUNT_NAME             = 'Account Name:';
    const HOME_BRANCH_NAME         = 'Home Branch:';
    const CUSTOMER_ADDRESS         = 'Customer Address:';
    const CUSTOMER_MOBILE          = 'Phone:';
    const CUSTOMER_EMAIL           = 'Email:';
    const CUSTOMER_CIF_ID          = 'CIF ID:';
    const CURRENCY                 = 'A/c Currency:';
    const ACCOUNT_OPENING_DATE     = 'A/C Opening Date:';
    const ACCOUNT_TYPE             = 'A/C Type:';
    const ACCOUNT_STATUS           = 'A/c Status:';
    const ACCOUNT_NUMBER           = 'Statement Of Transactions in Savings Account Number:';
    const STATEMENT_PERIOD         = 'Period:';
    const HOME_BRANCH_ADDRESS      = 'Home Branch Address:';
    const IFSC_CODE                = 'IFSC/RTGS/NEFT code:';
    const SANCTION_LIMIT           = 'Sanction Limit:';
    const DRAWING_POWER            = 'Drawing Power:';
    const BRANCH_TIMINGS           = 'Branch Timings:';
    const CALL_CENTER              = 'Call Center:';
    const BRANCH_PHONE_NUMBER      = 'Branch Phone Num:';
    const TRANSACTION_DATE         = 'Transaction Date';
    const TRANSACTION_DETAILS      = 'Transaction Details';
    const CHEQUE_ID                = 'Cheque ID';
    const VALUE_DATE               = 'Value Date';
    const WITHDRAWL_AMT            = 'Withdrawl Amt';
    const DEPOSIT_AMT              = 'Deposit Amt';
    const BALANCE                  = 'Balance (INR)';
    const STATEMENT_SUMMARY        = 'Statement Summary';
    const OPENING_BALANCE          = 'Opening Balance:';
    const CLOSING_BALANCE          = 'Closing Balance:';
    const EFFECTIVE_BALANCE        = 'Eff Avail Bal:';
    const STATEMENT_GENERATED_DATE = 'As On:';
    const DEBIT_COUNT              = 'Count Of Debit:';
    const CREDIT_COUNT             = 'Count Of Credit:';
    const LIEN_AMOUNT              = 'Lien Amt:';
}
