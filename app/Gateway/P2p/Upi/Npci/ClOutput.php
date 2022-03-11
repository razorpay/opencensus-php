<?php

namespace RZP\Gateway\P2p\Upi\Npci;

class ClOutput
{
    const ACTION            = 'action';
    const TYPE              = 'type';
    const NPCI              = 'npci';
    const VECTOR            = 'vector';
    const COUNT             = 'count';
    const TOKEN             = 'token';
    const EXPIRY            = 'expiry';

    // Used as Type for ClAction::GET_CHALLENGE
    const INITIAL           = 'initial';
    const ROTATE            = 'rotate';

    // GetCredential::CONTROLS
    const CRED_ALLOWED      = 'CredAllowed';
    const SUB_TYPE          = 'subType';
    const DTYPE             = 'dtype';
    const DLENGTH           = 'dlength';
    const CRED_TYPE         = 'credType';

    // GetCredential::SALT
    const TXN_ID            = CLInput::TXN_ID;
    const TXN_AMOUNT        = 'txnAmount';
    const DEVICE_ID         = CLInput::DEVICE_ID;
    const APP_ID            = CLInput::APP_ID;
    const MOBILE_NUMBER     = CLInput::MOBILE_NUMBER;
    const PAYER_ADDR        = 'payerAddr';
    const PAYEE_ADDR        = 'payeeAddr';

    // GetCredential::PAY_INFO
    const NAME              = 'name';
    const VALUE             = 'value';
    const PAYEE_NAME        = 'payeeName';
    const NOTE              = 'note';
    const REF_ID            = 'refId';
    const REF_URL           = 'refUrl';
    const ACCOUNT           = 'account';

    // GetCredential::Mandate
    const MANDATE_ID        = 'mandate_id';
    const MANDATE_AMOUNT    = 'mandate_amount';

}
