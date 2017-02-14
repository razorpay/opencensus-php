<?php

namespace RZP\Gateway\Wallet\Jiomoney;

/**
 * STATUSQUERY API has completely differently spelt response fields compared to
 * other JioMoney APIs so created a new class for them
 */
class StatusQueryResponseFields extends ResponseFields
{
    const RESPONSE_HEADER = 'response_header';
    const API_STATUS      = 'api_status';
    const PAYLOAD_DATA    = 'payload_data';
    const TXN_STATUS      = 'txn_status';
    const JM_TRAN_REF_NO  = 'jm_tran_ref_no';
}
