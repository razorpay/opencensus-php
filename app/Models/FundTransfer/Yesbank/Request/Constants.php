<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;


final class Constants
{
    // Bank Response code for successful operation
    const SUCCESS                             = 'SUCCESS';

    // Bank Response code for failed operation
    const FAILURE                             = 'FAILURE';

    //
    // Using this payment type while registering the bene
    // With PAYMENT_TYPE as OTHR we can initiate transfer to all mode
    // Mode has to be specified while initiating payment
    // NEFT - NEFT
    // RTGS - RTGS
    // IFT  - FT
    // UPI  - UPI
    // IMPS - IMPS
    //
    const BENE_PAYMENT_TYPE                   = 'OTHR';

    //
    // Flag to be sent for adding beneficiary
    // ADD: add beneficiary
    // MODIFY: modify beneficiary data
    // DELETE: remove a beneficiary
    //
    const BENE_FLAG                           = 'ADD';

    const VERIFY_BENE_FLAG                    = 'VERIFY';

    const BENE_RESPONSE_IDENTIFIER            = 'NS1maintainBeneResponse';

    const BENE_RESPONSE_BODY_IDENTIFIER       = 'soapenvBody';

    //
    // Beneficiary type to be used while registering the beneficiary
    // Supported values:
    // V: vendor
    // D: dealer
    // O: other
    //
    const BENE_TYPE                           = 'V';

    // this should be used as value for       `TransactionLimit` in verify request
    const MAX_TRANSACTION_LIMIT               = 999999999;

    const CURRENCY                            = 'INR';

    // ******* Constants used in beneficiary registration ******* //
    const REQUEST_STATUS                      = 'RequestStatus';

    const ERROR                               = 'Error';

    const BENEFICIARY_CD                      = 'BeneficiaryCd';

    const ITEM                                = 'Item';

    const REASON                              = 'Reason';

    const GENERAL_MESSAGE                     = 'GeneralMsg';

    const ERROR_SUB_CODE                      = 'ErrorSubCode';

    const FAULT_RESPONSE_IDENTIFIER           = 'Fault';

    const VERSION                             = 'version';

    const REQUEST_REFERENCE_NO                = 'requestReferenceNo';

    const CUSTOMER_ID                         = 'customerID';

    const ASYNC_TRANSFER_REQUEST_IDENTIFIER   = 'startTransfer';

    const ASYNC_TRANSFER_RESPONSE_IDENTIFIER  = 'startTransferResponse';

    const SYNC_TRANSFER_REQUEST_IDENTIFIER    = 'transfer';

    const SYNC_TRANSFER_RESPONSE_IDENTIFIER   = 'transferResponse';

    const UNIQUE_REQUEST_NO                   = 'uniqueRequestNo';

    const APP_ID                              = 'appID';

    const PURPOSE_CODE                        = 'purposeCode';

    const DEBIT_ACCOUNT_NUMBER                = 'debitAccountNo';

    const BENEFICIARY                         = 'beneficiary';

    const BENEFICIARY_CODE                    = 'beneficiaryCode';

    const BENEFICIARY_DETAILS                 = 'beneficiaryDetail';

    const BENEFICIARY_ACCOUNT_NO              = 'beneficiaryAccountNo';

    const BENEFICIARY_CONTACT                 = 'beneficiaryContact';

    const BENEFICIARY_IFSC                    = 'beneficiaryIFSC';

    const FULL_NAME                           = 'fullName';

    const BENEFICIARY_NAME                    = 'beneficiaryName';

    const TRANSFER_TYPE                       = 'transferType';

    const ATTEMPT_NO                          = 'attemptNo';

    const TRANSFER_CURRENCY_CODE              = 'transferCurrencyCode';

    const TRANSFER_AMOUNT                     = 'transferAmount';

    const REMITTER_TO_BENEFICIARY_INFO        = 'remitterToBeneficiaryInfo';

    const UNIQUE_RESPONSE_NO                  = 'uniqueResponseNo';

    const STATUS_CODE                         = 'statusCode';

    const SUB_STATUS_CODE                     = 'subStatusCode';

    const SUB_STATUS_TEXT                     = 'subStatusText';

    const RRN                                 = 'rrn';

    const REQ_TRANSFER_TYPE                   = 'reqTransferType';

    // Used only in generating mock responses
    const DEFAULT_TRANSFER_TYPE               = 'IMPS';

    const DEFAULT_CURRENCY                    = 'INR';

    const STATUS_REQUEST_IDENTIFIER           = 'getStatus';

    const STATUS_RESPONSE_IDENTIFIER          = 'getStatusResponse';

    const TRANSACTION_DATE                    = 'transactionDate';

    const TRANSACTION_STATUS                  = 'transactionStatus';

    const BANK_REFERENCE_NO                   = 'bankReferenceNo';

    const BENEFICIARY_REFERENCE_NO            = 'beneficiaryReferenceNo';

    const NAME_WITH_BENEFICIARY_BANK          = 'nameWithBeneficiaryBank';

    const LOW_BALANCE_ALERT                   = 'lowBalanceAlert';

    // ****** Attribute specific to fault response ****** //
    const TEXT                                = 'Text';

    const CODE                                = 'Code';

    const SUB_CODE                            = 'Subcode';

    const VALUE                               = 'Value';

    // ============= UPI RESPONSE KEYS ===============-

    const UPI_REQUEST_REFERENCE_NUMBER = 'request_reference_number';
    const UPI_UNIQUE_RESPONSE_NUMBER   = 'unique_response_number';
    const UPI_BANK_REFERENCE_NUMBER    = 'bank_reference_number';
    const UPI_STATUS_DESCRIPTION       = 'status_desc';
    const UPI_STATUS_CODE              = 'status_code';
    const UPI_RESPONSE_ERROR_CODE      = 'response_error_code';
    const UPI_ERROR_CODE               = 'error_code';
    const UPI_RESPONSE_CODE            = 'response_code';

    const PURPOSE_CODE_MAP              = [
        'settlement'    => 'NODAL',
        'refund'        => 'REFUND',
        'penny_testing' => 'REFUND',
    ];

    // Fund transfer type is denoted by this while using yesbank transfer
    const FT    = 'FT';
}
