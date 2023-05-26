<?php

namespace RZP\Models\UpiTransfer;

class GatewayResponseParams
{
    const GATEWAY               = 'gateway';
    const AMOUNT                = 'amount';
    const PAYEE_VPA             = 'payee_vpa';
    const PAYER_VPA             = 'payer_vpa';
    const PAYER_BANK            = 'payer_bank';
    const PAYER_IFSC            = 'payer_ifsc';
    const PAYER_ACCOUNT         = 'payer_account';
    const TRANSACTION_TIME      = 'transaction_time';
    const GATEWAY_MERCHANT_ID   = 'gateway_merchant_id';
    const NPCI_REFERENCE_ID     = 'npci_reference_id';
    const PROVIDER_REFERENCE_ID = 'provider_reference_id';
    const TRANSACTION_REFERENCE = 'transaction_reference';
    const PAYER_ACCOUNT_TYPE    = 'payer_account_type';
}
