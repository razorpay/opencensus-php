<?php

namespace RZP\Gateway\Wallet\Jiomoney;

/**
 * STATUSQUERY API has completely differently spelt request fields compared to
 * other JioMoney APIs so created a new class for them
 */
class StatusQueryRequestFields extends RequestFields
{
    const REQUEST_HEADER = 'request_header';
    const VERSION        = 'version';
    const API_NAME       = 'api_name';
    const PAYLOAD_DATA   = 'payload_data';
    const CLIENT_ID      = 'client_id';
    const MERCHANT_ID    = 'merchant_id';
    const TRAN_REF_NO    = 'tran_ref_no';
}
