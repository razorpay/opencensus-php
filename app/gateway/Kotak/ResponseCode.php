<?php

namespace Gateway\Kotak;

class ResponseCode
{
    protected $code = array(
        '00'    => 'Success Successful Transaction',
        'VER'   => 'Validation Error Occurs if field data is incorrect',
        'HNM'   => 'Hash Not Match Occurs if the data is tampered',
        'STO'   => 'Session Timeout If user session is timed out',
        'IER'   => 'Internal Error System Error',
        'TO'    => 'Timeout Time out while connecting to RuPay PaySecure',
        'CAN'   => 'Cancel User pressed Cancel Button',
    );
}