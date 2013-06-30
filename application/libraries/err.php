<?php

/**
 * Defines the constant error for the application to use.
 */
class ERR
{

    /**
     * We don't want the class to have objects.
     */
    private function __construct() {}
    
    const NO_ERROR = NULL;

    const SUCCESS = 0x0;

    const INVALID_PARAMETERS = 0x1;
    const MSG_1 = "The parameters provided are invalid.";

    const TOKEN_ALREADY_USED = 0x2;
    const MSG_2 = "This token has alreay been used and hence expired.";

    const CVV_ALREADY_VERIFIED = 0x3;
    const MSG_3 = "The CVV of the card has already been verified previously.";

    const DB_PROBLEM = 0x4;
    const MSG_4 = "There is a problem with database";

    const INTERNAL_SERVER_ERROR = 0x5;
    const MSG_5 = "There is a problem with the server.";

}

