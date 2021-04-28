<?php

namespace RZP\Reconciliator\NetbankingDlb;

class Constants
{
    const DEBIT_ACCOUNT_NO = 'AccountNumber';

    const PAYMENT_AMT = 'PAYMENT_AMT';

    const BANK_PAYMENT_ID = 'BankRefNo';

    const PAYMENT_DATE = 'TxnDate';

    const HOST_REF_NO = 'IBRefNo';

    const PAYMENT_ID = 'TxnRefNo';


    const REFUND_COLUMN_HEADERS = [
      'Txn Type',
      'Account Number',
      'Branch Code',
      'Txn Code',
      'Txn Date',
      'Dr / Cr',
      'Value Dt',
      'Txn CCY',
      'Amt LCY',
      'Amt TCY',
      'Rate Con',
      'Ref No',
      'Ref Doc No',
      'Transaction Desciption',
      'Benef IC',
      'Benef Name',
      'Benef Add 1',
      'Benef Add 2',
      'Benef Add 3',
      'Benef City',
      'Benef State',
      'Benef Cntry',
      'Benef Zip',
      'Option',
      'Issuer Code',
      'Payable At',
      'Flg FDT',
      'MIS Account Number'
    ];

    const PAYMENT_COLUMN_HEADERS = [
      'Sr.NO',
      'BankMerchantId',
      self::PAYMENT_DATE,
      self::PAYMENT_ID,
      self::BANK_PAYMENT_ID,
      self::PAYMENT_AMT,
      self::DEBIT_ACCOUNT_NO,
      'AccountType',
      self::HOST_REF_NO,
    ];
}
