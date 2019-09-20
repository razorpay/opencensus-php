<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

// All Fields that are received as parameters in the Response
class ResponseFields
{
    const CHECKSUM      = 'checkSumVal';
    const RESPONSE_XML  = 'MandateRespDoc';
    const RESPONSE_TYPE = 'respType';

    // Verify
    const TRANSACTION_STATUS = 'tranStatus';
}
