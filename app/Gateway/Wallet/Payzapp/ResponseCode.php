<?php

namespace RZP\Gateway\Wallet\Payzapp;

class ResponseCode
{
    public static $statusCodes = array(
        50010 => 'Init',
        50011 => 'Capture Aborted',
        50012 => '3DS Start',
        50013 => '3DS Completed',
        50014 => '3DS Failed',
        50015 => '3DS Aborted',
        50016 => 'Switch Start',
        50017 => 'Switch Timeout',
        50018 => 'Switch Aborted',
        50020 => 'Success',
        50021 => 'Failed',
        50097 => 'Test Transaction',
    );

    public static $wibmoStatusCodes = array(
        '000' => 'Success',
        '204' => 'User Abort',
        '050' => 'Failure',
        '051' => 'Internal error',
        '052' => 'Maintenance',
        '053' => 'Bad input',
        '054' => 'Missing input',
        '070' => 'Message hash failed',
    );

    public static $pgErrorCodes = array(
        0       => 'No Error',
        1       => 'Call Issuer',
        2       => 'Contact Switch Admin',
        3       => 'Retry After Some Time.',
        10001   => 'Disabled Instance',
        10002   => 'Test Instance',
        10003   => 'Instance under Maintenance',
        10004   => 'Internal Server Error',
        10005   => 'Invalid Data Sent to Switch',
        10006   => 'Internal Error caused contact Switch Admin',
        10011   => 'Disabled Acquirer',
        10012   => 'Test Acquirer',
        10013   => 'Acquirer under Maintenance',
        10021   => 'Disabled Merchant',
        10022   => 'Test Merchant',
        10023   => 'Merchant under Maintenance',
        10024   => 'Bad Input Data in Request',
        10025   => 'PGInterface not allowed',
        10026   => 'Merchant velocity check failed',
        10030   => 'Capture Aborted',
        10031   => 'Auth Aborted',
        10032   => 'Card Association not enabled',
        10033   => 'Card Range not enabled',
        10040   => 'Transaction not allowed - flow error',
        12001   => 'Acquirer Server Error',
        12002   => 'Acquirer Timeout',
        12003   => 'Acquirer Down',
        12004   => 'Acquirer Declined',
        12005   => 'Batch Closed',
        12006   => 'Totals Mismatched',
        12007   => 'Unable to settle',
        13001   => 'Issuer Server Error',
        13002   => 'Issuer Timeout',
        13003   => 'Issuer Down',
        13004   => 'Issuer Declined',
        13005   => 'Invalid Amount',
        13006   => 'Issuer Insufficient Funds',
        14001   => '3DS Failed',
        14002   => '3DS Aborted',
        14003   => 'MPI Error',
    );
}
