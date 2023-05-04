<?php

namespace RZP\Gateway\Upi\Yesbank;

class Fields
{
    // ==== REQUEST FIELDS FROM FTS TO GATEWAY SERVICE ====

    const REF_ID                    = 'ref_id';
    const GATEWAY_INPUT             = 'gateway_input';
    const TERMINAL                  = 'terminal';
    const MERCHANT                  = 'merchant';
    const GATEWAY_MERCHANT_ID       = 'gateway_merchant_id';
    const CATEGORY                  = 'category';
    const NARRATION                 = 'narration';
    const ACCOUNT_NUMBER            = 'account_number';
    const IFSC_CODE                 = 'ifsc_code';

    // ==== RESPONSE FIELDS FROM GATEWAY SERVICE TO FTS ====

    const SUCCESS                   = 'success';
    const REQUEST_REFERENCE_NUMBER  = 'request_reference_number';
    const UNIQUE_RESPONSE_NUMBER    = 'unique_response_number';
    const BANK_REFERENCE_NUMBER     = 'bank_reference_number';
    const API_ERROR_CODE            = 'api_error_code';
    const STATUS_CODE               = 'status_code';
    const STATUS_DESC               = 'status_desc';
    const RESPONSE_CODE             = 'response_code';



    // ==== REQUEST FIELDS FOR YESBANK ====

    const PGMERCHANTID     = 'pgMerchantId';
    const ORDERNO          = 'orderno';
    const TXN_NOTE         = 'txn_note';
    const AMOUNT           = 'amount';
    const CURRENCY         = 'currency';
    const PAYMENT_TYPE     = 'payment_type';
    const TXN_TYPE         = 'txn_type';
    const MCC              = 'mcc';
    const EXP_TIME         = 'exp_time';
    const PAYEE_ACC_NO     = 'payee_acc_no';
    const PAYEE_IFSC       = 'payee_ifsc';
    const PAYEE_AADHAR     = 'payee_aadhar';
    const PAYEE_MB_NO      = 'payee_mb_no';
    const PAYEE_VPA        = 'payee_vpa';
    const SUBMERCHANT_ID   = 'submerchant_id';
    const WHITELISTED_ACC  = 'whitelisted_acc';
    const PAYEE_MMID       = 'payee_mmid';
    const REF_URL          = 'ref_url';
    const TRANSFER_TYPE    = 'transfer_type';
    const PAYEE_NAME       = 'payee_name';
    const PAYEE_ADDRESS    = 'payee_address';
    const PAYEE_EMAIL      = 'payee_email';
    const PAYER_ACCNO      = 'payer_accno';
    const PAYER_IFSC       = 'payer_ifsc';
    const PAYER_MB_NO      = 'payer_mb_no';
    const PAYYE_VPA_TYPE   = 'payye_vpa_type';
    const ADD1             = 'add1';
    const ADD2             = 'add2';
    const ADD3             = 'add3';
    const ADD4             = 'add4';
    const ADD5             = 'add5';
    const ADD6             = 'add6';
    const ADD7             = 'add7';
    const ADD8             = 'add8';
    const ADD9             = 'add9';
    const ADD10            = 'add10';
    const REQUESTMSG       = 'requestMsg';

    // ==== PAYOUT VERIFY REQUEST FIELDS FOR YESBANK ====
    const PGMERCHANT_ID          = 'pgmerchant_id';
    const ORDER_ID               = 'order_id';
    const YBLREFNO               = 'yblrefno';
    const CUST_REF_ID            = 'cust_ref_id';
    const REFERENCE_ID           = 'reference_id';

    // ==== RESPONSE FIELDS FROM YESBANK ====

    const DATE                   = 'date';
    const STATUSCODE             = 'statuscode';
    const STATUSDESC             = 'statusdesc';
    const RESPCODE               = 'respcode';
    const APPROVALNUM            = 'approvalnum';
    const PRFVADDR               = 'prfvaddr';
    const TXNID                  = 'txnid';
    const RRN                    = 'rrn';
    const PRACCNO                = 'praccno';
    const PRIFSC                 = 'prifsc';
    const PRACCNAME              = 'praccname';
    const ERRORCODE              = 'errorcode';
    const TRANSFERTYPE           = 'transfertype';
    const PYFVADDR               = 'pyfvaddr';
    const PYIFSCCODE             = 'pyifsccode';
    const PYACCNO                = 'pyaccno';
    const PUAADHAR               = 'puaadhar';
    const PYACCNAME              = 'pyaccname';
    const PAYER_VPA              = 'payer_vpa';
    const NPCI_TXN_ID            = 'npci_txn_id';
    const PAYER_ACC_NO           = 'payer_acc_no';
    const PAYER_IFSC_NO          = 'payer_ifsc_no';
    const PAYER_ACC_NAME         = 'payer_account_name';
    const ERROR_CODE             = 'error_code';
    const RESPONSE_ERROR_CODE    = 'response_error_code';
    const PAYEE_ACC_NAME         = 'payee_account_name';
    const TIMED_OUT_TXN_STATUS   = 'timed_out_txn_status';
    const MERCHANT_REF_NO        = 'merchant_ref_no';
    const MERCHANT_REFERENCE     = 'merchant_reference';
    const NPCI_REFERENCE_ID      = 'npci_reference_id';
    const GATEWAY_PAYMENT_ID     = 'gateway_payment_id';
    const TRANSACTION_AUTH_DATE  = 'TransactionAuthDate';
    const STATUS                 = 'status';
    const PAYER_NOTE             = 'PayerNote';
    const AMOUNT_AUTHORIZED      = 'amount_authorized';

    const PAYOUT = [
      self::YBLREFNO,
      self::ORDERNO,
      self::AMOUNT,
      self::DATE,
      self::STATUSCODE,
      self::STATUSDESC,
      self::RESPCODE,
      self::APPROVALNUM,
      self::PAYER_VPA,
      self::NPCI_TXN_ID,
      self::CUST_REF_ID,
      self::PAYER_ACC_NO,
      self::PAYER_IFSC_NO,
      self::PAYER_ACC_NAME,
      self::ERROR_CODE,
      self::RESPONSE_ERROR_CODE,
      self::TRANSFER_TYPE,
      self::PAYEE_VPA,
      self::PAYEE_IFSC,
      self::PAYEE_ACC_NO,
      self::PAYEE_AADHAR,
      self::PAYEE_ACC_NAME,
      self::ADD1,
      self::ADD2,
      self::ADD3,
      self::ADD4,
      self::ADD5,
      self::ADD6,
      self::ADD7,
      self::ADD8,
      self::ADD9,
      self::ADD10,
    ];

    const PAYOUT_VERIFY = [
        self::YBLREFNO,
        self::ORDERNO,
        self::AMOUNT,
        self::DATE,
        self::STATUSCODE,
        self::STATUSDESC,
        self::RESPCODE,
        self::APPROVALNUM,
        self::PAYER_VPA,
        self::NPCI_TXN_ID,
        self::REFERENCE_ID,
        self::CUST_REF_ID,
        self::PAYER_ACC_NO,
        self::PAYER_IFSC_NO,
        self::PAYER_ACC_NAME,
        self::PAYEE_VPA,
        self::PAYEE_IFSC,
        self::PAYEE_ACC_NO,
        self::PAYEE_AADHAR,
        self::PAYEE_ACC_NAME,
        self::TIMED_OUT_TXN_STATUS,
        self::ADD2,
        self::ADD3,
        self::ADD4,
        self::ADD5,
        self::ADD6,
        self::ADD7,
        self::ADD8,
        self::ADD9,
        self::ADD10,
    ];
}
