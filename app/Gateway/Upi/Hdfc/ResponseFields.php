<?php

namespace RZP\Gateway\Upi\Hdfc;

class ResponseFields
{
    const AMOUNT                =   'amount';
    const APPROVAL_NO           =   'approval_no';
    const CALLBACK_RESPONSE_KEY =   'meRes';
    const CUSTOMER_REFERENCE_ID =   'customer_reference_id';
    const NPCI_UPI_TXN_ID       =   'npci_upi_txn_id';
    const PAYEE_VA              =   'payee_va';
    const PAYER_VA              =   'payer_va';
    const PAYMENT_ID            =   'payment_id';
    const REFERENCE_ID          =   'reference_id';
    const RESPCODE              =   'respcode';
    const STATUS                =   'status';
    const STATUS_DESCRIPTION    =   'status_description';
    const TXN_AUTH_DATE         =   'txn_auth_date';
    const UPI_TXN_ID            =   'upi_txn_id';
}
