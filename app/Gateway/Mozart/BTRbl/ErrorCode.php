<?php

namespace RZP\Gateway\Mozart\BTRbl;

class ErrorCode
{
    /*
     * ErrorStatus :  ER001
     * Enter a proper JSON Format Request
     */
    const ER001 = 'ER001';

    /*
     * ErrorCode : ERR_REF010
     * ErrorReason : Enter proper data types and constraints
     */
    const ER002 = 'ER002';

    /*
     * ErrorCode : ER006
     * ErrorReason : ESB Service didn’t respond because a technical roadblock
     */
    const ER006 = 'ER006';

    /*
     * ErrorCode : ER018
     * ErrorReason : Enter proper request field length and check ESB  database
     */
    const ER018 = 'ER018';

    const ERROR_CODE = [
        self::ER001 => 'ER001',
        self::ER002 => 'ER002',
        self::ER006 => 'ER006',
        self::ER018 => 'ER018',
        self::ERR005 => 'ERR005',
        self::ERR003 => 'ERR003',
        self::ERR004 => 'ERR004',
        self::ERR006 => 'ERR006',
        self::ERR007 => 'ERR007',
        self::ERR009 => 'ERR009',
        self::ERR000 => 'ERR000',
        self::ERRO01 => 'ERRO01',

    ];
}
